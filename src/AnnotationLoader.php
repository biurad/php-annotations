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

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RegexIterator;

class AnnotationLoader implements LoaderInterface
{
    /** @var null|AnnotationReader */
    private $annotation;

    /** @var ListenerInterface[] */
    private $listeners;

    /** @var string[] */
    private $resources = [];

    /**
     * @param null|AnnotationReader $reader
     */
    public function __construct(?AnnotationReader $reader = null)
    {
        $this->annotation = $reader;

        if (null === $reader && \interface_exists(AnnotationReader::class)) {
            $this->annotation = new SimpleAnnotationReader();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attachListener(ListenerInterface ...$listeners): void
    {
        foreach ($listeners as $listener) {
            if ($this->annotation instanceof SimpleAnnotationReader) {
                $this->annotation->addNamespace($listener->getAnnotation());
            }

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
            foreach ($this->listeners as $listener) {
                if (\class_exists($resource) || \is_dir($resource)) {
                    $annotations += $this->findAnnotations($resource, $listener);

                    continue;
                }

                if (!\file_exists($resource) || \is_dir($resource)) {
                    continue;
                }

                (function () use ($resource): void {
                    require $resource;
                })->call($listener->getBinding());
            }
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
    private function findAnnotations(string $resource, ListenerInterface $listener): array
    {
        $classes = $annotations = [];

        if (\is_dir($resource)) {
            $classes = \array_merge($this->findClasses($resource), $classes);
        } elseif (\class_exists($resource)) {
            $classes[] = $resource;
        }

        foreach ($classes as $class) {
            $classReflection = new ReflectionClass($class);
            $className       = $classReflection->getName();

            if ($classReflection->isAbstract()) {
                throw new InvalidAnnotationException(\sprintf(
                    'Annotations from class "%s" cannot be read as it is abstract.',
                    $classReflection->getName()
                ));
            }

            foreach ($this->getAnnotations($classReflection, $listener) as $annotation) {
                $annotations[$className]['class'] = $annotation;
            }

            $reflections = \array_merge($classReflection->getMethods(), $classReflection->getProperties());

            foreach ($reflections as $reflection) {
                if ($reflection instanceof ReflectionMethod && $reflection->isAbstract()) {
                    continue;
                }

                foreach ($this->getAnnotations($reflection, $listener) as $annotation) {
                    if ($reflection instanceof ReflectionMethod) {
                        $annotations[$className]['method'][] = [$reflection, $annotation];

                        continue;
                    }

                    $annotations[$className]['property'][] = [$reflection, $annotation];
                }
            }
        }

        \gc_mem_caches();

        return $annotations;
    }

    /**
     * @param ReflectionClass|ReflectionMethod|ReflectionProperty $reflection
     *
     * @return iterable<object>
     */
    private function getAnnotations(Reflector $reflection, ListenerInterface $listener): iterable
    {
        $annotationClass = $listener->getAnnotation();
        $annotations     = [];

        if (\PHP_VERSION_ID >= 80000) {
            foreach ($reflection->getAttributes($annotationClass) as $attribute) {
                yield $attribute->newInstance();
            }
        }

        if (null === $this->annotation) {
            return;
        }

        switch (true) {
            case $reflection instanceof ReflectionClass:
                $annotations = $this->annotation->getClassAnnotations($reflection);

                break;

            case $reflection instanceof ReflectionMethod:
                $annotations = $this->annotation->getMethodAnnotations($reflection);

                break;

            case $reflection instanceof ReflectionProperty:
                $annotations = $this->annotation->getPropertyAnnotations($reflection);

                break;
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationClass) {
                yield $annotation;
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
