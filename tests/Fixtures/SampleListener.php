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

    public function __construct(SampleCollector $collector = null)
    {
        $this->collector = $collector ?? new SampleCollector();
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotations(): array
    {
        return ['Biurad\Annotations\Tests\Fixtures\Sample'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $annotations): SampleCollector
    {
        foreach ($annotations as $annotation) {
            $reflector = $annotation['type'];

            if ($reflector instanceof \ReflectionClass) {
                $attributes = \array_merge($annotation['methods'], $annotation['properties'], $annotation['constants']);

                if (!empty($attributes)) {
                    foreach ($attributes as $attribute) {
                        $this->addSample($attribute['type'], $attribute['attributes'], $annotation['attributes']);

                        if (isset($attribute['parameters'])) {
                            foreach ($attribute['parameters'] as $parameter) {
                                $this->addSample($parameter['type'], $parameter['attributes']);
                            }
                        }
                    }
                    continue;
                }
                $this->addSample($reflector, $annotation['attributes']);
            } elseif ($reflector instanceof \ReflectionFunction) {
                $this->addSample($reflector, $annotation['attributes']);

                foreach ($annotation['parameters'] as $parameter) {
                    $this->addSample($parameter['type'], $parameter['attributes']);
                }
            }
        }

        return $this->collector;
    }

    /**
     * @param array<int,Sample>|Sample $attributes
     */
    private function addSample(\Reflector $reflector, array $annotations, $attribute = []): void
    {
        if (\is_array($attribute) && !empty($attribute)) {
            foreach ($attribute as $annotation) {
                $this->addSample($reflector, $annotations, $annotation);
            }
        } else {
            foreach ($annotations as $annotated) {
                if (!$annotated instanceof Sample) {
                    continue;
                }

                $name = $annotated->getName();
                $priority = $annotated->getPriority();

                if ($attribute instanceof Sample) {
                    $name = $attribute->getName() . '_' . $name;
                    $priority += $attribute->getPriority();
                }

                $this->collector->add($name, $priority, $reflector);
            }
        }
    }
}
