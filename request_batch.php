<?php

require __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$client = new React\HttpClient\Client($loop);

for ($i = 1; $i <= 10; $i++) {
    $loop->addTimer($i, function () use ($client, $i) {
        $request = $client->request('GET', 'http://127.0.0.1:8080/' . ($i === 5 ? '?important=1' : ''));
        $request->end();
        echo "request sent\n";
    });
}

$loop->run();
