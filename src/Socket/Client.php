<?php

namespace MixPlus\RpcMultiplex\Socket;

use MixPlus\RpcMultiplex\Channel;
use MixPlus\RpcMultiplex\ChannelManager;
use MixPlus\RpcMultiplex\Contract\ClientInterface;
use MixPlus\RpcMultiplex\Contract\CoordinatorInterface;
use MixPlus\RpcMultiplex\Contract\HasHeartbeatInterface;
use MixPlus\RpcMultiplex\Contract\HasSerializerInterface;
use MixPlus\RpcMultiplex\Contract\IdGeneratorInterface;
use MixPlus\RpcMultiplex\Contract\PackerInterface;
use MixPlus\RpcMultiplex\Contract\SerializerInterface;
use MixPlus\RpcMultiplex\CoordinatorManager;
use MixPlus\RpcMultiplex\Exception\ChannelClosedException;
use MixPlus\RpcMultiplex\Exception\ChannelLosedException;
use MixPlus\RpcMultiplex\Exception\ClientConnectFailedException;
use MixPlus\RpcMultiplex\Exception\RecvTimeoutException;
use MixPlus\RpcMultiplex\Exception\SendFailedException;
use MixPlus\RpcMultiplex\IdGenerator;
use MixPlus\RpcMultiplex\Packer;
use MixPlus\RpcMultiplex\Packet;
use MixPlus\RpcMultiplex\Serializer\StringSerializer;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\Socket;
use Throwable;

class Client implements ClientInterface, HasSerializerInterface
{
    protected PackerInterface $packer;

    protected SerializerInterface $serializer;

    protected IdGeneratorInterface $generator;

    protected ?Channel $chan = null;

    protected ?Socket $client = null;

    protected array $config = [
        'package_max_length' => 1024 * 1024 * 2,
        'recv_timeout' => 10,
        'connect_timeout' => 0.5,
        'heartbeat' => 20,
    ];

    protected ChannelManager $channelManager;

    protected bool $heartbeat = false;

    protected ?LoggerInterface $logger = null;

    public function __construct(
        protected string      $name,
        protected int         $port,
        ?IdGeneratorInterface $generator = null,
        ?SerializerInterface  $serializer = null,
        ?PackerInterface      $packer = null
    )
    {
        $this->packer = $packer ?? new Packer();
        $this->generator = $generator ?? new IdGenerator();
        $this->serializer = $serializer ?? new StringSerializer();
        $this->channelManager = new ChannelManager();
    }

    public function set(array $settings): static
    {
        $this->config = $settings;
        return $this;
    }

    public function setLogger(?LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    public function request(mixed $data): mixed
    {
        return $this->recv($this->send($data));
    }

    public function recv(int $id): mixed
    {
        $this->loop();

        $manager = $this->getChannelManager();
        $chan = $manager->get($id);
        if ($chan === null) {
            throw new ChannelLosedException();
        }

        try {
            $data = $chan->pop($this->config['recv_timeout'] ?? 10);
            if ($chan->isTimeout()) {
                throw new RecvTimeoutException(sprintf('Recv channel [%d] pop timeout.', $id));
            }

            if ($chan->isClosing()) {
                throw new ChannelClosedException(sprintf('Recv channel [%d] closed.', $id));
            }
        } finally {
            $manager->close($id);
        }

        return $data;
    }

    protected function loop(): void
    {
        $this->heartbeat();

        if ($this->chan !== null && !$this->chan->isClosing()) {
            return;
        }
        $this->chan = $this->getChannelManager()->make(65535);
        $this->client = $this->makeClient();
        go(function () {
            $reason = '';
            try {
                $chan = $this->chan;
                $client = $this->client;
                while (true) {
                    $data = $client->recvPacket($this->getMaxIdleTime());

                    if ($chan->isClosing()) {
                        $reason = 'channel closed.';
                        break;
                    }

                    if ($data === false || $data === '') {
                        $reason = 'client broken. ' . $client->errMsg;
                        break;
                    }

                    $packet = $this->packer->unpack($data);
                    if ($packet->isHeartbeat()) {
                        continue;
                    }

                    if ($channel = $this->getChannelManager()->get($packet->getId())) {
                        $channel->push(
                            $this->serializer->unserialize($packet->getBody())
                        );
                    } else {
                        $this->logger?->error(sprintf('Recv channel [%d] does not exists.', $packet->getId()));
                    }
                }
            } catch (Throwable $exception) {
                $this->logger?->error((string)$exception);
            } finally {
                $this->logger?->warning('Recv loop broken, wait to restart in next time. The reason is ' . $reason);
                $chan->close();
                $this->getChannelManager()->flush();
                $client->close();
            }
        });

        go(function () {
            $reason = '';
            try {
                $chan = $this->chan;
                $client = $this->client;
                while (true) {
                    $data = $chan->pop();
                    if ($chan->isClosing()) {
                        $reason = 'channel closed.';
                        break;
                    }

                    if (empty($data)) {
                        continue;
                    }

                    $res = $client->sendAll($data, $this->getMaxIdleTime());
                    if ($res === false || strlen($data) !== $res) {
                        throw new SendFailedException('Send data failed. The reason is ' . $client->errMsg);
                    }
                }
            } catch (Throwable $exception) {
                $this->logger?->error((string)$exception);
            } finally {
                $this->logger && $this->logger->warning('Send loop broken, wait to restart in next time. The reason is ' . $reason);
                $chan->close();
                $this->getChannelManager()->flush();
                $client->close();
            }
        });
    }

    protected function heartbeat(): void
    {
        $heartbeat = $this->getHeartbeat();
        if (!$this->heartbeat && is_numeric($heartbeat)) {
            $this->heartbeat = true;

            go(function () use ($heartbeat) {
                try {
                    while (true) {
                        if (CoordinatorManager::until(CoordinatorInterface::WORKER_EXIT)->yield($heartbeat)) {
                            break;
                        }

                        try {
                            // PING
                            if ($chan = $this->chan and $chan->isEmpty()) {
                                $payload = $this->packer->pack(
                                    new Packet(0, HasHeartbeatInterface::PING)
                                );
                                $chan->push($payload);
                            }
                        } catch (Throwable $exception) {
                            $this->logger?->error((string)$exception);
                        }
                    }
                } catch (Throwable $exception) {
                    $this->logger?->error((string)$exception);
                } finally {
                    $this->close();
                }
            });
        }
    }

    protected function getHeartbeat()
    {
        return $this->config['heartbeat'] ?? null;
    }

    public function close(): void
    {
        $this->chan?->close();
        $this->getChannelManager()->flush();
        $this->client?->close();
    }

    public function getChannelManager(): ChannelManager
    {
        return $this->channelManager;
    }

    protected function makeClient(): Socket
    {
        $client = new Socket(AF_INET, SOCK_STREAM, 0);
        $client->setProtocol([
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,
            'package_body_offset' => 4,
            'package_max_length' => $this->config['package_max_length'] ?? 1024 * 1024 * 2,
        ]);
        $ret = $client->connect($this->name, $this->port, $this->config['connect_timeout'] ?? 0.5);
        if ($ret === false) {
            $this->close();
            throw new ClientConnectFailedException($client->errMsg, $client->errCode);
        }
        return $client;
    }

    protected function getMaxIdleTime(): int
    {
        $heartbeat = $this->getHeartbeat();
        if (!is_numeric($heartbeat)) {
            return -1;
        }

        return (int)($heartbeat * 2);
    }

    /**
     * @throws Throwable
     */
    public function send(mixed $data): int
    {
        $this->loop();

        $this->getChannelManager()->get($id = $this->generator->generate(), true);

        try {
            $payload = $this->packer->pack(
                new Packet(
                    $id,
                    $this->getSerializer()->serialize($data)
                )
            );

            $this->chan->push($payload);
        } catch (Throwable $exception) {
            is_int($id) && $this->getChannelManager()->close($id);
            throw $exception;
        }

        return $id;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}