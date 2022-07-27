<?php

namespace MixPlus\RpcMultiplex;

use MixPlus\RpcMultiplex\Contract\PackerInterface;

class Packer implements PackerInterface
{
    public function pack(Packet $packet): string
    {
        return sprintf(
            '%s%s%s',
            pack('N', strlen($packet->getBody()) + 4),
            pack('N', $packet->getId()),
            $packet->getBody()
        );
    }

    public function unpack(string $data): Packet
    {
        $unpacked = unpack('Nid', substr($data, 4, 4));
        $body = substr($data, 8);
        return new Packet((int)$unpacked['id'], $body);
    }
}