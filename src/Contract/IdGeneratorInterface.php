<?php

namespace MixPlus\RpcMultiplex\Contract;

interface IdGeneratorInterface
{
    public function generate(): int;
}