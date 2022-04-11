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

/**
 * A listener class to load attributes/annotations.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface ListenerInterface
{
    /**
     * This method utilises found annotations and return collector.
     *
     * @param array<int,array<string,mixed>> $annotations
     *
     * @return mixed
     */
    public function load(array $annotations);

    /**
     * The annotation/attribute classes to find.
     *
     * @return array<int,string>
     */
    public function getAnnotations(): array;
}
