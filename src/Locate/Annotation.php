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

namespace Biurad\Annotations\Locate;

/**
 * A representation of annotated reflection type.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class Annotation implements \Stringable
{
    /** @var iterable<object> */
    private $annotations = [];

    /** @var \ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionFunctionAbstract|\ReflectionParameter */
    protected $reflection;

    /**
     * @param iterable<object> $annotations
     */
    public function __construct(iterable $annotations)
    {
        $this->annotations = $annotations;
    }

    /**
     * Get the reflection object used in annotation.
     */
    abstract public function getReflection();

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->reflection->getName();
    }

    /**
     * @return iterable<object>
     */
    public function getAnnotation(): iterable
    {
        return $this->annotations;
    }
}
