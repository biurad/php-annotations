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
use Biurad\Annotations\Locate\Annotation;
use Biurad\Annotations\Locate\Class_;
use Biurad\Annotations\Locate\Method;

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
    public function getAnnotation(): string
    {
        return 'Biurad\Annotations\Tests\Fixtures\Sample';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $annotations): SampleCollector
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Class_) {
                $attributes = \array_merge($annotation->methods, $annotation->properties, $annotation->constants);

                if ([] === $attributes) {
                    $this->addSample(null, $annotation);

                    continue;
                }

                foreach ($attributes as $attribute) {
                    $this->addSample($annotation->getAnnotation(), $attribute);

                    if ($attribute instanceof Method) {
                        foreach ($attribute->parameters as $parameter) {
                            $this->addSample($annotation->getAnnotation(), $parameter);
                        }
                    }
                }

                continue;
            }

            $this->addSample(null, $annotation);

            foreach ($annotation->parameters as $parameter) {
                $this->addSample(null, $parameter);
            }
        }

        return $this->collector;
    }

    /**
     * @param Sample[]|Sample|null $attribute
     */
    private function addSample($attribute, Annotation $listener): void
    {
        if (\is_array($attribute) && [] !== $attribute) {
            foreach ($attribute as $annotation) {
                $this->addSample($annotation, $listener);
            }

            return;
        }

        /** @var Sample $annotated */
        foreach ($listener->getAnnotation() as $annotated) {
            $name = $annotated->getName();
            $priority = $annotated->getPriority();

            if ($attribute instanceof Sample) {
                $name = $attribute->getName() . '_' . $name;
                $priority += $attribute->getPriority();
            }

            $this->collector->add($name, $priority, $listener->getReflection());
        }
    }
}
