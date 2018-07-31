<?php

use Generators\Scheduler;
use Generators\SystemCall;
use Generators\Task;

require __DIR__ . '/../vendor/autoload.php';

if ($argv[1] == 'range') {
    function xrange($start, $end, $step = 1)
    {
        for ($i = $start; $i <= $end; $i += $step) {
            yield $i;
        }
    }

    foreach (xrange(1, 1000000) as $num) {
        echo $num, "\n";
    }
}

if ($argv[1] == 'logger') {
    function logger($fileName)
    {
        $fileHandle = fopen($fileName, 'a');
        while (true) {
            fwrite($fileHandle, yield . "\n");
        }
    }

    $logger = logger('php://stdout');
    $logger->send('Foo');
    $logger->send('Bar');
}

if ($argv[1] == 'gen') {
    function gen()
    {
        $ret = (yield 'yield1');
        var_dump($ret);
        $ret = (yield 'yield2');
        var_dump($ret);
    }

    $gen = gen();
    $current = $gen->current();
    var_dump($current);    // string(6) "yield1"
    var_dump($gen->send('ret1')); // string(4) "ret1"   (the first var_dump in gen)
    // string(6) "yield2" (the var_dump of the ->send() return value)
    var_dump($gen->send('ret2')); // string(4) "ret2"   (again from within gen)
    // NULL               (the return value of ->send())
}

if ($argv[1] == 'prueba-yield') {
    function printer()
    {
        $values = ['one', 'two'];
        echo "I'm printer!" . PHP_EOL;
        for ($i = 0; ; $i++) {
            $string = yield;
            echo $string . $values[$i] . PHP_EOL;
        }
    }

    $printer = printer();
    $printer->send('Hello world!');
    $printer->send('Bye world!');
    $printer->send('HOla primo');
    $printer->send('vamos primo');
}

if ($argv[1] == 'gen-rewind') {
    function gen()
    {
        yield 'foo';
        yield 'bar';
    }

    $gen = gen();
    var_dump($gen->send('something'));

// As the send() happens before the first yield there is an implicit rewind() call,
// so what really happens is this:
//    $gen->rewind();
//    var_dump($gen->send('something'));

// The rewind() will advance to the first yield (and ignore its value), the send() will
// advance to the second yield (and dump its value). Thus we loose the first yielded value!
}

if ($argv[1] == 'simple-scheduler') {
    function task1()
    {
        for ($i = 1; $i <= 10; ++$i) {
            echo "This is task 1 iteration $i.\n";
            yield;
        }
    }

    function task2()
    {
        for ($i = 1; $i <= 5; ++$i) {
            echo "This is task 2 iteration $i.\n";
            yield;
        }
    }

    $scheduler = new Scheduler;

    $scheduler->newTask(task1());
    $scheduler->newTask(task2());

    $scheduler->run();
}
if ($argv[1] == 'scheduler-2') {
    function getTaskId()
    {
        return new SystemCall(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    function task($max)
    {
        $tid = (yield getTaskId()); // <-- here's the syscall!
        for ($i = 1; $i <= $max; ++$i) {
            echo "This is task $tid iteration $i.\n";
            yield;
        }
    }

    $scheduler = new Scheduler;

    $scheduler->newTask(task(10));
    $scheduler->newTask(task(5));

    $scheduler->run();
}
if ($argv[1] == 'scheduler') {
    function getTaskId()
    {
        return new SystemCall(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    function newTask(Generator $coroutine)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($coroutine) {
                $task->setSendValue($scheduler->newTask($coroutine));
                $scheduler->schedule($task);
            }
        );
    }

    function killTask($tid)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($tid) {
                $task->setSendValue($scheduler->killTask($tid));
                $scheduler->schedule($task);
            }
        );
    }

    function childTask()
    {
        $tid = (yield getTaskId());
        while (true) {
            echo "Child task $tid still alive!\n";
            yield;
        }
    }

    function task()
    {
        $tid = (yield getTaskId());
        $childTid = (yield newTask(childTask()));

        for ($i = 1; $i <= 6; ++$i) {
            echo "Parent task $tid iteration $i.\n";
            yield;

            if ($i == 3) yield killTask($childTid);
        }
    }


    $scheduler = new Scheduler;
    $scheduler->newTask(task());
    $scheduler->run();
}
if ($argv[1] == 'httpserver') {

    echo "hola";

    function waitForRead($socket)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($socket) {
                $scheduler->waitForRead($socket, $task);
            }
        );
    }

    function waitForWrite($socket)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($socket) {
                $scheduler->waitForWrite($socket, $task);
            }
        );
    }

    function server($port)
    {
        echo "Starting server at port $port...\n";

        $socket = stream_socket_server("tcp://0.0.0.0:8081", $errNo, $errStr);
        if (!$socket) throw new \Exception($errStr, $errNo);

        stream_set_blocking($socket, 0);

        while (true) {
            yield waitForRead($socket);
            $clientSocket = stream_socket_accept($socket, 0);
            yield newTask(handleClient($clientSocket));
        }
    }

    function handleClient($socket)
    {
        yield waitForRead($socket);
        $data = fread($socket, 8192);

        $msg = "Received following request:\n\n$data";
        $msgLength = strlen($msg);

        $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

        yield waitForWrite($socket);
        fwrite($socket, $response);

        fclose($socket);
    }

    $scheduler = new Scheduler;
    $scheduler->newTask(server(8081));
    $scheduler->run();
}