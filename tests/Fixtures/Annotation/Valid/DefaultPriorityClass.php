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

namespace Biurad\Annotations\Tests\Fixtures\Annotation\Valid;

use Biurad\Annotations\Tests\Fixtures\Sample;

class DefaultPriorityClass
{
    /**
     * @Sample("public_property")
     */
    public $priority = 32;

    /**
     * @Sample("priority")
     */
    public function action($default = 'value'): void
    {
    }

    /**
     * @Sample(name="private")
     */
    private function accessedPrivate(): void
    {
    }

    /**
     * @Sample(name="protected", priority=323)
     */
    private function accessedProtected(): void
    {
    }
}
