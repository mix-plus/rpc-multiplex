<?php

namespace MixPlus\RpcMultiplex;

class ChannelManager
{
    /**
     * @var Channel[]
     */
    protected array $channels = [];

    public function get(int $id, bool $initialize = false): ?Channel
    {
        if (isset($this->channels[$id])) {
            return $this->channels[$id];
        }

        if ($initialize) {
            return $this->channels[$id] = $this->make(1);
        }

        return null;
    }

    public function make(int $limit): Channel
    {
        return new Channel($limit);
    }

    public function flush(): void
    {
        $channels = $this->getChannels();
        foreach ($channels as $id => $channel) {
            $this->close($id);
        }
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function close(int $id): void
    {
        if ($channel = $this->channels[$id] ?? null) {
            $channel->close();
        }

        unset($this->channels[$id]);
    }
}