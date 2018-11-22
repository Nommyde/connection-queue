<?php

use React\Stream\ReadableStreamInterface;

require __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$client = new React\HttpClient\Client($loop);

for ($i = 1; $i <= 5; $i++) {
    $loop->addTimer($i, function () use ($client, $i) {
        $request = $client->request('GET', 'http://127.0.0.1:8080/' . ($i === 3 ? '?important=1' : ''));
        $request->on('response', function (ReadableStreamInterface $response) use ($i) {
            $response->on('end', function () use ($i) {
                echo "done $i\n";
            });
        });
        $request->end();
    });
}

$loop->run();
