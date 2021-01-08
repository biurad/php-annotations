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
     * The array that is received contains eg:
     *  ```php
     * $annotations = [
     *     ClassName::class => [
     *        'class' => [$annotation, ...],
     *        'methods' => [[$reflection, $annotation], ..],
     *        'property' => [[$reflection, $annotation], ..],
     *        'constant' => [[$reflection, $annotation], ..],
     *        'method_propert' => [[$reflection, $annotation], ..],
     *    ],
     *    ...
     * ];
     * ```
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
     * This methods gets the annotation from a function or method's
     * paramater.
     *
     * @return string[]
     */
    public function getArguments(): array;
}
