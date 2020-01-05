<?php

namespace Inspirum\Cache\Tests;

use Inspirum\Cache\Model\Values\TagSet;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class AbstractTestCase extends PHPUnitTestCase
{
    /**
     * Setup the test environment, before each test.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        TagSet::resetCachedTags();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }
}
