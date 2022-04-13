<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Tests\App\Service;

use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

class UserService implements ServiceInterface
{
    public function get(ContextInterface $ctx, RequestGetUser $in): ResponseGetUser
    {
        return new ResponseGetUser([
            'id' => $in->getId(),
            'name' => 'John Doe',
            'email' => 'john.doe@site.com',
        ]);
    }
}
