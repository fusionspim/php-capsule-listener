<?php
use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;

if (! function_exists('show_queries')) {
    function show_queries(): void
    {
        (new CapsuleDebugListener)->enable();
    }
}

if (! function_exists('hide_queries')) {
    function hide_queries(): void
    {
        (new CapsuleDebugListener)->disable();
    }
}
