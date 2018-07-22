<?php

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$loop->addReadStream(STDIN, function ($stream) use ($loop) {
    $chunk = fread($stream, 64 * 1024);
    if (empty(trim($chunk))) {
        $loop->removeReadStream($stream);
        stream_set_blocking($stream, true);
        fclose($stream);

        return;
    }
    echo strlen($chunk) . ' bytes' . PHP_EOL;
});
$loop->run();