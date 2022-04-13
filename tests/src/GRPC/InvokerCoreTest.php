<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Tests\GRPC;

use Mockery as m;
use Spiral\RoadRunner\GRPC\Context;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\GRPC\Method;
use VendorName\Skeleton\GRPC\InvokerCore;
use VendorName\Skeleton\Tests\App\Service\UserService;
use VendorName\Skeleton\Tests\TestCase;

final class InvokerCoreTest extends TestCase
{
    public function testInvokerShouldBeInvoked(): void
    {
        $core = new InvokerCore(
            $invoker = m::mock(InvokerInterface::class)
        );

        $class = new \ReflectionClass(UserService::class);
        $params = [
            'service' => $service = $class->newInstance(),
            'method'  => $method = Method::parse($class->getMethod('get')),
            'ctx' => $ctx = new Context(['foo' => 'bar']),
            'input' => $input = null
        ];

        $invoker->shouldReceive('invoke')
            ->with($service, $method, $ctx, $input)
            ->once()
            ->andReturn($response = 'baz');


        $this->assertSame(
            $response,
            $core->callAction('foo', 'bar', $params)
        );
    }
}
