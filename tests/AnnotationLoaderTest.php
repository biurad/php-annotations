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
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
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
        require_once __DIR__ . '/Fixtures/Annotation/function.php';

        // doctrine/annotations ^1.0 compatibility.
        if (\method_exists(AnnotationRegistry::class, 'registerLoader')) {
            AnnotationRegistry::registerLoader('class_exists');
        }
    }

    public function testEmptyAnnotations(): void
    {
        $annotation = new AnnotationLoader(new AnnotationReader());
        $annotation->build(Fixtures\Sample::class);

        $this->assertEmpty($annotation->load());
    }

    /**
     * @dataProvider provideAnnotationLoader
     * @runInSeparateProcess
     */
    public function testAnnotationListenerWithResource($loader): void
    {
        $annotation = new AnnotationLoader(new AnnotationReader(), $loader);
        $result = $names = [];

        $annotation->listener(new Fixtures\SampleListener());
        $annotation->resource(...[
            __DIR__ . '/Fixtures/Annotation/Valid',
            'non-existing-file.php',
        ]);

        $this->assertCount(1, $founds = $annotation->load());

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

        $this->assertInstanceOf(Fixtures\SampleCollector::class, $annotation->load(Fixtures\Sample::class));
    }

    /**
     * @dataProvider provideAnnotationLoader
     * @runInSeparateProcess
     */
    public function testAnnotationLoaderWithAttribute($loader): void
    {
        $resources = [
            __DIR__ . '/Fixtures/Annotation/Attribute',
            'Biurad\\Annotations\\Tests\\Fixtures\\Valid\\annotated_function',
        ];
        $annotation1 = new AnnotationLoader(new AttributeReader(), $loader);
        $annotation2 = new AnnotationLoader(null, $loader);

        $annotation1->listener(new Fixtures\SampleListener());
        $annotation1->resource(...$resources);
        $annotation2->listener(new Fixtures\SampleListener());
        $annotation2->resource(...$resources);

        $this->assertInstanceOf(Fixtures\SampleCollector::class, $collector1 = $annotation1->load(Fixtures\Sample::class));
        $this->assertInstanceOf(Fixtures\SampleCollector::class, $collector2 = $annotation1->load(Fixtures\Sample::class));

        ($collected1 = $collector1->getCollected())->ksort();
        ($collected2 = $collector2->getCollected())->ksort();

        $this->assertEquals($collected1->getArrayCopy(), $collected2->getArrayCopy());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAnnotationLoaderWithFunction(): void
    {
        $annotation = new AnnotationLoader(new MergeReader([new AnnotationReader(), new AttributeReader()]));

        $annotation->resource('Biurad\\Annotations\\Tests\\Fixtures\\Valid\\annotated_function');
        $collector = (new Fixtures\SampleListener())->load($annotation->load(Fixtures\Sample::class));

        $collected = $collector->getCollected();
        $collected->ksort();

        $this->assertEquals(['attributed_function', 'function_property'], array_keys((array) $collected));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSettingConstructorNullInPhp7(): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped();
        }

        $this->expectException(\RuntimeException::class);
        $annotation = new AnnotationLoader();
    }

    public function provideAnnotationLoader(): array
    {
        return [
            'Default Class Loader' => [null],
            'Token Class Loader' => [[$this, 'tokenClassLoader']],
            'Node Class Loader' => [[$this, 'nodeClassLoader']],
        ];
    }

    public function tokenClassLoader(array $files): array
    {
        if (!\function_exists('token_get_all')) {
            $this->markTestSkipped('The Tokenizer extension is required for the annotation loader.');
        }
        $classes = [];

        foreach ($files as $file) {
            $classes[] = $this->findClassByToken($file);
        }

        return $classes;
    }

    public function nodeClassLoader(array $files): array
    {
        if (!\interface_exists(Node::class)) {
            $this->markTestSkipped('The PhpParser is required for the annotation loader.');
        }
        $classes = [];

        foreach ($files as $file) {
            $classes[] = $this->findClassByNode($file);
        }

        return $classes;
    }

    protected function findClassByNode(string $file)
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse(\file_get_contents($file));
        $traverser = new NodeTraverser();

        $traverser->addVisitor($class = new class () extends NodeVisitorAbstract {
            private $className = null;

            public function enterNode(Node $node): void
            {
                if ($node instanceof Namespace_) {
                    // Clean out the function body
                    $this->className = \join('\\', $node->name->parts) . '\\';
                } elseif ($node instanceof Class_) {
                    $this->className .= $node->name->name;
                }
            }

            public function getClassName()
            {
                return $this->className;
            }
        });
        $traverser->traverse($ast);

        return $class->getClassName();
    }

    protected function findClassByToken(string $file)
    {
        $class = false;
        $namespace = false;
        $tokens = \token_get_all(\file_get_contents($file));

        if (1 === \count($tokens) && \T_INLINE_HTML === $tokens[0][0]) {
            throw new \InvalidArgumentException(\sprintf('The file "%s" does not contain PHP code. Did you forgot to add the "<?php" start tag at the beginning of the file?', $file));
        }

        $nsTokens = [\T_NS_SEPARATOR => true, \T_STRING => true];

        if (\defined('T_NAME_QUALIFIED')) {
            $nsTokens[\T_NAME_QUALIFIED] = true;
        }

        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];

            if (!isset($token[1])) {
                continue;
            }

            if (true === $class && \T_STRING === $token[0]) {
                return $namespace . '\\' . $token[1];
            }

            if (true === $namespace && isset($nsTokens[$token[0]])) {
                $namespace = $token[1];

                while (isset($tokens[++$i][1], $nsTokens[$tokens[$i][0]])) {
                    $namespace .= $tokens[$i][1];
                }
                $token = $tokens[$i];
            }

            if (\T_CLASS === $token[0]) {
                // Skip usage of ::class constant and anonymous classes
                $skipClassToken = false;

                for ($j = $i - 1; $j > 0; --$j) {
                    if (!isset($tokens[$j][1])) {
                        break;
                    }

                    if (\T_DOUBLE_COLON === $tokens[$j][0] || \T_NEW === $tokens[$j][0]) {
                        $skipClassToken = true;

                        break;
                    }

                    if (!\in_array($tokens[$j][0], [\T_WHITESPACE, \T_DOC_COMMENT, \T_COMMENT])) {
                        break;
                    }
                }

                if (!$skipClassToken) {
                    $class = true;
                }
            }

            if (\T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return false;
    }
}
