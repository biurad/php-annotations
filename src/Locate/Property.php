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

class Property extends Annotation
{
    public function __construct(iterable $annotations, \ReflectionProperty $reflection)
    {
        $this->reflection = $reflection;
        parent::__construct($annotations);
    }

    /**
     * {@inheritdoc}
     */
    public function getReflection(): \ReflectionProperty
    {
        return $this->reflection;
    }
}
