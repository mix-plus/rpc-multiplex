<?php

namespace MixPlus\RpcMultiplex\Contract;

interface SerializerInterface
{
    public function serialize(mixed $data): string;

    public function unserialize(string $serialized): mixed;
}