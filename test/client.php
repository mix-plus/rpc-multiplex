<?php

use MixPlus\RpcMultiplex\Socket\Client;

require_once __DIR__ . '/../vendor/autoload.php';


try {
    $max = 100;
    go(function () use ($max) {
        $client = new Client('127.0.0.1', 9601);
        for ($i = 0; $i < $max; ++$i) {
            go(function () use ($client) {
                $client->request('World.');
            });
        }
    });
} catch (Throwable $e) {
    var_dump((string)$e);
}
