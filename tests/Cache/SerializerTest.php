<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Vuryss\Cache;

use DateTime;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    public function testSerializerInstantiation()
    {
        $native = new Serializer(Serializer::METHOD_NATIVE);
        $this->assertTrue($native instanceof Serializer);

        $json = new Serializer(Serializer::METHOD_JSON);
        $this->assertTrue($json instanceof Serializer);

        $igbinary = new Serializer(Serializer::METHOD_IGBINARY);
        $this->assertTrue($igbinary instanceof Serializer);
    }

    public function testInvalidSerializerInstantiation()
    {
        $this->expectException(Exception::class);
        new Serializer('invalid');
    }

    public function testSerialize()
    {
        $data = [
            'string' => 'Your mom',
            'int' => 123,
            'float' => 123.123,
            'bool' => false,
            'bool2' => true,
            'null' => null,
            'object' => new DateTime(),
        ];

        $native = new Serializer(Serializer::METHOD_NATIVE);
        $this->assertEquals(serialize($data), $native->serialize($data));

        $json = new Serializer(Serializer::METHOD_JSON);
        $this->assertEquals(json_encode($data), $json->serialize($data));

        $igbinary = new Serializer(Serializer::METHOD_IGBINARY);
        $this->assertEquals(igbinary_serialize($data), $igbinary->serialize($data));
    }

    public function testDeserialize()
    {
        $data = [
            'string' => 'Your mom',
            'int' => 123,
            'float' => 123.123,
            'bool' => false,
            'bool2' => true,
            'null' => null,
            'object' => new DateTime(),
        ];

        $native = new Serializer(Serializer::METHOD_NATIVE);
        $data2 = serialize($data);
        $this->assertEquals(unserialize($data2, ['allowed_classes' => false]), $native->deserialize($data2));

        $json = new Serializer(Serializer::METHOD_JSON);
        $data2 = json_encode($data);
        $this->assertEquals(json_decode($data2), $json->deserialize($data2));

        $igbinary = new Serializer(Serializer::METHOD_IGBINARY);
        $data2 = igbinary_serialize($data);
        $this->assertEquals(igbinary_unserialize($data2), $igbinary->deserialize($data2));
    }

    public function testInvalidNativeDeserialize()
    {
        $this->expectException(Exception::class);
        $native = new Serializer(Serializer::METHOD_NATIVE);
        $native->deserialize('invalid');
    }

    public function testInvalidJsonDeserialize()
    {
        $this->expectException(Exception::class);
        $json = new Serializer(Serializer::METHOD_JSON);
        $json->deserialize('invalid');
    }

    public function testInvalidIgbinaryDeserialize()
    {
        $this->expectException(Exception::class);
        $igbinary = new Serializer(Serializer::METHOD_IGBINARY);
        $igbinary->deserialize('invalid');
    }
}
