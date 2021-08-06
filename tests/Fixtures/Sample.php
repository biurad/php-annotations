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

/**
 * A sample Attribute/Annotation class.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({ "ALL" })
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class Sample
{
    /** @var string */
    private $name;

    /** @var int */
    private $priority;

    /**
     * @param @param array<string,mixed>|string $data
     * @param string                            $name
     */
    public function __construct($data = [], string $name = null, int $priority = 0)
    {
        if (\is_string($data)) {
            $data = ['value' => $data];
        }

        $this->name = $data['value'] ?? $data['name'] ?? $name;
        $this->priority = $data['priority'] ?? $priority;

        if (empty($this->name) || !\is_string($this->name)) {
            throw new \InvalidArgumentException(\sprintf('@Sample.name must %s.', empty($this->name) ? 'be not an empty string' : 'contain only a string'));
        }

        if (!\is_int($this->priority)) {
            throw new \InvalidArgumentException('@Sample.priority must contain only an integer');
        }
    }

    /**
     * Get the priority.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
