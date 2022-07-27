<?php

namespace MixPlus\RpcMultiplex\Contract;

use MixPlus\RpcMultiplex\Packet;

interface PackerInterface
{
    public function pack(Packet $packet): string;

    public function unpack(string $data): Packet;
}