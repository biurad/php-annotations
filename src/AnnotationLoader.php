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
class AnnotationLoader
{
    /** @var ReaderInterface|null */
    private $reader;

    /** @var array<string,mixed> */
    private $loadedAttributes = [], $loadedListeners = [], $aliases = [];

    /** @var array<string,ListenerInterface> */
    private $listeners = [];

    /** @var string[] */
    private $resources = [];

    /** @var callable(string[])|null */
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
     * Attach a listener to the loader.
     */
    public function listener(ListenerInterface $listener, string $alias = null): void
    {
        $this->listeners[$name = \get_class($listener)] = $listener;
        unset($this->loadedListeners[$name]);

        if (null !== $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    /**
     * Attache(s) the given resource(s) to the loader.
     *
     * @param string ...$resources type of class string, function name, file, or directory
     */
    public function resource(string ...$resources): void
    {
        foreach ($resources as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * Load annotations/attributes from the given resource(s).
     *
     * @param string ...$listener the name of class name for registered lister
     *                            annotation/attribute class name or listener's aliased name
     */
    public function load(string ...$listener)
    {
        $loadedAnnotation = [];

        if (empty($listener)) {
            if (empty($this->loadedListeners)) {
                foreach ($this->listeners as $name => $value) {
                    $this->loadedListeners[$name] = $value->load($this->build(...$value->getAnnotations()));
                }
            }

            $loadedAnnotation = $this->loadedListeners;
        } else {
            foreach ($listener as $name) {
                $name = $this->aliases[$name] ?? $name;
                $loaded = ($this->loadedListeners[$name] ?? $this->loadedAttributes[$name] ?? null);

                if (null === $loaded) {
                    $l = $this->listeners[$name] ?? null;

                    if ($l instanceof ListenerInterface) {
                        $this->loadedListeners[$name] = $l->load($this->build(...$l->getAnnotations()));
                    }

                    $loaded = $this->loadedListeners[$name] ?? ($this->loadedAttributes[$name] = $this->build($name));
                }

                $loadedAnnotation[$name] = $loaded;
            }
        }

        return 1 === \count($loadedAnnotation) ? \current($loadedAnnotation) : $loadedAnnotation;
    }

    /**
     * Builds the attributes/annotations for the given class or function.
     *
     * @return array<int,array<string, mixed>>
     */
    protected function build(string ...$annotationClass): array
    {
        $annotations = [];

        foreach ($this->resources as $resource) {
            $values = [];

            if (\is_dir($resource)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resource, \FilesystemIterator::CURRENT_AS_PATHNAME));
                $files = new \RegexIterator($iterator, '/\.php$/');

                if (\iterator_count($files) > 0) {
                    $values = ($this->classLoader ?? [$this, 'findClasses'])($files);

                    foreach ($values as $class) {
                        $classes = $this->fetchClassAnnotation($class, $annotationClass);

                        if (!empty($classes)) {
                            $annotations[] = $classes;
                        }
                    }
                }
                continue;
            }

            if (\function_exists($resource)) {
                $values = $this->fetchFunctionAnnotation(new \ReflectionFunction($resource), $annotationClass);
            } elseif (\class_exists($resource)) {
                $values = $this->fetchClassAnnotation($resource, $annotationClass);
            }

            if (!empty($values)) {
                $annotations[] = $values;
            }
        }

        return $annotations;
    }

    /**
     * @param array<int,string>|string $annotation
     *
     * @return array<int,object>
     */
    private function getAnnotations(\Reflector $reflection, $annotation): array
    {
        $annotations = [];

        if (\is_array($annotation)) {
            foreach ($annotation as $annotationClass) {
                $annotations = \array_merge($annotations, $this->getAnnotations($reflection, $annotationClass));
            }

            return $annotations;
        }

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

        return $annotations instanceof \Traversable ? \iterator_to_array($annotations) : $annotations;
    }

    /**
     * Finds annotations in the given resource.
     *
     * @param class-string|string $resource
     * @param array<int,string>   $annotationClass
     *
     * @return array<string,mixed>
     */
    private function fetchClassAnnotation(string $resource, array $annotationClass): array
    {
        $classReflection = new \ReflectionClass($resource);

        if ($classReflection->isAbstract()) {
            return [];
        }

        $classRefCount = 0;
        $constants = $properties = $methods = [];
        $reflections = \array_merge($classReflection->getMethods(), $classReflection->getProperties(), $classReflection->getConstants());

        foreach ($reflections as $name => $reflection) {
            if (\is_string($name)) {
                $reflection = new \ReflectionClassConstant($classReflection->name, $name);
            }

            if ($reflection instanceof \ReflectionMethod) {
                $method = $this->fetchFunctionAnnotation($reflection, $annotationClass);

                if (!empty($method)) {
                    $methods[] = $method;
                    ++$classRefCount;
                }

                continue;
            }

            if (empty($annotations = $this->getAnnotations($reflection, $annotationClass))) {
                continue;
            }

            if ($reflection instanceof \ReflectionProperty) {
                $properties[] = ['attributes' => $annotations, 'type' => $reflection];
                ++$classRefCount;
            } elseif ($reflection instanceof \ReflectionClassConstant) {
                $constants[] = ['attributes' => $annotations, 'type' => $reflection];
                ++$classRefCount;
            }
        }

        if (empty($class = $this->getAnnotations($classReflection, $annotationClass)) && 0 === $classRefCount) {
            return [];
        }

        return ['attributes' => $class] + \compact('constants', 'properties', 'methods') + ['type' => $classReflection];
    }

    /**
     * @return array<string,mixed>
     */
    private function fetchFunctionAnnotation(\ReflectionFunctionAbstract $reflection, array $annotationClass)
    {
        $parameters = [];
        $annotations = $this->getAnnotations($reflection, $annotationClass);

        if (empty($annotations)) {
            return [];
        }

        foreach ($reflection->getParameters() as $parameter) {
            $attributes = $this->getAnnotations($parameter, $annotationClass);

            if (!empty($attributes)) {
                $parameters[] = ['attributes' => $attributes, 'type' => $parameter];
            }
        }

        return ['attributes' => $annotations, 'parameters' => $parameters, 'type' => $reflection];
    }

    /**
     * Finds classes in the given resource directory.
     *
     * @param \Traversable<int,string> $files
     *
     * @return array>int,string>
     */
    private static function findClasses(\Traversable $files): array
    {
        $declared = \get_declared_classes();
        $classes = [];

        foreach ($files as $file) {
            require_once $file;
        }

        return \array_merge($classes, \array_diff(\get_declared_classes(), $declared));
    }
}
