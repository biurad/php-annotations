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

namespace Biurad\Annotations\Tests\Fixtures;

use Biurad\Annotations\ListenerInterface;

class SampleListener implements ListenerInterface
{
    /** @var SampleCollector */
    private $collector;

    public function __construct(?SampleCollector $collector = null)
    {
        $this->collector = $collector ?? new SampleCollector();
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return ['parameter'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotation(): string
    {
        return 'Biurad\Annotations\Tests\Fixtures\Sample';
    }

    /**
     * {@inheritdoc}
     */
    public function onAnnotation(array $annotations): SampleCollector
    {
        foreach ($annotations as $class => $collection) {
            $reflections = \array_merge(
                $collection['method'] ?? [],
                $collection['property'] ?? [],
                $collection['constant'] ?? [],
                $collection['method_property'] ?? []
            );

            if (!empty($reflections)) {
                $this->addSampleGroup($collection['class'] ?? [], $reflections);

                continue;
            }

            foreach ($collection['class'] ?? [] as $annotation) {
                $this->addSample($annotation, $class);
            }
        }

        return $this->collector;
    }

    /**
     * @param Sample            $annotation
     * @param \Reflector|string $handler
     * @param null|Sample       $group
     */
    private function addSample(Sample $annotation, $handler, ?Sample $group = null): void
    {
        $name     = $annotation->getName();
        $priority = $annotation->getPriority();

        if (null !== $group) {
            $name = $group->getName() . '_' . $name;
            $priority += $group->getPriority();
        }

        $this->collector->add($name, $priority, $handler);
    }

    /**
     * @param Sample[] $groups
     * @param mixed[]  $collection
     */
    private function addSampleGroup(array $groups, array $collection): void
    {
        $handleCollection = function (array $collection, ?Sample $group = null): void {
            /**
             * @var \ReflectionMethod|\ReflectionProperty $reflector
             * @var Sample                              $annotation
             */
            foreach ($collection as [$reflector, $annotation]) {
                $this->addSample($annotation, $reflector, $group);
            }
        };

        if (empty($groups)) {
            $handleCollection($collection);

            return;
        }

        foreach ($groups as $group) {
            $handleCollection($collection, $group);
        }
    }
}
