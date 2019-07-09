<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Vuryss\Cache;

use DateInterval;
use PHPUnit\Framework\TestCase;
use RedisException;
use stdClass;

class RedisCacheTest extends TestCase
{
    public function testInstantiation()
    {
        // Test with connected client
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $class = new RedisCache(null, null, null, null, $redisClient);
        $this->assertTrue($class instanceof RedisCache);

        // Test with not connected client
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'pconnect'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn(null);

        $redisClient
            ->expects($this->once())
            ->method('pconnect')
            ->willReturn(true);

        $class = new RedisCache(null, null, null, null, $redisClient);
        $this->assertTrue($class instanceof RedisCache);

        // Test with exception on ping
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'pconnect'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturnCallback(function() {
                throw new RedisException();
            });

        $redisClient
            ->expects($this->once())
            ->method('pconnect')
            ->willReturn(true);

        $class = new RedisCache(null, null, null, null, $redisClient);
        $this->assertTrue($class instanceof RedisCache);
    }

    public function testErrorOnInitialization()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'pconnect'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturnCallback(function() {
                throw new RedisException();
            });

        $redisClient
            ->expects($this->once())
            ->method('pconnect')
            ->willReturnCallback(function() {
                throw new RedisException();
            });

        $this->expectException(Exception::class);
        new RedisCache(null, null, null, null, $redisClient);
    }

    public function testError2OnInitialization()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'pconnect'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturnCallback(function() {
                throw new RedisException();
            });

        $redisClient
            ->expects($this->once())
            ->method('pconnect')
            ->willReturn(false);

        $this->expectException(Exception::class);
        new RedisCache(null, null, null, null, $redisClient);
    }

    public function testClear()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'flushDB'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->once())
            ->method('flushDB')
            ->willReturn(true);

        $cache = new RedisCache(null, null, null, null, $redisClient);
        $this->assertTrue($cache instanceof RedisCache);
        $this->assertTrue($cache->clear());
    }

    public function testGet()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'get'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['test-key'], ['expired-key'])
            ->willReturnOnConsecutiveCalls(serialize('test-value'), false);

        $cache = new RedisCache(null, null, null, null, $redisClient);
        $this->assertTrue($cache instanceof RedisCache);

        $this->assertEquals('test-value', $cache->get('test-key', 'test content'));
        $this->assertEquals('test content', $cache->get('expired-key', 'test content'));
    }

    public function testSet()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'set', 'setex', 'get'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->once())
            ->method('set')
            ->with('test-key', serialize('test-value'))
            ->willReturn(true);

        $redisClient
            ->expects($this->once())
            ->method('setex')
            ->with('test-interval', 86400, serialize('test-value-2'))
            ->willReturn(true);

        $redisClient
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['test-key'], ['test-interval'])
            ->willReturnOnConsecutiveCalls(serialize('test-value'), serialize('test-value-2'));

        $cache = new RedisCache(null, null, null, null, $redisClient);
        $this->assertTrue($cache instanceof RedisCache);

        $this->assertTrue($cache->set('test-key', 'test-value'));
        $this->assertEquals('test-value', $cache->get('test-key'));

        $this->assertTrue($cache->set('test-interval', 'test-value-2', new DateInterval('P1D')));
        $this->assertEquals('test-value-2', $cache->get('test-interval', 'default?'));
    }

    public function testDelete()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'del'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->once())
            ->method('del')
            ->with('test-key')
            ->willReturn(1);

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $this->assertTrue($cache->delete('test-key'));
    }

    public function testGetMultiple()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'mget'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->exactly(2))
            ->method('mget')
            ->withConsecutive([['key1', 'key2']], [['key1', 'key2']])
            ->willReturnOnConsecutiveCalls([false, false], [serialize('some-value'), false]);

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $result = $cache->getMultiple(['key1', 'key2'], 'default-value');
        $this->assertEquals(['key1' => 'default-value', 'key2' => 'default-value'], $result);

        $result = $cache->getMultiple(['key1', 'key2'], 'default-value');
        $this->assertEquals(['key1' => 'some-value', 'key2' => 'default-value'], $result);
    }

    public function testInvalidIterableGetMultiple()
    {
        $this->expectException(Exception::class);

        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $cache->getMultiple('invalid');
    }

    public function testHas()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'exists'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->exactly(2))
            ->method('exists')
            ->withConsecutive(['missing'], ['existing'])
            ->willReturnOnConsecutiveCalls(0, 1);

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $this->assertEquals(false, $cache->has('missing'));
        $this->assertEquals(true, $cache->has('existing'));
    }

    public function testSetMultiple()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'mset', 'expire'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->exactly(3))
            ->method('mset')
            ->withConsecutive(
                [['key1' => serialize('value1'), 'key2' => serialize('value2')]],
                [['key3' => serialize('value3'), 'key4' => serialize('value4')]],
                [['key5' => serialize('value5')]]
            )
            ->willReturnOnConsecutiveCalls(true, true, false);

        $redisClient
            ->expects($this->exactly(2))
            ->method('expire')
            ->withConsecutive(['key3', 86400], ['key4', 86400])
            ->willReturnOnConsecutiveCalls(true, true);

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $this->assertTrue($cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']));
        $this->assertTrue(
            $cache->setMultiple(['key3' => 'value3', 'key4' => 'value4'], new DateInterval('P1D'))
        );
        $this->assertFalse($cache->setMultiple(['key5' => 'value5']));
    }

    public function testInvalidIterableSetMultiple()
    {
        $this->expectException(Exception::class);

        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $cache->setMultiple('invalid');
    }

    public function testDeleteMultiple()
    {
        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping', 'del'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $redisClient
            ->expects($this->once())
            ->method('del')
            ->with('key1', 'key3')
            ->willReturn(true);

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $this->assertTrue($cache->deleteMultiple(['key1', 'key3']));

    }

    public function testInvalidIterableDeleteMultiple()
    {
        $this->expectException(Exception::class);

        $redisClient = $this->getMockBuilder(stdClass::class)
            ->setMethods(['ping'])
            ->getMock();

        $redisClient
            ->expects($this->once())
            ->method('ping')
            ->willReturn('+PONG');

        $cache = new RedisCache(null, null, null, null, $redisClient);

        $cache->deleteMultiple('invalid');
    }
}
