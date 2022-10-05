<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC\Interceptors;

use Spiral\Core\CoreInterceptorInterface;
use Spiral\Telemetry\TracerInterface;
use Spiral\Core\CoreInterface;
use VendorName\Skeleton\GRPC\RequestContext;

class TelemetryInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private readonly TracerInterface $tracer
    ) {
    }

    public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
    {
        if (isset($parameters['ctx']) and $parameters['ctx'] instanceof RequestContext) {
            $parameters['ctx']->withTelemetry($this->tracer->getContext());
        }

        return $core->callAction($controller, $action, $parameters);
    }
}
