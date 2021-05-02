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

interface LoaderInterface
{
    /**
     * Attache(s) the given resource(s) to the loader
     *
     * @param string ...$resources type of class string, file, or directory
     */
    public function resource(string ...$resources): void;

    /**
     * Attache(s) the given listener(s) to the loader
     *
     * @param ListenerInterface ...$listeners
     */
    public function listener(ListenerInterface ...$listeners): void;

    /**
     * Loads annotations from attached resources
     *
     * @return iterable|mixed[]
     */
    public function load(): iterable;

    /**
     * This finds and build annotations once
     */
    public function build(): void;
}
