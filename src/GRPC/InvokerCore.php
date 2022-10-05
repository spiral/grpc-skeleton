<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Spiral\Core\CoreInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\GRPC\Method;
use Spiral\RoadRunner\GRPC\ServiceInterface;

class InvokerCore implements CoreInterface
{
    public function __construct(
        private readonly InvokerInterface $invoker,
    ) {
    }

    /**
     * @param array{service: ServiceInterface, method: Method, ctx: ContextInterface, input: ?string} $parameters
     */
    public function callAction(string $controller, string $action, array $parameters = [])
    {
        return $this->invoker->invoke(
            $parameters['service'],
            $parameters['method'],
            $parameters['ctx'],
            $parameters['input'],
        );
    }
}
