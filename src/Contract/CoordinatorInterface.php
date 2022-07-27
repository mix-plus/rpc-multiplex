<?php

namespace MixPlus\RpcMultiplex\Contract;

interface CoordinatorInterface
{
    /**
     * Swoole onWorkerStart event.
     */
    public const WORKER_START = 'workerStart';

    /**
     * Swoole onWorkerExit event.
     */
    public const WORKER_EXIT = 'workerExit';
}