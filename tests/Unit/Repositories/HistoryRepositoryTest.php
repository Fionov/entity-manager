<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\NoSuchEntityException;
use App\Models\History;
use App\Models\User;
use App\Repositories\HistoryRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use ReflectionClass;

class HistoryRepositoryTest extends MockeryTestCase
{
    private HistoryRepository & MockInterface $repository;
    private History & MockInterface $historyModelMock;
    private User & MockInterface $modelMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(HistoryRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->historyModelMock = Mockery::mock(History::class);
        $this->historyModelMock->allows('getEntityType')->andReturn('some_entity_type');
        $this->historyModelMock->allows('getEntityId')->andReturn(2);
        $this->historyModelMock->allows('getId')->andReturn(1);

        $this->modelMock = Mockery::mock(User::class);
        $this->modelMock->allows('getId')->andReturn(1);
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
     * @throws \App\Exceptions\EntitySaveException
     * @throws \ReflectionException
     */
    public function testSaveAndPutCache(): void
    {
        $this->repository->expects('saveModel')
            ->once()
            ->with($this->historyModelMock)
            ->andReturn($this->historyModelMock);

        $result = $this->repository->save($this->historyModelMock);

        $this->assertSame($this->historyModelMock, $result);
        $entities = $this->getPrivateProperty($this->repository, 'entitiesLast', get_parent_class($this->repository));

        $key = History::ENTITY_TYPE . '_' . History::ENTITY_ID;
        $values = $this->historyModelMock->getEntityType() . '_' . $this->historyModelMock->getEntityId();

        $this->assertArrayHasKey($key, $entities);
        $this->assertArrayHasKey($values, $entities[$key]);
        $this->assertSame($this->historyModelMock, $entities[$key][$values]);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetLastByEntityWithoutCache(): void
    {
        $this->repository->expects('loadModel')
            ->andReturnUsing(function(History $loadedHistory) {
                $loadedHistory->setId($this->historyModelMock->getId());

                return $loadedHistory;
            });

        $result = $this->repository->getLastByEntity($this->modelMock);
        $this->assertSame($this->historyModelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testGetByEntityWithCache(): void
    {
        $key = History::ENTITY_TYPE . '_' . History::ENTITY_ID;
        $values = $this->modelMock::class . '_' . $this->modelMock->getId();

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entitiesLast', [
            $key => [$values => $this->historyModelMock]
        ]);
        $this->repository->expects('loadModel')->never();
        $result = $this->repository->getLastByEntity($this->modelMock);

        $this->assertSame($this->historyModelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testGetByEntityWithCacheForced(): void
    {
        $key = History::ENTITY_TYPE . '_' . History::ENTITY_ID;
        $values = $this->modelMock::class . '_' . $this->modelMock->getId();

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entitiesLast', [
            $key => [$values => $this->historyModelMock]
        ]);
        $this->repository->expects('loadModel')
            ->andReturnUsing(function(History $loadedHistory) {
                $loadedHistory->setId($this->historyModelMock->getId());

                return $loadedHistory;
            });
        $result = $this->repository->getLastByEntity($this->modelMock, true);

        $this->assertSame($this->historyModelMock->getId(), $result->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function testGetByEntityNotFoundException(): void
    {
        $this->repository->expects('loadModel')->once();

        $this->expectException(NoSuchEntityException::class);
        $result = $this->repository->getLastByEntity($this->modelMock);

        $this->assertSame($this->historyModelMock->getId(), $result->getId());
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