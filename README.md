# GRPC skeleton

This repo can be used to create a shared package with common DTO and services contracts for microservices based on
Spiral Framework and RoadRunner.

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.0+
- [php-grpc](https://cloud.google.com/php/grpc) extension
- [Spiral App](https://github.com/spiral/app) with
  installed [RoadRunner bridge](https://github.com/spiral/roadrunner-bridge) package
- The [protobuf](https://grpc.io/docs/protoc-installation/) runtime library for compiling proto files

## Usage

1. Clone the repo to your local machine.
2. Run `php ./configure.php` to run a script that will replace all placeholders throughout all the files.
3. Run `composer install` to install all dependencies.
4. Download [`protoc-gen-php-grpc`](https://github.com/roadrunner-server/roadrunner/releases) plugin for compiling proto
   files.

```
./rr get-protoc-binary
```

5. Place proto files into `proto` directory. Example of structure you can see below.

```
- proto
   - api
      - users
        - v1
          - messages.proto
          - service.proto
      - roles
        - v1
          - messages.proto
          - service.proto
      - auth
        - v1
          - messages.proto
          - service.proto
      - common
        - v1
          - messages.proto
```

**Example of `service.proto`**

```protobuf
syntax = "proto3";

package api.users.v1;

option php_namespace = "Spiral\\Shared\\Services\\Users\\v1";
option php_metadata_namespace = "Spiral\\Shared\\Services\\Users\\v1\\GPBMetadata";

import "api/users/v1/message.proto";

service UserService {
    rpc List (api.users.v1.dto.UserListRequest) returns (api.users.v1.dto.UserListResponse) {
    }

    rpc Get (api.users.v1.dto.UserGetRequest) returns (api.users.v1.dto.UserGetResponse) {
    }

    rpc Register (api.users.v1.dto.UserRegisterRequest) returns (api.users.v1.dto.UserGetResponse) {
    }

    rpc Update (api.users.v1.dto.UserUpdateRequest) returns (api.users.v1.dto.UserGetResponse) {
    }

    rpc Delete (api.users.v1.dto.UserDeleteRequest) returns (api.users.v1.dto.UserDeleteResponse) {
    }
}
```

**Example of `messages.proto`**

```protobuf
syntax = "proto3";

package api.users.v1.dto;

option php_namespace = "Spiral\\Shared\\Services\\Users\\v1\\DTO";
option php_metadata_namespace = "Spiral\\Shared\\Services\\Users\\v1\\GPBMetadata";

import "google/protobuf/timestamp.proto";
import "api/common/v1/message.proto";

message User {
    int32 id = 1;
    string username = 2;
    string email = 3;
    google.protobuf.Timestamp created_at = 5;
}

message UserListRequest {
    int32 page = 1;
    int32 per_page = 2;
}

message UserListResponse {
    repeated User users = 1;
}

message UserGetRequest {
    int32 id = 1;
}

message UserGetResponse {
    User user = 1;
}

message UserRegisterRequest {
    string username = 1;
    string email = 2;
    string password = 3;
}

message UserUpdateRequest {
    int32 id = 1;
    string username = 2;
    string email = 3;
    string password = 4;
}

message UserDeleteRequest {
    int32 id = 2;
}

message UserDeleteResponse {

}
```

6. Set information about proto files to compile in `services.php` file.

```php
// services.php

<?php

declare(strict_types=1);

return [
    __DIR__ . '/proto/api/common/v1',
    __DIR__ . '/proto/api/users/v1',
    __DIR__ . '/proto/api/roles/v1',
    __DIR__ . '/proto/api/auth/v1',
];
```

7. Compile the proto files.

```
./rr compile-proto-files
```

or via docker

```
docker-compose up
```

The compiler will generate php files in your project according php namespaces you set in `proto` files and also register
services in the package bootloader.

** Example of generated files **

```
- src
  - Services
    - Users
      - v1
        - DTO                       // DTO classes
          - ...
        - GPBMetadata               // Protobuf metadata
          - ...
        - UserServiceInterface.php  // Service interface
        - UserServiceClient.php     // Service client
```

!!! Be careful with compiling proto files. Compiler will replace all previous compiled php files with new ones. !!!

8. Commit your changes.
9. Profit!

### Usage in your application

1. Add the package in your microservice `composer.json` file.

**Example**

```json
{
    ...,
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/my-company/my-package.git"
        }
    ],
    "require": {
        ...,
        "my-company/my-package": "*"
    },
    ...
}
```

2. Implement service interfaces in your microservice which should be handled by the application.

```
- src
  - Services
    - Users
      - UserService.php
```

**Example of `UserService.php`**

```php
<?php

declare(strict_types=1);

namespace App\Services\Users;

use Cycle\ORM\EntityManagerInterface;
use Google\Protobuf\Timestamp;
use Spiral\Auth\TokenInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\RoadRunner\GRPC;
use Spiral\Shared\Attributes\Guarded;
use Spiral\Shared\Attributes\InjectInterceptor;
use Spiral\Shared\GRPC\RequestContext;
use Spiral\Shared\Services\Common\v1\DTO\Pagination;
use Spiral\Shared\Services\Common\v1\DTO\Token;
use Spiral\Shared\Services\Users\v1\DTO;
use Spiral\Shared\Services\Users\v1\UserServiceInterface;

final class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em
    ) {
    }

    public function List(GRPC\ContextInterface $ctx, DTO\UserListRequest $in): DTO\UserListResponse
    {
        $users = $this->users->paginate($in->getPage(), $in->getPerPage());

        $response = new DTO\UserListResponse();
        $response->setUsers(
            \array_map(
                static fn (User $user) => UserDTOFactory::fromEntity($user),
                $users->items()
            )
        );

        return $response;
    }

    public function Get(GRPC\ContextInterface $ctx, DTO\UserGetRequest $in): DTO\UserGetResponse
    {
        $user = $this->users->getByPK($in->getId());

        return new DTO\UserGetResponse([
            'user' => UserDTOFactory::fromEntity($user)
        ]);
    }

    public function Register(GRPC\ContextInterface $ctx, DTO\UserRegisterRequest $in): DTO\UserGetResponse
    {
        $user = new User(
            $in->getUsername(),
            $in->getEmail(),
            $in->getPassword(),
        );

        $this->em->persist($user)->run();

        return new DTO\UserGetResponse([
            'user' => UserDTOFactory::fromEntity($user)
        ]);
    }


    #[Guarded]
    public function Update(GRPC\ContextInterface $ctx, DTO\UserUpdateRequest $in): DTO\UserGetResponse
    {
        $user = $this->users->getByPK($in->getId());

        $user->setUsername($in->getUsername());
        $user->setEmail($in->getEmail());
        $user->setPassword($in->getPassword());
        $this->em->persist($user)->run();

        return new DTO\UserGetResponse([
            'user' => UserDTOFactory::fromEntity($user)
        ]);
    }

    #[Guarded]
    public function Delete(GRPC\ContextInterface $ctx, DTO\UserDeleteRequest $in): DTO\UserDeleteResponse
    {
        $userId = (int) $ctx->getValue(TokenInterface::class)->getPayload()['id'];

        if (!$this->users->getByPK($userId)->isAdmin()) {
            throw new GRPC\Exception\GRPCException(
                'Only admins can delete users',
                GRPC\StatusCode::PERMISSION_DENIED
            );
        }

        $this->em->delete($this->users->getByPK($in->getId()))->run();

        return new DTO\UserDeleteResponse();
    }
}
```

3. Specify the service proto file in RoadRunner config file.

```yaml
grpc:
  listen: "tcp://0.0.0.0:9001"
  proto:
      - "./vendor/my-company/my-package/proto/api/users/v1/service.proto"
```

4. Specify the microservice hosts in application `.env` file.
You can get env variable names from the package Bootloader.

```dotenv
# GRPC microservice hosts
USERSERVICE_HOST=127.0.0.1:9001
ROLESSERVICE_HOST=127.0.0.1:9002
AUTHSERVICE_HOST=127.0.0.1:9003
```


### Usage a service client

The following example shows how to use a service client.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Carbon\Carbon;
use Spiral\Http\Request\InputManager;
use Spiral\Router\Annotation\Route;
use Spiral\Shared\GRPC\RequestContext;
use Spiral\Shared\Services\Users\v1\DTO\User;
use Spiral\Shared\Services\Users\v1\DTO\UserAuthRequest;
use Spiral\Shared\Services\Users\v1\DTO\UserDeleteRequest;
use Spiral\Shared\Services\Users\v1\DTO\UserGetRequest;
use Spiral\Shared\Services\Users\v1\DTO\UserListRequest;
use Spiral\Shared\Services\Users\v1\DTO\UserRegisterRequest;
use Spiral\Shared\Services\Users\v1\UserServiceInterface;
use Spiral\Shared\Services\Auth\v1\AuthServiceInterface;

class UsersController
{
    public function __construct(
        private UserServiceInterface $userService,
        private AuthServiceInterface $authService
    ) {
    }

    #[Route(route: 'users', name: 'user.list', methods: ['GET'])]
    public function index(InputManager $input): array
    {
        $response = $this->userService->List(
            new RequestContext(),
            new UserListRequest(['page' => (int) ($input->query('page') ?? 1), 'per_page' => 10])
        );

        return [
            'data' => \array_map(fn(User $user) => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'is_admin' => $user->getIsAdmin(),
                'created_at' => Carbon::createFromTimestamp($user->getCreatedAt()->getSeconds())->toDateTimeString(),
            ], \iterator_to_array(
                $response->getUsers()->getIterator()
            ))
        ];
    }

    #[Route(route: 'user/<id:\d+>', name: 'user.show', methods: ['GET'])]
    public function get(int $id): array
    {
        $user = $this->userService->Get(
            new RequestContext(),
            new UserGetRequest(['id' => $id])
        )->getUser();

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'is_admin' => $user->getIsAdmin(),
            'created_at' => Carbon::createFromTimestamp($user->getCreatedAt()->getSeconds())->toDateTimeString(),
        ];
    }

    #[Route(route: 'user/auth', name: 'user.auth', methods: ['POST'])]
    public function auth(InputManager $input): array
    {
        $response = $this->authService->Auth(
            new RequestContext(),
            new UserAuthRequest([
                'username' => $input->post('username'),
                'password' => $input->post('password')
            ])
        );

        $user = $response->getUser();
        $toke = $response->getToken();

        return [
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'is_admin' => $user->getIsAdmin(),
                'created_at' => Carbon::createFromTimestamp($user->getCreatedAt()->getSeconds())->toDateTimeString(),
            ],
            'token' => [
                'token' => $toke->getToken(),
                'expires_at' => Carbon::createFromTimestamp($toke->getExpiresAt()->getSeconds())->toDateTimeString(),
            ]
        ];
    }

    #[Route(route: 'user', name: 'user.register', methods: ['POST'])]
    public function register(InputManager $input): array
    {
        // Validate input data
        // ...

        $user = $this->userService->Register(
            (new RequestContext())->withToken($input->header('Authorization')),
            new UserRegisterRequest([
                'username' => $input->input('username'),
                'email' => $input->input('email'),
                'password' => $input->input('password'),
                'is_admin' => (bool) $input->input('is_admin'),
            ])
        )->getUser();

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'is_admin' => $user->getIsAdmin(),
            'created_at' => Carbon::createFromTimestamp($user->getCreatedAt()->getSeconds())->toDateTimeString(),
        ];
    }

    #[Route(route: 'user/<id:\d+>', name: 'user.delete', methods: ['DELETE'])]
    public function delete(InputManager $input, int $id): string
    {
        $this->userService->Delete(
            (new RequestContext())->withToken($input->header('Authorization')),
            new UserDeleteRequest(['id' => $id])
        );

        return 'OK';
    }
}
```

## PHPStorm setting

You may to install [Protobuf](https://plugins.jetbrains.com/plugin/16422-protobuf) plugin for PHPStorm and then mark
directory `proto` as `Source root` and will be activated autocomplete.

![screen](https://git.spiralscout.com/cpq/grpc-shared-package/uploads/cc3288063a869794d08907a1441a7a39/screen.png)



## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
