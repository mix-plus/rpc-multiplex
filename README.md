# 多路复用组件


# 安装
```bash
composer require mix-plus/rpc-multiplex
```

# 测试

```php
# 客户端
$max = 100;
    go(function () use ($max) {
        $client = new Client('127.0.0.1', 9601);
        for ($i = 0; $i < $max; ++$i) {
            go(function () use ($client) {
                $client->request('World.');
            });
        }
    });
# 服务端
Coroutine::create(function () {
    $server = new Server();
    $config = [];
    echo 'swoole server running...';
    $server->bind('0.0.0.0', 9601, $config)->handle(static function (Packet $packet) {
        var_dump('hello' . $packet->getBody());
    })->start();
});
```

结果
```bash
swoole server running...string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
string(6) "World."
```

# LICENSE
Apache License Version 2.0, http://www.apache.org/licenses/