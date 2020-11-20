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

namespace Biurad\Annotations\Tests;

use Biurad\Annotations\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\NativeAttributeReader;

/**
 * AnnotationLoaderTest
 */
class AnnotationLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        // doctrine/annotations ^1.0 compatibility.
        if (\method_exists(AnnotationRegistry::class, 'registerLoader')) {
            AnnotationRegistry::registerLoader('\\class_exists');
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testAttach(): void
    {
        $annotation = new AnnotationLoader(new AnnotationReader());
        $result     = $names = [];

        $annotation->attachListener(new Fixtures\SampleListener());
        $annotation->attach(...[
            __DIR__ . '/Fixtures/Annotation/Valid',
            'non-existing-file.php',
        ]);

        $this->assertCount(1, $founds = \iterator_to_array($annotation->load()));

        foreach ($founds as $found) {
            $this->assertInstanceOf(Fixtures\SampleCollector::class, $found);

            foreach ($found->getCollected() as $name => $sample) {
                if (\is_object($sample['handler'])) {
                    $sample['handler'] = \get_class($sample['handler']);
                }

                $names[]  = $name;
                $result[] = $sample;
            }
        }

        $this->assertEquals([
            'default',
            'protected_property',
            'private_property',
            'priority',
            'private',
            'protected',
            'public_property',
            'global_specific_name',
            'global_specific_none',
            'global_property',
            'mtp_start',
            'mtp_end',
            'mtp_next',
        ], $names);

        $this->assertEquals([
            ['handler' => ReflectionMethod::class, 'priority' => 24],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionProperty::class, 'priority' => 4],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 323],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 14],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 1],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
        ], $result);
    }

    /**
     * @requires PHP 8
     * @runInSeparateProcess
     */
    public function testAttachAttribute(): void
    {
        $annotation = new AnnotationLoader(new NativeAttributeReader());
        $result     = [];

        $annotation->attachListener(new Fixtures\SampleListener());
        $annotation->attach(__DIR__ . '/Fixtures/Annotation/Attribute');

        $this->assertCount(1, $founds = \iterator_to_array($annotation->load()));

        foreach ($founds as $found) {
            $this->assertInstanceOf(Fixtures\SampleCollector::class, $found);

            foreach ($found->getCollected() as $name => $sample) {
                if (\is_object($sample['handler'])) {
                    $sample['handler'] = \get_class($sample['handler']);
                }

                $result[$name] = $sample;
            }
        }

        $this->assertEquals([
            'attribute_specific_name' => ['handler' => ReflectionMethod::class, 'priority' => 0],
            'attribute_specific_none' => ['handler' => ReflectionMethod::class, 'priority' => 14],
            'attribite_property'      => ['handler' => ReflectionProperty::class, 'priority' => 0],
        ], $result);
    }
}
