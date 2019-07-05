<?php

/**
 * Testing the write and read speed of different serialization methods for use in the cache library
 * 1. var_export on write, import on read.
 * 2. serialize on write, unserialize on read.
 * 3. json_encode on write, json_decode on read.
 * 4. igbinary_serialize on write, igbinary_unserialize on read.
 */

$data = [
    'small' => [
        'int' => 123,
        'float' => 123.123,
        'string' => 'Hey mama',
        'boolean' => false,
        'null' => null,
        'object' => new DateTime(),
        'array' => [
            'some' => 'values',
            'added' => 'here',
        ]
    ],
    'big' => [
        'key' => [
            'int' => 123,
            'float' => 123.123,
            'string' => 'Hey mama',
            'boolean' => false,
            'null' => null,
            'object' => new DateTime(),
            'array' => [
                'some' => 'values',
                'added' => 'here',
                'key' => [
                    'int' => 123,
                    'float' => 123.123,
                    'string' => 'Hey mama',
                    'boolean' => false,
                    'null' => null,
                    'object' => new DateTime(),
                    'array' => [
                        'some' => 'values',
                        'added' => 'here',
                        'key' => [
                            'int' => 123,
                            'float' => 123.123,
                            'string' => 'Hey mama',
                            'boolean' => false,
                            'null' => null,
                            'object' => new DateTime(),
                            'array' => [
                                'some' => 'values',
                                'added' => 'here',
                                'key' => [
                                    'int' => 123,
                                    'float' => 123.123,
                                    'string' => 'Hey mama',
                                    'boolean' => false,
                                    'null' => null,
                                    'object' => new DateTime(),
                                    'array' => [
                                        'some' => 'values',
                                        'added' => 'here',
                                        'key' => [
                                            'int' => 123,
                                            'float' => 123.123,
                                            'string' => 'Hey mama',
                                            'boolean' => false,
                                            'null' => null,
                                            'object' => new DateTime(),
                                            'array' => [
                                                'some' => 'values',
                                                'added' => 'here',
                                                'key' => [
                                                    'int' => 123,
                                                    'float' => 123.123,
                                                    'string' => 'Hey mama',
                                                    'boolean' => false,
                                                    'null' => null,
                                                    'object' => new DateTime(),
                                                    'array' => [
                                                        'some' => 'values',
                                                        'added' => 'here',
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ]
];

for ($i = 1; $i < 10000; $i++) {
    $data['big']['key' . $i] = $data['big']['key'];
}

$results = [];

const TEST_FILE = __DIR__ . '/test-file';

touch(TEST_FILE);

/**
 * 1. var_export on write, import on read.
 */
echo 'Testing var_export write - small data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, '<?php return ' . var_export($data['small'], true) . ';');
    $times[] = microtime(true) - $start;
}

$results['var_export']['small'] = array_sum($times);

echo 'Testing var_export write - big data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, '<?php return ' . var_export($data['big'], true) . ';');
    $times[] = microtime(true) - $start;
}

$results['var_export']['big'] = array_sum($times);


echo 'Testing include read - small data...' . PHP_EOL;
file_put_contents(TEST_FILE, '<?php return ' . var_export($data['small'], true) . ';');

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = include TEST_FILE;
    $times[] = microtime(true) - $start;
}

$results['include']['small'] = array_sum($times);

echo 'Testing include read - big data...' . PHP_EOL;
file_put_contents(TEST_FILE, '<?php return ' . var_export($data['big'], true) . ';');

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = include TEST_FILE;
    $times[] = microtime(true) - $start;
}

$results['include']['big'] = array_sum($times);

/**
 * 2. serialize on write, unserialize on read.
 */
echo 'Testing serialize write - small data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, serialize($data['small']));
    $times[] = microtime(true) - $start;
}

$results['serialize']['small'] = array_sum($times);

echo 'Testing serialize write - big data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, serialize($data['big']));
    $times[] = microtime(true) - $start;
}

$results['serialize']['big'] = array_sum($times);


echo 'Testing unserialize read - small data...' . PHP_EOL;
file_put_contents(TEST_FILE, serialize($data['small']));

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = unserialize(file_get_contents(TEST_FILE));
    $times[] = microtime(true) - $start;
}

$results['unserialize']['small'] = array_sum($times);

echo 'Testing unserialize read - big data...' . PHP_EOL;
file_put_contents(TEST_FILE, serialize($data['big']));

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = unserialize(file_get_contents(TEST_FILE));
    $times[] = microtime(true) - $start;
}

$results['unserialize']['big'] = array_sum($times);

/**
 * 3. json_encode on write, json_decode on read.
 */
echo 'Testing json_encode write - small data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, json_encode($data['small']));
    $times[] = microtime(true) - $start;
}

$results['json_encode']['small'] = array_sum($times);

echo 'Testing json_encode write - big data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, json_encode($data['big']));
    $times[] = microtime(true) - $start;
}

$results['json_encode']['big'] = array_sum($times);


echo 'Testing json_decode read - small data...' . PHP_EOL;
file_put_contents(TEST_FILE, json_encode($data['small']));

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = json_decode(file_get_contents(TEST_FILE));
    $times[] = microtime(true) - $start;
}

$results['json_decode']['small'] = array_sum($times);

echo 'Testing json_decode read - big data...' . PHP_EOL;
file_put_contents(TEST_FILE, json_encode($data['big']));

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = json_decode(file_get_contents(TEST_FILE));
    $times[] = microtime(true) - $start;
}

$results['json_decode']['big'] = array_sum($times);

/**
 * 4. igbinary_serialize on write, igbinary_unserialize on read.
 */
echo 'Testing igbinary_serialize write - small data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, igbinary_serialize($data['small']));
    $times[] = microtime(true) - $start;
}

$results['igbinary_serialize']['small'] = array_sum($times);

echo 'Testing igbinary_serialize write - big data...' . PHP_EOL;

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    file_put_contents(TEST_FILE, igbinary_serialize($data['big']));
    $times[] = microtime(true) - $start;
}

$results['igbinary_serialize']['big'] = array_sum($times);


echo 'Testing igbinary_unserialize read - small data...' . PHP_EOL;
file_put_contents(TEST_FILE, igbinary_serialize($data['small']));

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = igbinary_unserialize(file_get_contents(TEST_FILE));
    $times[] = microtime(true) - $start;
}

$results['igbinary_unserialize']['small'] = array_sum($times);

echo 'Testing igbinary_unserialize read - big data...' . PHP_EOL;
file_put_contents(TEST_FILE, igbinary_serialize($data['big']));

$times = [];

for ($i = 0; $i < 100; $i++) {
    $start = microtime(true);
    $readData = igbinary_unserialize(file_get_contents(TEST_FILE));
    $times[] = microtime(true) - $start;
}

$results['igbinary_unserialize']['big'] = array_sum($times);


/**
 * Printing results
 */
echo  PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- var_export write' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['var_export']['small'] . PHP_EOL
    . 'Big data average: ' . $results['var_export']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- include read' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['include']['small'] . PHP_EOL
    . 'Big data average: ' . $results['include']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- serialize write' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['serialize']['small'] . PHP_EOL
    . 'Big data average: ' . $results['serialize']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- unserialize read' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['unserialize']['small'] . PHP_EOL
    . 'Big data average: ' . $results['unserialize']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- json_encode write' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['json_encode']['small'] . PHP_EOL
    . 'Big data average: ' . $results['json_encode']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- json_decode read' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['json_decode']['small'] . PHP_EOL
    . 'Big data average: ' . $results['json_decode']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- igbinary_serialize write' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['igbinary_serialize']['small'] . PHP_EOL
    . 'Big data average: ' . $results['igbinary_serialize']['big'] . PHP_EOL
    . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . '--- igbinary_unserialize read' . PHP_EOL
    . '-----------------------------------------------------------------------------' . PHP_EOL
    . PHP_EOL
    . 'Small data average: ' . $results['igbinary_unserialize']['small'] . PHP_EOL
    . 'Big data average: ' . $results['igbinary_unserialize']['big'] . PHP_EOL
    . PHP_EOL;


unlink(TEST_FILE);
