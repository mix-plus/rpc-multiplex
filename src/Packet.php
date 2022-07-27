<?php

namespace MixPlus\RpcMultiplex;

use MixPlus\RpcMultiplex\Contract\HasHeartbeatInterface;

class Packet implements HasHeartbeatInterface
{
    public function __construct(protected int $id, protected string $body)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function isHeartbeat(): bool
    {
        return $this->id === 0 && in_array($this->body, [static::PING, static::PONG], true);
    }
}