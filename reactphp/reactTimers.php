<?php

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$i = 0;
$loop->addPeriodicTimer(1, function () use (&$i) {
    echo ++$i, PHP_EOL;
});

$loop->addTimer(0.8, function () {
    echo 'world!' . PHP_EOL;
});

$loop->addTimer(0.3, function () {
    echo 'hello ';
});

$loop->futureTick(function () {
    echo 'b';
});
$loop->futureTick(function () {
    echo 'c';
});

$loop->addSignal(SIGINT, function (int $signal) use ($loop) {
    echo 'Caught user interrupt signal' . PHP_EOL;
    $loop->stop();
});

$loop->run();