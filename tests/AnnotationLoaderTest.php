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
     * @dataProvider provideAnnotationLoader
     * @runInSeparateProcess
     */
    public function testAttach($loader): void
    {
        $annotation = new AnnotationLoader(new AnnotationReader(), $loader);
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
            ['handler' => Fixtures\Annotation\Valid\SingleClass::class, 'priority' => 0],
        ], $result);
    }

    /**
     * @dataProvider provideAnnotationLoader
     * @runInSeparateProcess
     */
    public function testAttachAttribute($loader): void
    {
        $annotation = new AnnotationLoader(new AttributeReader(), $loader);
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
            'attribute_specific_name'   => ['handler' => \ReflectionMethod::class, 'priority' => 0],
            'attribute_specific_none'   => ['handler' => \ReflectionMethod::class, 'priority' => 14],
            'attribute_property'        => ['handler' => \ReflectionProperty::class, 'priority' => 0],
            'attribute_constant'        => ['handler' => \ReflectionClassConstant::class, 'priority' => 0],
            'attribute_method_property' => ['handler' => \ReflectionParameter::class, 'priority' => 4],
            'attribute_added_specific_name'   => ['handler' => \ReflectionMethod::class, 'priority' => 0],
            'attribute_added_specific_none'   => ['handler' => \ReflectionMethod::class, 'priority' => 14],
            'attribute_added_property'        => ['handler' => \ReflectionProperty::class, 'priority' => 0],
            'attribute_added_constant'        => ['handler' => \ReflectionClassConstant::class, 'priority' => 0],
            'attribute_added_method_property' => ['handler' => \ReflectionParameter::class, 'priority' => 4],
        ], $result);
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
        $ast = $parser->parse(file_get_contents($file));
        $traverser = new NodeTraverser();

        $traverser->addVisitor($class = new class () extends NodeVisitorAbstract {
            private $className = null;

            public function enterNode(Node $node)
            {
                if ($node instanceof Namespace_) {
                    // Clean out the function body
                    $this->className = join('\\', $node->name->parts) . '\\';
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
        $tokens = token_get_all(file_get_contents($file));

        if (1 === \count($tokens) && \T_INLINE_HTML === $tokens[0][0]) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain PHP code. Did you forgot to add the "<?php" start tag at the beginning of the file?', $file));
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
                    } elseif (!\in_array($tokens[$j][0], [\T_WHITESPACE, \T_DOC_COMMENT, \T_COMMENT])) {
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
