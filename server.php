<?php

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;
use React\Promise\Promise;

require __DIR__ . '/vendor/autoload.php';

$id = 0;
$queue = [];
$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server(8080, $loop);

$server = new Server(function (ServerRequestInterface $request) use (&$queue, &$id) {
    $id++;
    $query = $request->getQueryParams();
    $important = intval($query['important'] ?? 0);
    echo "request $id" . ($important ? "!\n" : "\n");

    return new Promise(function ($resolve) use (&$queue, $important, $id) {
        if ($important) {
            array_unshift($queue, [$resolve, $id]);
        } else {
            array_push($queue, [$resolve, $id]);
        }
    });
});

$loop->addPeriodicTimer(5, function () use (&$queue) {
    if ([$resolve, $id] = array_shift($queue)) {
        echo "  response for $id\n";
        $resolve(new Response(200, ['Content-Type' => 'text/plain'], 'ok'));
    }
});

$server->listen($socket);
$loop->run();
