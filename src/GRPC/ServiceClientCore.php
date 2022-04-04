<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Spiral\Core\CoreInterface;

class ServiceClientCore extends \Grpc\BaseStub implements CoreInterface
{
    public function callAction(string $controller, string $action, array $parameters = [])
    {
        /** @var RequestContext $ctx */
        $ctx = $parameters['ctx'];

        return $this->_simpleRequest(
            $action,
            $parameters['in'],
            [$parameters['responseClass'], 'decode'],
            (array) $ctx->getValue('metadata'),
            (array) $ctx->getValue('options'),
        )->wait();
    }
}
