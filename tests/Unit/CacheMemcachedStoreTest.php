<?php

namespace Inspirum\Cache\Tests\Unit;

use Illuminate\Support\Carbon;
use Inspirum\Cache\Services\MemcachedStore;
use Inspirum\Project\Tests\AbstractTestCase;
use Memcached;
use stdClass;

class CacheMemcachedStoreTest extends AbstractTestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $memcache = $this->getMockBuilder(stdClass::class)->setMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())
                 ->method('get')
                 ->with($this->equalTo('foo:bar'))
                 ->will($this->returnValue(null));
        $memcache->expects($this->once())
                 ->method('getResultCode')
                 ->will($this->returnValue(1));

        $store = new MemcachedStore($memcache, 'foo');

        $this->assertNull($store->get('bar'));
    }

    public function testMemcacheValueIsReturned()
    {
        $memcache = $this->getMockBuilder(stdClass::class)->setMethods(['get', 'getResultCode'])->getMock();
        $memcache->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue('bar'));
        $memcache->expects($this->once())
                 ->method('getResultCode')
                 ->will($this->returnValue(0));

        $store = new MemcachedStore($memcache);

        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testMemcacheGetMultiValuesAreReturnedWithCorrectKeys()
    {
        $memcache = $this->getMockBuilder(stdClass::class)->setMethods(['getMulti', 'getResultCode'])->getMock();
        $memcache->expects($this->once())
                 ->method('getMulti')
                 ->with(['foo:foo', 'foo:bar', 'foo:baz'])
                 ->will($this->returnValue(['fizz', 'buzz', 'norf']));
        $memcache->expects($this->once())
                 ->method('getResultCode')
                 ->will($this->returnValue(0));

        $store = new MemcachedStore($memcache, 'foo');

        $this->assertEquals(
            ['foo' => 'fizz', 'bar' => 'buzz', 'baz' => 'norf'],
            $store->many(['foo', 'bar', 'baz',])
        );
    }

    public function testSetMethodProperlyCallsMemcache()
    {
        Carbon::setTestNow($now = Carbon::now());

        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['set'])->getMock();
        $memcache->expects($this->once())
                 ->method('set')
                 ->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo($now->timestamp + 60))
                 ->willReturn(true);

        $store = new MemcachedStore($memcache);

        $result = $store->put('foo', 'bar', 60);

        $this->assertTrue($result);

        Carbon::setTestNow();
    }

    public function testIncrementMethodProperlyCallsMemcache()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['increment'])->getMock();
        $memcache->expects($this->once())
                 ->method('increment')
                 ->with($this->equalTo('foo'), $this->equalTo(5));

        $store = new MemcachedStore($memcache);

        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsMemcache()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['decrement'])->getMock();
        $memcache->expects($this->once())
                 ->method('decrement')
                 ->with($this->equalTo('foo'), $this->equalTo(5));

        $store = new MemcachedStore($memcache);

        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsMemcached()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['set'])->getMock();
        $memcache->expects($this->once())
                 ->method('set')
                 ->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0))
                 ->willReturn(true);

        $store = new MemcachedStore($memcache);

        $result = $store->forever('foo', 'bar');

        $this->assertTrue($result);
    }

    public function testForgetMethodProperlyCallsMemcache()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['delete'])->getMock();
        $memcache->expects($this->once())
                 ->method('delete')
                 ->with($this->equalTo('foo'));

        $store = new MemcachedStore($memcache);

        $store->forget('foo');
    }

    public function testFlushesCached()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['flush'])->getMock();
        $memcache->expects($this->once())
                 ->method('flush')
                 ->willReturn(true);

        $store = new MemcachedStore($memcache);

        $result = $store->flush();

        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        $store = new MemcachedStore(new Memcached(), 'bar');

        $this->assertEquals('bar:', $store->getPrefix());

        $store->setPrefix('foo');

        $this->assertEquals('foo:', $store->getPrefix());

        $store->setPrefix(null);

        $this->assertEmpty($store->getPrefix());
    }
}