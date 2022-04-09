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

namespace Biurad\Annotations\Tests\Benchmark;

use Biurad\Annotations\AnnotationLoader;
use Biurad\Annotations\Tests\Fixtures\SampleListener;
use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;
use Spiral\Attributes\Composite\SelectiveReader;
use Spiral\Attributes\Internal\FallbackAttributeReader;

/**
 * @Iterations(5)
 * @Revs(500)
 */
class PerformanceBench
{
    protected const DIRECTORIES = [
        __DIR__ . '/../Fixtures/Annotation/Valid',
        __DIR__ . '/../Fixtures/Annotation/Attribute',
        'Biurad\Annotations\Tests\Fixtures\Valid\annotated_function',
        'SampleFunc',
    ];

    /** @var AnnotationLoader */
    private $annotation;

    public function __construct()
    {
        DoctrineAnnotationReader::addGlobalIgnoredName('BeforeMethods');
        DoctrineAnnotationReader::addGlobalIgnoredName('Iterations');
        DoctrineAnnotationReader::addGlobalIgnoredName('Revs');
    }

    public function createSelectiveLoaderInstance(): void
    {
        $annotation = new AnnotationLoader(new SelectiveReader([new AttributeReader(), new AnnotationReader()]));

        $annotation->listener(new SampleListener());
        $annotation->resource(...self::DIRECTORIES);

        $this->annotation = $annotation;
    }

    public function createMergedLoaderInstance(): void
    {
        $annotation = new AnnotationLoader(new MergeReader([new AttributeReader(), new AnnotationReader()]));

        $annotation->listener(new SampleListener());
        $annotation->resource(...self::DIRECTORIES);

        $this->annotation = $annotation;
    }

    public function createDoctrineLoaderInstance(): void
    {
        $annotation = new AnnotationLoader(new AnnotationReader());

        $annotation->listener(new SampleListener());
        $annotation->resource(...self::DIRECTORIES);

        $this->annotation = $annotation;
    }

    public function createAttributeLoaderInstance(): void
    {
        $annotation = new AnnotationLoader(80000 <= \PHP_VERSION_ID ? null : new FallbackAttributeReader());

        $annotation->listener(new SampleListener());
        $annotation->resource(...self::DIRECTORIES);

        $this->annotation = $annotation;
    }

    /**
     * @BeforeMethods({"createSelectiveLoaderInstance"})
     */
    public function benchOptimisedSelectiveLoad(): void
    {
        $this->annotation->load();
    }

    /**
     * @BeforeMethods({"createMergedLoaderInstance"})
     */
    public function benchOptimisedMergedLoad(): void
    {
        $this->annotation->load();
    }

    /**
     * @BeforeMethods({"createDoctrineLoaderInstance"})
     */
    public function benchOptimisedDoctrineLoad(): void
    {
        $this->annotation->load();
    }

    /**
     * @BeforeMethods({"createAttributeLoaderInstance"})
     */
    public function benchOptimisedAttributeLoad(): void
    {
        $this->annotation->load();
    }

    public function benchSelectiveLoad(): void
    {
        $this->createSelectiveLoaderInstance();
        $this->annotation->load();
    }

    public function benchMergedLoad(): void
    {
        $this->createMergedLoaderInstance();
        $this->annotation->load();
    }

    public function benchDoctrineLoad(): void
    {
        $this->createDoctrineLoaderInstance();
        $this->annotation->load();
    }

    public function benchAttributeLoad(): void
    {
        $this->createAttributeLoaderInstance();
        $this->annotation->load();
    }
}
