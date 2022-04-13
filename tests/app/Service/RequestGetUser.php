<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Tests\App\Service;

use Google\Protobuf\Internal\Message;

class RequestGetUser extends Message
{
    protected int $id = 0;

    public function getId()
    {
        return $this->id;
    }

    public function setId($var)
    {
        $this->id = $var;

        return $this;
    }
}
