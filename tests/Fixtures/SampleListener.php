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
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

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
            $reflections = \array_merge($collection['method'] ?? [], $collection['property'] ?? []);

            if (!empty($reflections)) {
                $this->addSampleGroup($collection['class'] ?? null, $reflections);

                continue;
            }

            $this->addSample($collection['class'], $class);
        }

        return $this->collector;
    }

    /**
     * @param Sample           $annotation
     * @param Reflector|string $handler
     * @param null|Sample      $group
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
     * @param null|Sample $group
     * @param mixed[]     $collection
     */
    private function addSampleGroup(?Sample $group, array $collection): void
    {
        /**
         * @var ReflectionMethod|ReflectionProperty $reflector
         * @var Sample                              $annotation
         */
        foreach ($collection as [$reflector, $annotation]) {
            $this->addSample($annotation, $reflector, $group);
        }
    }
}
