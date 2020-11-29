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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use RegexIterator;
use Spiral\Attributes\ReaderInterface;

class AnnotationLoader implements LoaderInterface
{
    /** @var ReaderInterface */
    private $annotation;

    /** @var ListenerInterface[] */
    private $listeners = [];

    /** @var string[] */
    private $resources = [];

    /**
     * @param ReaderInterface $reader
     */
    public function __construct(ReaderInterface $reader)
    {
        $this->annotation = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function attachListener(ListenerInterface ...$listeners): void
    {
        foreach ($listeners as $listener) {
            $this->listeners[] = $listener;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attach(string ...$resources): void
    {
        foreach ($resources as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(): iterable
    {
        $annotations = [];

        foreach ($this->resources as $resource) {
            if (\class_exists($resource) || \is_dir($resource)) {
                $annotations += $this->findAnnotations($resource);

                continue;
            }

            //TODO: Read annotations from functions ...
        }

        foreach ($this->listeners as $listener) {
            if (null !== $found = $listener->onAnnotation($annotations)) {
                yield $found;
            }
        }
    }

    /**
     * Finds annotations in the given resource
     *
     * @param string $resource
     *
     * @return array<string,array<string,mixed>>
     */
    private function findAnnotations(string $resource): array
    {
        $classes = $annotations = [];

        if (\is_dir($resource)) {
            $classes += $this->findClasses($resource);
        } elseif (\class_exists($resource)) {
            $classes[] = $resource;
        }

        /** @var class-string $class */
        foreach ($classes as $class) {
            $classReflection = new ReflectionClass($class);
            $className       = $classReflection->getName();

            if ($classReflection->isAbstract()) {
                throw new InvalidAnnotationException(\sprintf(
                    'Annotations from class "%s" cannot be read as it is abstract.',
                    $classReflection->getName()
                ));
            }

            foreach ($this->getAnnotations($classReflection) as $annotation) {
                $annotations[$className]['class'] = $annotation;
            }

            // Reflections belonging to class object.
            $reflections = \array_merge(
                $classReflection->getMethods(),
                $classReflection->getProperties(),
                $classReflection->getConstants()
            );

            $this->fetchAnnotations($className, $reflections, $annotations);
        }

        \gc_mem_caches();

        return $annotations;
    }

    /**
     * @param Reflector $reflection
     *
     * @return iterable<object>
     */
    private function getAnnotations(Reflector $reflection): iterable
    {
        $annotations = [];

        switch (true) {
            case $reflection instanceof ReflectionClass:
                $annotations = $this->annotation->getClassMetadata($reflection);

                break;

            case $reflection instanceof ReflectionMethod:
                $annotations = $this->annotation->getFunctionMetadata($reflection);

                break;

            case $reflection instanceof ReflectionProperty:
                $annotations = $this->annotation->getPropertyMetadata($reflection);

                break;

            case $reflection instanceof ReflectionClassConstant:
                $annotations = $this->annotation->getConstantMetadata($reflection);

                break;

            case $reflection instanceof ReflectionParameter:
                $annotations = $this->annotation->getParameterMetadata($reflection);
        }

        foreach ($annotations as $annotation) {
            foreach ($this->listeners as $listener) {
                $annotationClass = $listener->getAnnotation();

                if ($annotation instanceof $annotationClass) {
                    yield $annotation;
                }
            }
        }
    }

    /**
     * @param ReflectionParameter[] $parameters
     *
     * @return iterable<int,object[]>
     */
    private function getMethodParameter(array $parameters): iterable
    {
        foreach ($this->listeners as $listener) {
            foreach ($parameters as $parameter) {
                if (\in_array($parameter->getName(), $listener->getArguments(), true)) {
                    foreach ($this->getAnnotations($parameter) as $annotation) {
                        yield [$parameter, $annotation];
                    }
                }
            }
        }
    }

    /**
     * Fetch annotations from methods, constant, property and methods parameter
     *
     * @param string                            $className
     * @param Reflector[]                       $reflections
     * @param array<string,array<string,mixed>> $annotations
     */
    private function fetchAnnotations(string $className, array $reflections, array &$annotations): void
    {    
        foreach ($reflections as $name => $reflection) {
            if ($reflection instanceof ReflectionMethod && $reflection->isAbstract()) {
                continue;
            }

            if (is_string($name)) {
                $reflection = new ReflectionClassConstant($className, $name);
            }

            foreach ($this->getAnnotations($reflection) as $annotation) {
                if ($reflection instanceof ReflectionMethod) {
                    $annotations[$className]['method'][] = [$reflection, $annotation];

                    foreach ($this->getMethodParameter($reflection->getParameters()) as $parameter) {
                        $annotations[$className]['method_property'][] = $parameter;
                    }

                    continue;
                }

                if ($reflection instanceof ReflectionClassConstant) {
                    $annotations[$className]['constant'][] = [$reflection, $annotation];

                    continue;
                }

                $annotations[$className]['property'][] = [$reflection, $annotation];
            }
        }
    }

    /**
     * Finds classes in the given resource directory
     *
     * @param string $resource
     *
     * @return string[]
     */
    private function findClasses(string $resource): array
    {
        $files    = $this->findFiles($resource);
        $declared = \get_declared_classes();

        foreach ($files as $file) {
            require_once $file;
        }

        return \array_diff(\get_declared_classes(), $declared);
    }

    /**
     * Finds files in the given resource
     *
     * @param string $resource
     *
     * @return string[]
     */
    private function findFiles(string $resource): array
    {
        $flags = FilesystemIterator::CURRENT_AS_PATHNAME;

        $directory = new RecursiveDirectoryIterator($resource, $flags);
        $iterator  = new RecursiveIteratorIterator($directory);
        $files     = new RegexIterator($iterator, '/\.php$/');

        return \iterator_to_array($files);
    }
}
