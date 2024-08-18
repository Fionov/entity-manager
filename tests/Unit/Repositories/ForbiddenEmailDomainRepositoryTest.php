<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\NoSuchEntityException;
use App\Models\ForbiddenEmailDomain;
use App\Repositories\ForbiddenEmailDomainRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use ReflectionClass;

class ForbiddenEmailDomainRepositoryTest extends MockeryTestCase
{
    private ForbiddenEmailDomainRepository & MockInterface $repository;
    private ForbiddenEmailDomain & MockInterface $modelMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ForbiddenEmailDomainRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->modelMock = Mockery::mock(ForbiddenEmailDomain::class);
        $this->modelMock->allows('getId')->andReturn(1);
        $this->modelMock->allows('getDomain')->andReturn('test.com');
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
    public function testGetByEmailWithoutCache(): void
    {
        $this->repository->expects('loadModel')
            ->once()
            ->andReturnUsing(function(ForbiddenEmailDomain $forbiddenEmailDomain) {
                $forbiddenEmailDomain->setId($this->modelMock->getId());

                return $forbiddenEmailDomain;
            });

        $result = $this->repository->getByDomain($this->modelMock->getDomain());

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
            ->andReturnUsing(function(ForbiddenEmailDomain $forbiddenEmailDomainser) {
                $forbiddenEmailDomainser->setId($this->modelMock->getId());

                return $forbiddenEmailDomainser;
            });

        $this->setPrivateProperty($this->repository, get_parent_class($this->repository), 'entities', [
            ForbiddenEmailDomain::DOMAIN => [$this->modelMock->getDomain() => $this->modelMock]
        ]);

        $this->repository->getByDomain($this->modelMock->getDomain(), true);
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
            ForbiddenEmailDomain::DOMAIN => [$this->modelMock->getDomain() => $this->modelMock]
        ]);

        $result = $this->repository->getByDomain($this->modelMock->getDomain());

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
        $this->repository->getByDomain($this->modelMock->getDomain());
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
}