<?php

namespace MixPlus\RpcMultiplex;

use MixPlus\RpcMultiplex\Contract\IdGeneratorInterface;

class IdGenerator implements IdGeneratorInterface
{
    protected int $id = 0;

    public function generate(): int
    {
        return ++$this->id;
    }
}