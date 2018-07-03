<?php
use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;

if (! function_exists('start_dumping_queries')) {
    function start_dumping_queries(): void
    {
        CapsuleDebugListener::getInstance()->enable();
    }
}

if (! function_exists('stop_dumping_queries')) {
    function stop_dumping_queries(): void
    {
        CapsuleDebugListener::getInstance()->disable();
    }
}

if (! function_exists('start_capturing_queries')) {
    function start_capturing_queries(): void
    {
        $listener       = CapsuleDebugListener::getInstance();
        $listener->logs = []; // Dynamically declared so it's available in function below.
        $listener->enable(function (array $trace) {
            $this->logs[] = $trace;
        });
    }
}

if (! function_exists('stop_capturing_queries')) {
    function stop_capturing_queries(): array
    {
        $listener = CapsuleDebugListener::getInstance();
        $listener->disable();
        return $listener->logs;
    }
}
