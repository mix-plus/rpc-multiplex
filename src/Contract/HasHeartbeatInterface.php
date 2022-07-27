<?php

namespace MixPlus\RpcMultiplex\Contract;

interface HasHeartbeatInterface
{
    public const PING = 'ping';

    public const PONG = 'pong';

    public function isHeartbeat(): bool;
}