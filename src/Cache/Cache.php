<?php

declare(strict_types=1);

namespace Vuryss\Cache;

class Cache
{
    /**
     * Which method of serializing data to use.
     *
     * @var string
     */
    protected $serializeMethod = Serializer::METHOD_NATIVE;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * Validates key
     *
     * @throws Exception
     *
     * @param string $key
     */
    protected function validateKey($key)
    {
        if (!is_string($key) || empty($key) || !preg_match('/^[a-z0-9\-_\:]+$/i', $key)) {
            throw new Exception(
                'Invalid key, it should be a non-empty string containing the following characters: a-z0-9-_:'
            );
        }
    }
}
