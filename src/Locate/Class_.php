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

class Class_ extends Annotation
{
    /** @var Constant[] */
    public $constants = [];

    /** @var Method[] */
    public $methods = [];

    /** @var Property[] */
    public $properties = [];

    public function __construct(iterable $annotations, \ReflectionClass $reflection)
    {
        $this->reflection = $reflection;
        parent::__construct($annotations);
    }

    /**
     * {@inheritdoc}
     */
    public function getReflection(): \ReflectionClass
    {
        return $this->reflection;
    }
}
