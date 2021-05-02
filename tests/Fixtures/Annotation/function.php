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

namespace Biurad\Annotations\Tests\Fixtures\Valid;

use Biurad\Annotations\Tests\Fixtures\Sample;

/** @Sample(name="annotated_function") */
#[Sample(name: 'attributed_function')]
function annotated_function(
    #[Sample('function_property', priority: 4)]
    string $parameter
): void {
}
