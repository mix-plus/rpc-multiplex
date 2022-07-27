<?php

namespace MixPlus\RpcMultiplex\Contract;

use MixPlus\RpcMultiplex\ChannelManager;

interface ClientInterface
{
    public function set(array $settings): static;

    public function request(mixed $data): mixed;

    public function send(mixed $data): int;

    public function recv(int $id): mixed;

    public function getChannelManager(): ChannelManager;

    public function close(): void;
}