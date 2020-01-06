<?php

namespace Inspirum\Mcrouter\Tests\Unit;

use Illuminate\Support\Carbon;
use Inspirum\Mcrouter\Model\Values\Mcrouter;
use Inspirum\Mcrouter\Services\MemcachedStore;
use Inspirum\Mcrouter\Tests\AbstractTestCase;
use Memcached;

class CacheMemcachedStoreTest extends AbstractTestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get', 'getResultCode'])->getMock();
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
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get', 'getResultCode'])->getMock();
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
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['getMulti', 'getResultCode'])->getMock();
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

    public function testMemcacheGetMultiValuesAreReturnedWithMissingKeys()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['getMulti', 'getResultCode'])->getMock();
        $memcache->expects($this->once())
                 ->method('getMulti')
                 ->with(['foo:foo', 'foo:bar', 'foo:baz']);
        $memcache->expects($this->once())
                 ->method('getResultCode')
                 ->will($this->returnValue(1));

        $store = new MemcachedStore($memcache, 'foo');

        $this->assertEquals(
            ['foo' => null, 'bar' => null, 'baz' => null],
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

    public function testPutManyMethodProperlyCallsMemcache()
    {
        Carbon::setTestNow($now = Carbon::now());

        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['setMulti'])->getMock();
        $memcache->expects($this->once())
                 ->method('setMulti')
                 ->with(['foo' => 'fizz', 'bar' => 'buzz', 'baz' => 'norf'])
                 ->willReturn(true);

        $store = new MemcachedStore($memcache);

        $result = $store->putMany(['foo' => 'fizz', 'bar' => 'buzz', 'baz' => 'norf'], 60);

        $this->assertTrue($result);

        Carbon::setTestNow();
    }

    public function testAddMethodProperlyCallsMemcache()
    {
        Carbon::setTestNow($now = Carbon::now());

        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['add'])->getMock();
        $memcache->expects($this->once())
                 ->method('add')
                 ->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo($now->timestamp + 60))
                 ->willReturn(true);

        $store = new MemcachedStore($memcache);

        $result = $store->add('foo', 'bar', 60);

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

    public function testNoMcrouterConfigNeeded()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get'])->getMock();
        $memcache->expects($this->once())
                 ->method('get')
                 ->with($this->equalTo('foo:/default/shr/bar'))
                 ->willReturn(null);

        $store = new MemcachedStore($memcache, 'foo');

        $this->assertNull($store->get('/default/shr/bar'));
    }

    public function testWrongSharedPrefixIsPrefixed()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get'])->getMock();
        $memcache->expects($this->once())
                 ->method('get')
                 ->with($this->equalTo('foo:/default/shr/bar'))
                 ->willReturn(null);

        $store = new MemcachedStore($memcache, 'foo', new Mcrouter('/default/local/'));

        $this->assertNull($store->get('/default/shr/bar'));
    }

    public function testSharedPrefixIsNotPrefixed()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get'])->getMock();
        $memcache->expects($this->once())
                 ->method('get')
                 ->with($this->equalTo('/default/shr/foo:bar'))
                 ->willReturn(null);

        $store = new MemcachedStore($memcache, 'foo', new Mcrouter('/default/shr/'));

        $this->assertNull($store->get('/default/shr/bar'));
    }

    public function testAdditionalPrefixesIsNotPrefixed()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get'])->getMock();
        $memcache->expects($this->exactly(4))
                 ->method('get')
                 ->withConsecutive(
                     [$this->equalTo('/default/shr/foo:bar')],
                     [$this->equalTo('/default/a/foo:bar')],
                     [$this->equalTo('/default/b/foo:bar')],
                     [$this->equalTo('foo:/default/c/bar')]
                 )
                 ->willReturn(null);

        $store = new MemcachedStore($memcache, 'foo', new Mcrouter('/default/shr/', ['/default/a/', '/default/b/']));

        $this->assertNull($store->get('/default/shr/bar'));
        $this->assertNull($store->get('/default/a/bar'));
        $this->assertNull($store->get('/default/b/bar'));
        $this->assertNull($store->get('/default/c/bar'));
    }
}
