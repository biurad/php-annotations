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
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;

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

        /** @var Fixtures\SampleCollector $found */
        foreach ($founds as $found) {
            $this->assertInstanceOf(Fixtures\SampleCollector::class, $found);

            $collected = $found->getCollected();
            $collected->ksort();

            foreach ($collected as $name => $sample) {
                $names[]  = $name;
                $result[] = $sample;
            }
        }

        $this->assertEquals([
            'default',
            'global_property',
            'global_specific_name',
            'global_specific_none',
            'mtp_end',
            'mtp_next',
            'mtp_start',
            'multiple_1_default',
            'multiple_1_protected_property',
            'multiple_2_default',
            'multiple_2_protected_property',
            'multiple_3_default',
            'multiple_3_protected_property',
            'priority',
            'private',
            'private_property',
            'protected',
            'protected_property',
            'public_property',
            'single',
        ], $names);

        $this->assertEquals([
            ['handler' => ReflectionMethod::class, 'priority' => 24],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 14],
            ['handler' => ReflectionMethod::class, 'priority' => 1],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 24],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 24],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 24],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionMethod::class, 'priority' => 0],
            ['handler' => ReflectionProperty::class, 'priority' => 4],
            ['handler' => ReflectionMethod::class, 'priority' => 323],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => ReflectionProperty::class, 'priority' => 0],
            ['handler' => Fixtures\Annotation\Valid\SingleClass::class, 'priority' => 0],
        ], $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAttachAttribute(): void
    {
        $annotation = new AnnotationLoader(new AttributeReader());
        $result     = [];

        $annotation->attachListener(new Fixtures\SampleListener());
        $annotation->attach(__DIR__ . '/Fixtures/Annotation/Attribute');

        $this->assertCount(1, $founds = \iterator_to_array($annotation->load()));

        /** @var Fixtures\SampleCollector $found */
        foreach ($founds as $found) {
            $this->assertInstanceOf(Fixtures\SampleCollector::class, $found);

            $collected = $found->getCollected();
            $collected->ksort();

            foreach ($collected as $name => $sample) {
                $result[$name] = $sample;
            }
        }

        $this->assertEquals([
            'attribute_specific_name'   => ['handler' => ReflectionMethod::class, 'priority' => 0],
            'attribute_specific_none'   => ['handler' => ReflectionMethod::class, 'priority' => 14],
            'attribute_property'        => ['handler' => ReflectionProperty::class, 'priority' => 0],
            'attribute_constant'        => ['handler' => ReflectionClassConstant::class, 'priority' => 0],
            'attribute_method_property' => ['handler' => ReflectionParameter::class, 'priority' => 4],
            'attribute_added_specific_name'   => ['handler' => ReflectionMethod::class, 'priority' => 0],
            'attribute_added_specific_none'   => ['handler' => ReflectionMethod::class, 'priority' => 14],
            'attribute_added_property'        => ['handler' => ReflectionProperty::class, 'priority' => 0],
            'attribute_added_constant'        => ['handler' => ReflectionClassConstant::class, 'priority' => 0],
            'attribute_added_method_property' => ['handler' => ReflectionParameter::class, 'priority' => 4],
        ], $result);
    }
}
