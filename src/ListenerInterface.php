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

interface ListenerInterface
{
    /**
     * This method utilises found annotations and return collector.
     *
     * @param array<string,array<string,mixed>> $annotations
     *
     * @return mixed
     */
    public function onAnnotation(array $annotations);

    /**
     * The annotation class to find
     *
     * @return string
     */
    public function getAnnotation(): string;

    /**
     * Add a binding, so we can load from php files
     * which are not class type.
     *
     * @return object
     */
    public function getBinding(): object;
}
