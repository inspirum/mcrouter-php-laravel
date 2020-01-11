<?php

namespace Inspirum\Mcrouter\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Inspirum\Mcrouter\Model\Values\Mcrouter;
use Inspirum\Mcrouter\Services\MemcachedSessionHandler;
use Inspirum\Mcrouter\Tests\AbstractTestCase;

class MemcachedSessionHandlerTest extends AbstractTestCase
{
    public function testReadReturnsWrittenData()
    {
        $session = new MemcachedSessionHandler(new Repository(new ArrayStore()), 60);

        $this->assertEmpty($session->read('UID1234'));
        $session->write('UID1234', '789456');
        $this->assertEquals('789456', $session->read('UID1234'));
    }

    public function testWrittenDataCanBeDeleted()
    {
        $session = new MemcachedSessionHandler(new Repository(new ArrayStore()), 60);

        $session->write('UID1234', '789456');
        $this->assertEquals('789456', $session->read('UID1234'));
        $session->destroy('UID1234');
        $this->assertEmpty($session->read('UID1234'));
    }

    public function testReadMethodDonwUseSharedPrefixWithoutConfiguration()
    {
        $cache = $this->getMockBuilder(Repository::class)
                      ->disableOriginalConstructor()
                      ->setMethods(['get'])
                      ->getMock();

        $cache->expects($this->once())
              ->method('get')
              ->with($this->equalTo('UID1234'))
              ->will($this->returnValue(null));

        $session = new MemcachedSessionHandler($cache, 60);

        $this->assertEmpty($session->read('UID1234'));
    }

    public function testReadMethodUseSharedPrefix()
    {
        $cache = $this->getMockBuilder(Repository::class)
                      ->disableOriginalConstructor()
                      ->setMethods(['get'])
                      ->getMock();

        $cache->expects($this->once())
              ->method('get')
              ->with($this->equalTo('/default/a/UID1234'))
              ->will($this->returnValue(null));

        $session = new MemcachedSessionHandler($cache, 60, new Mcrouter('/default/a/'));

        $this->assertEmpty($session->read('UID1234'));
    }

    public function testWriteMethodUseSharedPrefix()
    {
        $cache = $this->getMockBuilder(Repository::class)
                      ->disableOriginalConstructor()
                      ->setMethods(['put'])
                      ->getMock();

        $cache->expects($this->once())
              ->method('put')
              ->with($this->equalTo('/default/b/UID1234'), $this->equalTo('789456'), $this->equalTo(60 * 60))
              ->will($this->returnValue(null));

        $session = new MemcachedSessionHandler($cache, 60, new Mcrouter('/default/b/'));

        $this->assertEmpty($session->write('UID1234', '789456'));
    }

    public function testDestroyMethodUseSharedPrefix()
    {
        $cache = $this->getMockBuilder(Repository::class)
                      ->disableOriginalConstructor()
                      ->setMethods(['forget'])
                      ->getMock();

        $cache->expects($this->once())
              ->method('forget')
              ->with($this->equalTo('/default/c/UID1234'))
              ->will($this->returnValue(null));

        $session = new MemcachedSessionHandler($cache, 60, new Mcrouter('/default/c/'));

        $this->assertEmpty($session->destroy('UID1234'));
    }
}
