--TEST--
swoole_server: bug aio
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';
define('__FILE_CONTENTS__', file_get_contents(__FILE__));
$pm = new ProcessManager;
$pm->setWaitTimeout(1);
$pm->parentFunc = function () use ($pm) {
    $pm->kill();
    echo "DONE\n";
    exit;
};
$pm->childFunc = function () use ($pm) {
    go(function () {
        assert(Co::readFile(__FILE__) === __FILE_CONTENTS__); // will be discarded
    });
    $server = new Swoole\Server('127.0.0.1', $pm->getFreePort());
    $server->set(['worker_num' => 1, 'log_file' => '/dev/null']);
    $server->on('WorkerStart', function (Swoole\Server $server, int $worker_id) use ($pm) {
        echo 'read file' . PHP_EOL;
        assert(Co::readFile(__FILE__) === __FILE_CONTENTS__);
        echo 'read file ok' . PHP_EOL;
        $pm->wakeup();
        $server->shutdown();
    });
    $server->on('Receive', function () { });
    $server->start();
};
$pm->childFirst();
$pm->run();
?>
--EXPECTF--
read file
read file ok
DONE
