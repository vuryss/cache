<?php

declare(strict_types=1);

namespace Vuryss\Cache;

/**
 * Class Cache
 */
abstract class Cache
{
    /**
     * Which method of serializing data to use.
     *
     * @var string
     */
    protected $serializeMethod = Serializer::METHOD_NATIVE;

    /**
     * Class which will provide methods for serialization.
     *
     * @var Serializer
     */
    protected $serializer;

    /**
     * Validates key
     *
     * @throws Exception
     *
     * @param mixed $key Cache key to validate.
     *
     * @return void
     */
    protected function validateKey($key)
    {
        if (!is_string($key) || empty($key) || !preg_match('/^[a-z0-9\-_\:\\\\]+$/i', $key)) {
            throw new Exception(
                'Invalid key, it should be a non-empty string containing the following characters: a-z0-9-_:'
            );
        }
    }

    /**
     * Loads the serializer
     *
     * @throws Exception
     *
     * @param string $serializeMethod Serialize method to use, refer to Serializer class constants.
     *
     * @return void
     */
    protected function loadSerializer(string $serializeMethod = null)
    {
        $serializeMethods = [Serializer::METHOD_NATIVE, Serializer::METHOD_IGBINARY, Serializer::METHOD_JSON];

        if ($serializeMethod) {
            if (!in_array($serializeMethod, $serializeMethods)) {
                throw new Exception('Invalid serialization method!');
            }

            $this->serializeMethod = $serializeMethod;
        }

        $this->serializer = new Serializer($this->serializeMethod);
    }
}
