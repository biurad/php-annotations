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
    /** @var ReaderInterface|null */
    private $reader;

    /** @var mixed[] */
    private $annotations;

    /** @var array<string,ListenerInterface> */
    private $listeners = [];

    /** @var string[] */
    private $resources = [];

    /** @var null|callable(string[]) */
    private $classLoader;

    /**
     * @param callable $classLoader
     */
    public function __construct(ReaderInterface $reader = null, callable $classLoader = null)
    {
        if (\PHP_VERSION_ID < 80000 && null === $reader) {
            throw new \RuntimeException(\sprintf('A "%s" instance to read annotations/attributes not available.', ReaderInterface::class));
        }

        $this->reader = $reader;
        $this->classLoader = $classLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function listener(ListenerInterface ...$listeners): void
    {
        foreach ($listeners as $listener) {
            $this->listeners[$listener->getAnnotation()] = $listener;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resource(string ...$resources): void
    {
        foreach ($resources as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(?string ...$annotationClass): void
    {
        $this->annotations = $annotations = $files = [];

        if (1 === \count($annotationClass = \array_merge($annotationClass, \array_keys($this->listeners)))) {
            $annotationClass = $annotationClass[0];
        }

        foreach ($this->resources as $resource) {
            if (\is_dir($resource)) {
                $files += $this->findFiles($resource);
            } elseif (\function_exists($resource) || \class_exists($resource)) {
                $annotations = \array_replace_recursive($annotations, $this->findAnnotations($resource, $annotationClass));
            }
        }

        if (!empty($files)) {
            foreach ($this->findClasses($files) as $class) {
                $annotations = \array_replace_recursive($annotations, $this->findAnnotations($class, $annotationClass));
            }
        }

        foreach (\array_unique((array) $annotationClass) as $annotation) {
            $loadedAnnotation = \array_filter($annotations[$annotation] ?? []);

            if (isset($this->listeners[$annotation])) {
                $loadedAnnotation = $this->listeners[$annotation]->load($loadedAnnotation);
            }

            $this->annotations[$annotation] = $loadedAnnotation;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $annotationClass = null, bool $stale = true)
    {
        if (!$stale || null === $this->annotations) {
            $this->build($annotationClass);
        }

        if (isset($annotationClass, $this->annotations[$annotationClass])) {
            return $this->annotations[$annotationClass] ?? null;
        }

        return \array_filter($this->annotations);
    }

    /**
     * Finds annotations in the given resource.
     *
     * @param class-string|string $resource
     * @param string[]|string     $annotationClass
     *
     * @return Locate\Class_[]|Locate\Function_[]
     */
    private function findAnnotations(string $resource, $annotationClass): iterable
    {
        if (empty($annotationClass)) {
            return [];
        }

        if (\is_array($annotationClass)) {
            $annotations = [];

            foreach ($annotationClass as $annotation) {
                $annotations = \array_replace_recursive($annotations, $this->findAnnotations($resource, $annotation));
            }

            return $annotations;
        }

        if (\function_exists($resource)) {
            $funcReflection = new \ReflectionFunction($resource);
            $annotation = $this->fetchFunctionAnnotation($funcReflection, $this->getAnnotations($funcReflection, $annotationClass), $annotationClass);

            goto annotation;
        }

        $classReflection = new \ReflectionClass($resource);

        if ($classReflection->isAbstract()) {
            return [];
        }

        $annotation = $this->fetchAnnotations(
            new Locate\Class_($this->getAnnotations($classReflection, $annotationClass), $classReflection),
            \array_merge($classReflection->getMethods(), $classReflection->getProperties(), $classReflection->getConstants()),
            $annotationClass
        );

        annotation:
        return [$annotationClass => [$resource => $annotation]];
    }

    /**
     * @param class-string $annotation
     *
     * @return iterable<object>
     */
    private function getAnnotations(\Reflector $reflection, string $annotation): iterable
    {
        $annotations = [];

        if (null === $this->reader) {
            return \array_map(static function (\ReflectionAttribute $attribute): object {
                return $attribute->newInstance();
            }, $reflection->getAttributes($annotation));
        }

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
     * @return string[]
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
