<?php
// composer require rapttor/threads
require_once(__DIR__."/../src/Threads.php");

$wait = true;
$debug = true;

\RapTToR\Threads\Thread::run(array(
    "process" => function ($data = false) {
        var_dump('run A: ', $data);
        for ($i = 0; $i < 3; $i++) {
            echo "A $i" . PHP_EOL;
            sleep(1);
        }
        return "A RUN DONE;" . PHP_EOL; // never received.
    }
));

\RapTToR\Threads\Thread::run(array(
    "process" => function ($data = false) {
        var_dump('run B: ', $data);
        for ($i = 0; $i < 3; $i++) {
            echo "BB $i" . PHP_EOL;
            sleep(2);
        }
        return "BB RUN DONE; " . PHP_EOL;
    },
    "response" => function ($data) {
        var_dump('BB response: ', $data);
    }
));

if ($wait)
    pcntl_wait($status);
echo "app done.";
