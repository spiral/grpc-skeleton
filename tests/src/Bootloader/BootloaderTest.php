<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Tests\Bootloader;

use Ruvents\SpiralJwt\JwtAuthBootloader;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use VendorName\Skeleton\GRPC\Invoker;
use VendorName\Skeleton\Tests\TestCase;

final class BootloaderTest extends TestCase
{
    public function testInvokerInterface(): void
    {
        $this->assertContainerBoundAsSingleton(
            InvokerInterface::class,
            Invoker::class
        );
    }

    public function testJwtAuthBootloader(): void
    {
        $this->assertBootloaderRegistered(JwtAuthBootloader::class);
    }
}
