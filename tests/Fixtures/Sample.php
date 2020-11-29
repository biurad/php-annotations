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

use InvalidArgumentException;

/**
 *  Annotation class for @Listener().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS_CONSTANT | \Attribute::TARGET_PARAMETER)]
final class Sample
{
    /** @var string */
    private $name;

    /** @var int */
    private $priority;

    /**
     * @param @param array<string,mixed>|string $data
     * @param string                            $name
     * @param int                               $priority
     */
    public function __construct($data = null, string $name = null, int $priority = 0)
    {
        if (\is_array($data) && isset($data['value'])) {
            $data['name'] = $data['value'];
            unset($data['value']);
        } elseif (\is_string($data)) {
            $data = ['name' => $data];
        }

        $this->name     = $data['name'] ?? $name;
        $this->priority = $data['priority'] ?? $priority;

        if (empty($this->name) || !\is_string($this->name)) {
            throw new InvalidArgumentException(\sprintf(
                '@Sample.name must %s.',
                empty($this->event) ? 'be not an empty string' : 'contain only a string'
            ));
        }

        if (!\is_integer($this->priority)) {
            throw new InvalidArgumentException('@Sample.priority must contain only an integer');
        }
    }

    /**
     * Get the priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
