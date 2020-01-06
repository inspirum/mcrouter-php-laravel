<?php

namespace Inspirum\Mcrouter\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Inspirum\Mcrouter\Model\Values\Mcrouter;
use Inspirum\Mcrouter\Model\Values\TagSet;
use Inspirum\Mcrouter\Services\MemcachedStore;
use Inspirum\Mcrouter\Tests\AbstractTestCase;
use Memcached;

class CacheTaggedCacheTest extends AbstractTestCase
{
    public function testTagKeyHasSharedPrefix()
    {
        $tagSet = new TagSet(new ArrayStore('foo'), [], new Mcrouter('/default/shr/'));

        $this->assertEquals('/default/shr/tag:bar:key', $tagSet->tagKey('bar'));
        $this->assertEquals('/default/shr/tag:foo:key', $tagSet->tagKey('foo'));
    }

    public function testTagsHasStaticCache()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get', 'set', 'getResultCode'])->getMock();
        $memcache->expects($this->exactly(2))
                 ->method('get')
                 ->will($this->returnValue(null));
        $memcache->expects($this->exactly(2))
                 ->method('set')
                 ->will($this->returnValue(true));

        $store = new MemcachedStore($memcache, 'foo');

        $tags  = ['bop', 'zap'];

        $namespace = $store->tags($tags)->getTags()->getNamespace();
        $this->assertEquals($namespace, $store->tags($tags)->getTags()->getNamespace());
        $this->assertEquals($namespace, $store->tags($tags)->getTags()->getNamespace());
        $this->assertEquals($namespace, $store->tags($tags)->getTags()->getNamespace());

        $this->assertEqualsCanonicalizing($tags, array_keys(TagSet::getCachedTags()));
    }

    public function testStaticCacheCanBeReset()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get', 'set', 'getResultCode'])->getMock();
        $memcache->expects($this->exactly(3))
                 ->method('get')
                 ->will($this->returnValue(null));
        $memcache->expects($this->exactly(4))
                 ->method('set')
                 ->will($this->returnValue(true));

        $store = new MemcachedStore($memcache, 'foo');
        $tags  = ['bop', 'zap'];

        $namespace = $store->tags($tags)->getTags()->getNamespace();
        $this->assertEquals($namespace, $store->tags($tags)->getTags()->getNamespace());
        $this->assertEqualsCanonicalizing($tags, array_keys(TagSet::getCachedTags()));

        $store->tags($tags)->getTags()->resetTag('bop');
        $this->assertEqualsCanonicalizing(['zap'], array_keys(TagSet::getCachedTags()));
        $namespace1 = $store->tags($tags)->getTags()->getNamespace();
        $this->assertNotEquals($namespace, $namespace1);
        $this->assertEquals($namespace1, $store->tags($tags)->getTags()->getNamespace());
        $this->assertEqualsCanonicalizing($tags, array_keys(TagSet::getCachedTags()));
    }

    public function testStaticCacheCanBeFlushed()
    {
        $memcache = $this->getMockBuilder(Memcached::class)->setMethods(['get', 'set', 'getResultCode'])->getMock();
        $memcache->expects($this->exactly(4))
                 ->method('get')
                 ->will($this->returnValue(null));
        $memcache->expects($this->exactly(6))
                 ->method('set')
                 ->will($this->returnValue(true));

        $store = new MemcachedStore($memcache, 'foo');
        $tags  = ['bop', 'zap'];

        $namespace = $store->tags($tags)->getTags()->getNamespace();
        $this->assertEquals($namespace, $store->tags($tags)->getTags()->getNamespace());
        $this->assertEqualsCanonicalizing($tags, array_keys(TagSet::getCachedTags()));

        $store->tags($tags)->flush();
        $this->assertEqualsCanonicalizing([], array_keys(TagSet::getCachedTags()));
        $namespace1 = $store->tags($tags)->getTags()->getNamespace();
        $this->assertNotEquals($namespace, $namespace1);
        $this->assertEquals($namespace1, $store->tags($tags)->getTags()->getNamespace());
    }
}
