<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Annotations\Tests;

use Biurad\Annotations\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;

/**
 * AnnotationLoaderTest.
 */
class AnnotationLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__.'/Fixtures/Annotation/function.php';

        // doctrine/annotations ^1.0 compatibility.
        if (\method_exists(AnnotationRegistry::class, 'registerLoader')) {
            AnnotationRegistry::registerLoader('class_exists');
        }
    }

    /**
     * @dataProvider provideAnnotationLoader
     *
     * @runInSeparateProcess
     */
    public function testAnnotationListenerWithResource(int $loader): void
    {
        $annotation = new AnnotationLoader(new AnnotationReader(), $loader);
        $result = $names = [];

        $annotation->listener(new Fixtures\SampleListener(), 'test');
        $annotation->resource(...[
            __DIR__.'/Fixtures/Annotation/Valid',
            'non-existing-file.php',
        ]);

        $this->assertCount(1, $founds = [$annotation->load()]);

        /** @var Fixtures\SampleCollector $found */
        foreach ($founds as $found) {
            $this->assertInstanceOf(Fixtures\SampleCollector::class, $found);

            $collected = $found->getCollected();
            $collected->ksort();

            foreach ($collected as $name => $sample) {
                $names[] = $name;
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
            ['handler' => \ReflectionMethod::class, 'priority' => 24],
            ['handler' => \ReflectionProperty::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 14],
            ['handler' => \ReflectionMethod::class, 'priority' => 1],
            ['handler' => \ReflectionMethod::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 24],
            ['handler' => \ReflectionProperty::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 24],
            ['handler' => \ReflectionProperty::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 24],
            ['handler' => \ReflectionProperty::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 0],
            ['handler' => \ReflectionMethod::class, 'priority' => 0],
            ['handler' => \ReflectionProperty::class, 'priority' => 4],
            ['handler' => \ReflectionMethod::class, 'priority' => 323],
            ['handler' => \ReflectionProperty::class, 'priority' => 0],
            ['handler' => \ReflectionProperty::class, 'priority' => 0],
            ['handler' => \ReflectionClass::class, 'priority' => 0],
        ], $result);

        $this->assertInstanceOf(Fixtures\SampleCollector::class, $annotation->load('test'));
    }

    /**
     * @dataProvider provideAnnotationLoader
     *
     * @runInSeparateProcess
     */
    public function testAnnotationLoaderWithAttribute(int $loader): void
    {
        $resources = [
            __DIR__.'/Fixtures/Annotation/Attribute',
            'Biurad\\Annotations\\Tests\\Fixtures\\Valid\\annotated_function',
        ];
        $annotation = new AnnotationLoader(new AttributeReader(), $loader);
        $this->assertEmpty($annotation->load(Fixtures\Sample::class));

        $annotation->listener(new Fixtures\SampleListener(), 'test');
        $annotation->resource(...$resources);

        $this->assertInstanceOf(Fixtures\SampleCollector::class, $collector = $annotation->load('test'));
        $this->assertCount(11, $collector->getCollected()->getArrayCopy());
    }

    /**
     * @requires PHP >= 8.0
     *
     * @dataProvider provideAnnotationLoader
     *
     * @runInSeparateProcess
     */
    public function testAnnotationLoaderWithNativeAttribute(int $loader): void
    {
        $resources = [
            __DIR__.'/Fixtures/Annotation/Attribute',
            'Biurad\\Annotations\\Tests\\Fixtures\\Valid\\annotated_function',
        ];
        $annotation = new AnnotationLoader(null, $loader);
        $annotation->listener(new Fixtures\SampleListener(), 'test');

        $this->assertEmpty($annotation->load(Fixtures\Sample::class));
        $annotation->resource(...$resources);

        $this->assertInstanceOf(Fixtures\SampleCollector::class, $collector = $annotation->load('test'));
        $this->assertCount(11, $collector->getCollected()->getArrayCopy());
    }

    /**
     * @dataProvider provideAnnotationLoader
     *
     * @runInSeparateProcess
     */
    public function testAnnotationLoaderWithFunction(int $loader): void
    {
        $annotation = new AnnotationLoader(new MergeReader([new AnnotationReader(), new AttributeReader()]), $loader);

        $annotation->resource('Biurad\\Annotations\\Tests\\Fixtures\\Valid\\annotated_function');
        $collector = (new Fixtures\SampleListener())->load($annotation->load(Fixtures\Sample::class));

        $collected = $collector->getCollected();
        $collected->ksort();

        $this->assertEquals(['attributed_function', 'function_property'], \array_keys((array) $collected));
    }

    /**
     * @requires PHP < 8.0
     *
     * @runInSeparateProcess
     */
    public function testSettingConstructorNullInPhp7(): void
    {
        $this->expectException(\RuntimeException::class);
        new AnnotationLoader();
    }

    public function provideAnnotationLoader(): array
    {
        return [
            'Load by File' => [AnnotationLoader::REQUIRE_FILE],
            'Load by Tokenized' => [AnnotationLoader::TOKENIZED],
        ];
    }
}
