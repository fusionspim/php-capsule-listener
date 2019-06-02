<?php
use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;
use Illuminate\Database\Connection;

if (! function_exists('start_dumping_queries')) {
    function start_dumping_queries(Connection $connection = null): CapsuleDebugListener
    {
        $listener = CapsuleDebugListener::getInstance('dump');

        if ($connection !== null) {
            $listener->setConnection($connection);
        }

        $listener->enable();

        return $listener;
    }
}

if (! function_exists('stop_dumping_queries')) {
    function stop_dumping_queries(): void
    {
        CapsuleDebugListener::getInstance('dump')->disable();
    }
}

if (! function_exists('start_capturing_queries')) {
    function start_capturing_queries(Connection $connection = null): CapsuleDebugListener
    {
        $listener = CapsuleDebugListener::getInstance('capture');

        if ($connection !== null) {
            $listener->setConnection($connection);
        }

        $listener->logs = []; // Dynamically declared so it's available in function below.
        $listener->enable(function (array $trace): void {
            $this->logs[] = $trace;
        });

        return $listener;
    }
}

if (! function_exists('stop_capturing_queries')) {
    function stop_capturing_queries(): array
    {
        $listener = CapsuleDebugListener::getInstance('capture');
        $listener->disable();

        return $listener->logs;
    }
}
