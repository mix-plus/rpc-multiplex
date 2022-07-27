<?php

namespace MixPlus\RpcMultiplex\Serializer;

use MixPlus\RpcMultiplex\Contract\SerializerInterface;

class StringSerializer implements SerializerInterface
{
    public function serialize(mixed $data): string
    {
        return (string)$data;
    }

    public function unserialize(string $serialized): mixed
    {
        return $serialized;
    }
}