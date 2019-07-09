<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Vuryss\Cache;

use DateInterval;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertTrue(!file_exists(TEST_DATA_DIR . '/cache-file'));
        new FileCache(TEST_DATA_DIR . '/cache-file');
        $this->assertTrue(file_exists(TEST_DATA_DIR . '/cache-file'));
        unlink(TEST_DATA_DIR . '/cache-file');

        $this->assertTrue(!file_exists(TEST_DATA_DIR . '/cache-file'));
        new FileCache(TEST_DATA_DIR . '/cache-file', Serializer::METHOD_NATIVE);
        $this->assertTrue(file_exists(TEST_DATA_DIR . '/cache-file'));
        unlink(TEST_DATA_DIR . '/cache-file');

        $this->assertTrue(!file_exists(TEST_DATA_DIR . '/cache-file'));
        new FileCache(TEST_DATA_DIR . '/cache-file', Serializer::METHOD_JSON);
        $this->assertTrue(file_exists(TEST_DATA_DIR . '/cache-file'));
        unlink(TEST_DATA_DIR . '/cache-file');

        $this->assertTrue(!file_exists(TEST_DATA_DIR . '/cache-file'));
        new FileCache(TEST_DATA_DIR . '/cache-file', Serializer::METHOD_IGBINARY);
        $this->assertTrue(file_exists(TEST_DATA_DIR . '/cache-file'));
        unlink(TEST_DATA_DIR . '/cache-file');
    }

    public function testUnwritableFile()
    {
        touch(TEST_DATA_DIR . '/cache-file');
        chmod(TEST_DATA_DIR . '/cache-file', 0500);
        $this->expectException(Exception::class);
        new FileCache(TEST_DATA_DIR . '/cache-file');
    }

    public function testUnwritable2File()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        chmod(TEST_DATA_DIR, 0500);
        $this->expectException(Exception::class);
        new FileCache(TEST_DATA_DIR . '/cache-file');
    }

    public function testInvalidSerializer()
    {
        chmod(TEST_DATA_DIR, 0777);
        @unlink(TEST_DATA_DIR . '/cache-file');
        $this->expectException(Exception::class);
        new FileCache(TEST_DATA_DIR . '/cache-file', 'invalid');
    }

    public function testClear()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $this->assertTrue($cache->clear());
    }

    public function testGet()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');

        $this->assertEquals('test content', $cache->get('missing', 'test content'));

        file_put_contents(
            TEST_DATA_DIR . '/cache-file',
            serialize(
                [
                    'test-key'    => ['ttl' => 0, 'value' => 'test-value'],
                    'expired-key' => ['ttl' => -10, 'value' => 'test-value'],
                ]
            )
        );

        $this->assertEquals('test-value', $cache->get('test-key', 'test content'));
        $this->assertEquals('test content', $cache->get('expired-key', 'test content'));
    }

    public function testInvalidKey()
    {
        $this->expectException(Exception::class);
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $cache->get('!@#%$');
    }

    public function testSet()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');

        $this->assertTrue($cache->set('test-key', 'test-value'));
        $this->assertEquals('test-value', $cache->get('test-key'));

        $this->assertTrue($cache->set('test-interval', 'test-value-2', new DateInterval('P1D')));
        $this->assertEquals('test-value-2', $cache->get('test-interval', 'default?'));
    }

    public function testDelete()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $this->assertTrue($cache->delete('some-key'));

        $this->assertTrue($cache->set('other-key', 'value'));
        $this->assertTrue($cache->delete('other-key'));
    }

    public function testGetMultiple()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $result = $cache->getMultiple(['key1', 'key2'], 'default-value');
        $this->assertEquals(['key1' => 'default-value', 'key2' => 'default-value'], $result);

        $this->assertTrue($cache->set('key1', 'some-value'));
        $this->assertTrue($cache->set('key2', 'other-value', -10));

        $result = $cache->getMultiple(['key1', 'key2'], 'default-value');
        $this->assertEquals(['key1' => 'some-value', 'key2' => 'default-value'], $result);
    }

    public function testInvalidIterableGetMultiple()
    {
        $this->expectException(Exception::class);
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $cache->getMultiple('invalid');
    }

    public function testHas()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');

        $this->assertEquals(false, $cache->has('missing'));

        file_put_contents(
            TEST_DATA_DIR . '/cache-file',
            serialize(
                [
                    'test-key'    => ['ttl' => 0, 'value' => 'test-value'],
                    'expired-key' => ['ttl' => -10, 'value' => 'test-value'],
                ]
            )
        );

        $this->assertEquals(true, $cache->has('test-key'));
        $this->assertEquals(false, $cache->has('expired-key'));
    }

    public function testSetMultiple()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');

        $this->assertTrue($cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']));
        $this->assertTrue(
            $cache->setMultiple(['key3' => 'value3', 'key4' => 'value4'], new DateInterval('P1D'))
        );

        $this->assertEquals('value1', $cache->get('key1'));
        $this->assertEquals('value2', $cache->get('key2'));
        $this->assertEquals('value3', $cache->get('key3'));
        $this->assertEquals('value4', $cache->get('key4'));
    }

    public function testInvalidIterableSetMultiple()
    {
        $this->expectException(Exception::class);
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $cache->setMultiple('invalid');
    }

    public function testDeleteMultiple()
    {
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');

        $this->assertTrue($cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']));
        $this->assertTrue($cache->deleteMultiple(['key1', 'key3']));
        $this->assertEquals('default', $cache->get('key1', 'default'));
        $this->assertEquals('value2', $cache->get('key2', 'default'));

    }

    public function testInvalidIterableDeleteMultiple()
    {
        $this->expectException(Exception::class);
        @unlink(TEST_DATA_DIR . '/cache-file');
        $cache = new FileCache(TEST_DATA_DIR . '/cache-file');
        $cache->deleteMultiple('invalid');
    }
}
