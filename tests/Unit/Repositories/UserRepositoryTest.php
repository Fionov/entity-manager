<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\NoSuchEntityException;
use App\Interfaces\Data\UserInterface;
use App\Models\User;
use App\Repositories\UserRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use ReflectionClass;

use function PHPUnit\Framework\once;

class UserRepositoryTest extends MockeryTestCase
{
    private UserRepository & MockInterface $repository;
    private User & MockInterface $modelMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UserRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->modelMock = Mockery::mock(User::class);
        $this->modelMock->allows('getId')->andReturn(1);
        $this->modelMock->allows('getEmail')->andReturn('test@test.com');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByIdWithoutCache(): void
    {
        $this->repository->expects('loadModel')
            ->once()
            ->andReturnUsing(function(User $user) {
                $user->setId($this->modelMock->getId());

                return $user;
            });

        $result = $this->repository->getById($this->modelMock->getId());

        $this->assertSame($this->modelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testGetByIdWithCacheForced(): void
    {
        $this->repository->expects('loadModel')
            ->once()
            ->andReturnUsing(function(User $user) {
                $user->setId($this->modelMock->getId());

                return $user;
            });

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entities', [
            User::ID => [$this->modelMock->getId() => $this->modelMock]
        ]);

        $this->repository->getById($this->modelMock->getId(), true);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testGetByIdWithCache(): void
    {
        $this->repository->expects('loadModel')
            ->never();

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entities', [
            User::ID => [$this->modelMock->getId() => $this->modelMock]
        ]);

        $result = $this->repository->getById($this->modelMock->getId());

        $this->assertSame($this->modelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByIdException(): void
    {
        $this->repository->expects('loadModel')
            ->once();

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById($this->modelMock->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByEmailWithoutCache(): void
    {
        $this->repository->expects('loadModel')
            ->once()
            ->andReturnUsing(function(User $user) {
                $user->setId($this->modelMock->getId());

                return $user;
            });

        $result = $this->repository->getByEmail($this->modelMock->getEmail());

        $this->assertSame($this->modelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testGetByEmailWithCacheForced(): void
    {
        $this->repository->expects('loadModel')
            ->once()
            ->andReturnUsing(function(User $user) {
                $user->setId($this->modelMock->getId());

                return $user;
            });

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entities', [
            User::EMAIL => [$this->modelMock->getEmail() => $this->modelMock]
        ]);

        $this->repository->getByEmail($this->modelMock->getEmail(), true);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testGetByEmailWithCache(): void
    {
        $this->repository->expects('loadModel')
            ->never();

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entities', [
            User::EMAIL => [$this->modelMock->getEmail() => $this->modelMock]
        ]);

        $result = $this->repository->getByEmail($this->modelMock->getEmail());

        $this->assertSame($this->modelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByEmailException(): void
    {
        $this->repository->expects('loadModel')
            ->once();

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByEmail($this->modelMock->getEmail());
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \ReflectionException
     */
    public function testSaveAndPutCache(): void
    {
        $this->repository->expects('saveModel')
            ->once()
            ->with($this->modelMock)
            ->andReturn($this->modelMock);

        $result = $this->repository->save($this->modelMock);

        $this->assertSame($this->modelMock, $result);
        $entities = $this->getPrivateProperty($this->repository, 'entities', get_parent_class($this->repository));

        $this->assertArrayHasKey(User::ID, $entities);
        $this->assertArrayHasKey(User::EMAIL, $entities);
        $this->assertSame($this->modelMock, $entities[User::ID][$this->modelMock->getId()]);
        $this->assertSame($this->modelMock, $entities[User::EMAIL][$this->modelMock->getEmail()]);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testDeleteById(): void
    {
        $this->repository
            ->expects('getById')
            ->once()
            ->with($this->modelMock->getId())
            ->andReturn($this->modelMock);

        $this->repository
            ->expects('deleteModel')
            ->once()
            ->with($this->modelMock, true)
            ->andReturnTrue();

        $this->repository->deleteById($this->modelMock->getId());
    }

    /**
     * @return void
     */
    public function testGetCollectionReturnUsers(): void
    {
        $expectedUsers = [
            Mockery::mock(UserInterface::class),
            Mockery::mock(UserInterface::class),
        ];

        $where = ['id', '>', 5];
        $limit = 15;
        $offset = 10;
        $ordering = [User::ID => 'DESC'];

        $this->repository->expects('loadCollection')
            ->with(
                User::class,
                User::getTableName(),
                ['*'],
                $where,
                $ordering,
                $limit,
                $offset
            )
            ->andReturns($expectedUsers);

        $result = $this->repository->getCollection(where: $where, limit: $limit, offset: $offset, ordering: $ordering);
        $this->assertEquals($expectedUsers, $result);
    }


    /**
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws \ReflectionException
     */
    private function setPrivateProperty(object $object, string $class, string $property, mixed $value): void
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($property);
        $property->setValue($object, $value);
    }

    /**
     * @param object $object
     * @param string $property
     * @param string $class
     * @return mixed
     * @throws \ReflectionException
     */
    private function getPrivateProperty(object $object, string $property, string $class): mixed
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($property);

        return $property->getValue($object);
    }
}