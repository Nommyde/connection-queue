<?php

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Response;
use React\Http\StreamingServer;
use React\Promise\Promise;

require __DIR__ . '/vendor/autoload.php';

$id = 0;
$queue = [];
$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server(8080, $loop);
$limiter = new LimitConcurrentRequestsMiddleware(20);
$server = new StreamingServer([$limiter, function (ServerRequestInterface $request) use (&$queue, &$id) {
    $id++;
    $query = $request->getQueryParams();
    $important = intval($query['important'] ?? 0);
    echo "request $id" . ($important ? "!\n" : "\n");

    if (!$important && count($queue) > 15) {
        echo "  deny $id\n";
        return new Response(200, ['Content-Type' => 'text/plain'], 'no');
    }

    return new Promise(function ($resolve) use (&$queue, $important, $id) {
        if ($important) {
            array_unshift($queue, [$resolve, $id]);
        } else {
            array_push($queue, [$resolve, $id]);
        }
    });
}]);

$loop->addPeriodicTimer(5, function () use (&$queue) {
    if ([$resolve, $id] = array_shift($queue)) {
        echo "  response for $id\n";
        $resolve(new Response(200, ['Content-Type' => 'text/plain'], 'yes'));
    }
});

$server->listen($socket);
$loop->run();
