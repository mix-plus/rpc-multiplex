<?php

namespace MixPlus\RpcMultiplex\Contract;

interface HasSerializerInterface
{
    public function getSerializer(): SerializerInterface;
}