<?php
namespace FusionsPim\Tests\PhpCapsuleListener;

use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;
use PHPUnit\Framework\TestCase;

class CapsuleDebugListenerTest extends TestCase
{
    public function test_get_instance()
    {
        $instance = CapsuleDebugListener::getInstance();
        $this->assertInstanceOf(CapsuleDebugListener::class, $instance);
        $this->assertSame(CapsuleDebugListener::getInstance(), $instance);
    }
}
