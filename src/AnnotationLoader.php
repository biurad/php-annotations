<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Annotations;

use Spiral\Attributes\ReaderInterface;

/**
 * This class allows loading of annotations/attributes using listeners.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AnnotationLoader implements LoaderInterface
{
    /** @var ReaderInterface */
    private $reader;

    /** @var mixed[] */
    private $annotations;

    /** @var ListenerInterface[] */
    private $listeners = [];

    /** @var string[] */
    private $resources = [];

    /** @var null|callable(string[]) */
    private $classLoader;

    /**
     * @param callable $classLoader
     */
    public function __construct(ReaderInterface $reader, callable $classLoader = null)
    {
        $this->reader = $reader;
        $this->classLoader = $classLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function listener(ListenerInterface ...$listeners): void
    {
        $this->listeners += $listeners;
    }

    /**
     * {@inheritdoc}
     */
    public function resource(string ...$resources): void
    {
        $this->resources += $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function build(): void
    {
        $this->annotations = $annotations = $classes = $files = [];

        foreach ($this->resources as $resource) {
            if (\is_dir($resource)) {
                $files += $this->findFiles($resource);

                continue;
            }

            if (!(\class_exists($resource) || \function_exists($resource))) {
                continue;
            }

            $classes[] = $resource;
        }

        $classes += $this->findClasses($files);

        foreach ($classes as $class) {
            $annotations[] = $this->findAnnotations($class);
        }

        foreach ($this->listeners as $listener) {
            $listenerAnnotations = [];

            foreach ($annotations as $annotation) {
                if (isset($annotation[$listener->getAnnotation()])) {
                    $listenerAnnotations[] = $annotation[$listener->getAnnotation()];
                }
            }

            $found = $listener->load($listenerAnnotations);

            if (null !== $found) {
                $this->annotations[] = $found;
            }
        }

        \gc_mem_caches();
    }

    /**
     * {@inheritdoc}
     */
    public function load(): iterable
    {
        if (null === $this->annotations) {
            $this->build();
        }

        return $this->annotations;
    }

    /**
     * Finds annotations in the given resource.
     *
     * @param class-string|string $resource
     *
     * @return Locate\Class_[]|Locate\Function_[]
     */
    private function findAnnotations(string $resource)
    {
        $annotations = [];

        foreach ($this->listeners as $listener) {
            $annotationClass = $listener->getAnnotation();

            if (\function_exists($resource)) {
                $funcReflection = new \ReflectionFunction($resource);
                $function = $this->fetchFunctionAnnotation($funcReflection, $this->getAnnotations($funcReflection, $annotationClass), $annotationClass);

                if (null !== $function) {
                    $annotations[$annotationClass] = $function;
                }

                continue;
            }

            $classReflection = new \ReflectionClass($resource);

            if ($classReflection->isAbstract()) {
                continue;
            }

            $annotation = new Locate\Class_($this->getAnnotations($classReflection, $annotationClass), $classReflection);

            // Reflections belonging to class object.
            $reflections = \array_merge(
                $classReflection->getMethods(),
                $classReflection->getProperties(),
                $classReflection->getConstants()
            );

            $annotations[$annotationClass] = $this->fetchAnnotations($annotation, $reflections, $annotationClass);
        }

        return $annotations;
    }

    /**
     * @param class-string $annotation
     *
     * @return iterable<object>
     */
    private function getAnnotations(\Reflector $reflection, string $annotation): iterable
    {
        $annotations = [];


        if ($reflection instanceof \ReflectionClass) {
            $annotations = $this->reader->getClassMetadata($reflection, $annotation);
        } elseif ($reflection instanceof \ReflectionFunctionAbstract) {
            $annotations = $this->reader->getFunctionMetadata($reflection, $annotation);
        } elseif ($reflection instanceof \ReflectionProperty) {
            $annotations = $this->reader->getPropertyMetadata($reflection, $annotation);
        } elseif ($reflection instanceof \ReflectionClassConstant) {
            $annotations = $this->reader->getConstantMetadata($reflection, $annotation);
        } elseif ($reflection instanceof \ReflectionParameter) {
            $annotations = $this->reader->getParameterMetadata($reflection, $annotation);
        }

        return $annotations instanceof \Generator ? \iterator_to_array($annotations) : $annotations;
    }

    /**
     * Fetch annotations from methods, constant, property and methods parameter.
     *
     * @param \Reflector[] $reflections
     */
    private function fetchAnnotations(Locate\Class_ $classAnnotation, array $reflections, string $annotationClass): ?Locate\Class_
    {
        $classRefCount = 0;

        foreach ($reflections as $name => $reflection) {
            if (\is_string($name)) {
                $reflection = new \ReflectionClassConstant((string) $classAnnotation, $name);
            }

            $annotations = $this->getAnnotations($reflection, $annotationClass);

            if ($reflection instanceof \ReflectionMethod) {
                $method = $this->fetchFunctionAnnotation($reflection, $annotations, $annotationClass);

                if ($method instanceof Locate\Method) {
                    $classAnnotation->methods[] = $method;
                    ++$classRefCount;
                }

                continue;
            }

            if ([] === $annotations) {
                continue;
            }
            ++$classRefCount;

            if ($reflection instanceof \ReflectionProperty) {
                $classAnnotation->properties[] = new Locate\Property($annotations, $reflection);

                continue;
            }

            if ($reflection instanceof \ReflectionClassConstant) {
                $classAnnotation->constants[] = new Locate\Constant($annotations, $reflection);

                continue;
            }
        }

        if (0 === $classRefCount && [] === $classAnnotation->getAnnotation()) {
            return null;
        }

        return $classAnnotation;
    }

    /**
     * @return Locate\Method|Locate\Function_|null
     */
    private function fetchFunctionAnnotation(\ReflectionFunctionAbstract $reflection, iterable $annotations, string $annotationClass)
    {
        if ($reflection instanceof \ReflectionMethod) {
            $function = new Locate\Method($annotations, $reflection);
        } else {
            $function = new Locate\Function_($annotations, $reflection);
        }

        foreach ($reflection->getParameters() as $parameter) {
            $attributes = $this->getAnnotations($parameter, $annotationClass);

            if ([] !== $attributes) {
                $function->parameters[] = new Locate\Parameter($attributes, $parameter);
            }
        }

        return ([] !== $annotations || [] !== $function->parameters) ? $function : null;
    }

    /**
     * Finds classes in the given resource directory.
     *
     * @param string[] $files
     *
     * @return class-string[]
     */
    private function findClasses(array $files): array
    {
        if ([] === $files) {
            return [];
        }

        if (null !== $this->classLoader) {
            return ($this->classLoader)($files);
        }

        $declared = \get_declared_classes();

        foreach ($files as $file) {
            require_once $file;
        }

        return \array_diff(\get_declared_classes(), $declared);
    }

    /**
     * Finds files in the given resource.
     *
     * @return string[]
     */
    private function findFiles(string $resource): array
    {
        $directory = new \RecursiveDirectoryIterator($resource, \FilesystemIterator::CURRENT_AS_PATHNAME);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = new \RegexIterator($iterator, '/\.php$/');

        return \iterator_to_array($files);
    }
}
