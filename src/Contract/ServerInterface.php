<?php

namespace MixPlus\RpcMultiplex\Contract;

use MixPlus\RpcMultiplex\Exception\ServerBindFailedException;
use MixPlus\RpcMultiplex\Exception\ServerStartFailedException;

interface ServerInterface
{
    /**
     * @param $config = [
     *     'package_max_length' => 1024 * 1024 * 2
     * ]
     * @throws ServerBindFailedException
     */
    public function bind(string $name, int $port, array $config): static;

    public function handle(callable $callable): static;

    /**
     * @throws ServerStartFailedException
     */
    public function start(): void;
}