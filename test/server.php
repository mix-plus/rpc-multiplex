<?php

use MixPlus\RpcMultiplex\Packet;
use MixPlus\RpcMultiplex\Socket\Server;

require_once __DIR__ . '/../vendor/autoload.php';

go(function () {
    $server = new Server();
    $config = [];
    echo 'swoole server running...';
    $server->bind('0.0.0.0', 9601, $config)->handle(static function (Packet $packet) {
        var_dump('hello' . $packet->getBody());
    })->start();
});