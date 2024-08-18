<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Exceptions\EntitySaveException;
use App\Models\AbstractModel;
use App\Repositories\AbstractRepository;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

class AbstractRepositoryTest extends MockeryTestCase
{
    private AbstractRepository & MockInterface $repository;
    private PDO & MockInterface $pdoMock;
    private LoggerInterface & MockInterface $loggerMock;
    private PDOStatement & MockInterface $statement;
    private AbstractModel & MockInterface $modelMock;

    /**
     * @return void
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->statement = Mockery::mock(PDOStatement::class);
        $this->pdoMock = Mockery::mock(PDO::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->repository = Mockery::mock(AbstractRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
        ;

        $this->repository
            ->allows('validateFields')
            ->andReturnTrue();

        $this->modelMock = Mockery::mock(AbstractModel::class)->makePartial();
        $this->modelMock->allows('toArray')->andReturns(['name' => 'Alex']);
        $this->modelMock->allows('getTableName')->andReturns('table_name');

        $this->setPrivateProperty(
            $this->repository,
            get_parent_class($this->repository),
            'logger',
            $this->loggerMock
        );
        $this->setPrivateProperty(
            $this->repository,
            get_parent_class($this->repository),
            'connection',
            $this->pdoMock
        );
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
     * @throws \ReflectionException
     */
    public function testSaveModelInsert(): void
    {
        $this->modelMock->allows('getId')->andReturns(0);

        $this->repository
            ->allows('updateModel')->never();

        $this->pdoMock->expects('inTransaction')->andReturns(false);
        $this->pdoMock->expects('beginTransaction')->andReturns(true);

        $this->pdoMock->expects('prepare')->andReturns($this->statement);
        $this->statement->expects('execute');

        $this->pdoMock->allows('lastInsertId')->andReturns(1);

        $this->repository
            ->expects('loadModel')
            ->once()
            ->with($this->modelMock, $this->pdoMock->lastInsertId())
            ->andReturns($this->modelMock);

        $this->modelMock->expects('setIsNew')->with(true);

        $this->repository
            ->expects('saveCommitBefore')
            ->with($this->modelMock);

        $this->pdoMock->allows('commit')->andReturns(true);
        $result = $this->invokeNonPublicMethod($this->repository, 'saveModel', $this->modelMock);

        $this->assertSame($this->modelMock, $result);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSaveModelUpdate(): void
    {
        $this->modelMock->allows('getId')->andReturns(1);

        $this->repository
            ->expects('updateModel')
            ->with($this->modelMock)
            ->andReturns($this->modelMock);

        $result = $this->invokeNonPublicMethod($this->repository, 'saveModel', $this->modelMock);

        $this->assertSame($this->modelMock, $result);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateModel(): void
    {
        $this->modelMock->allows('getId')->andReturns(1);

        $this->pdoMock->expects('inTransaction')->once()->andReturns(false);
        $this->pdoMock->expects('beginTransaction')->once()->andReturns(true);

        $this->pdoMock->expects('prepare')->once()->andReturns($this->statement);
        $this->statement->expects('execute')->once();

        $this->repository
            ->expects('saveCommitBefore')
            ->with($this->modelMock)
            ->once()
        ;

        $this->pdoMock->allows('commit')->andReturns(true);
        $this->modelMock->expects('setOrigData')->once();

        $result = $this->invokeNonPublicMethod($this->repository, 'updateModel', $this->modelMock);

        $this->assertSame($this->modelMock, $result);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSaveModelException(): void
    {
        $this->modelMock->allows('getId')->andReturns(0);

        $this->pdoMock->allows('inTransaction')->andReturns(false);
        $this->pdoMock->expects('beginTransaction')->andReturns(true);

        $this->pdoMock->expects('prepare')->andThrow(new Exception());
        $this->pdoMock->expects('rollBack')->once();
        $this->loggerMock->expects('error')->once();

        $this->expectException(EntitySaveException::class);

        $this->invokeNonPublicMethod($this->repository, 'saveModel', $this->modelMock);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testDeleteModel(): void
    {
        $this->modelMock->allows('getId')->andReturns(1);

        $this->pdoMock->expects('prepare')->once()->andReturns($this->statement);
        $this->statement->expects('execute')
            ->once()
            ->with([
                ':id' => $this->modelMock->getId()
            ]);

        $this->invokeNonPublicMethod($this->repository, 'deleteModel', $this->modelMock);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testInsertFromArray(): void
    {
        $maxLinesPerRequest = $this->invokeNonPublicMethod($this->repository, 'getMaxInsertRows');
        $dataToInsert = array_fill(0, 1500, ['name', 'email']);
        $chunksCount = (int) ceil(count($dataToInsert) / $maxLinesPerRequest);

        $this->pdoMock->expects('prepare')->times($chunksCount)->andReturns($this->statement);
        $this->statement->expects('execute')->times($chunksCount);

        $this->invokeNonPublicMethod($this->repository, 'insertFromArray', $dataToInsert, 'table_name');
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testInsertFromArrayException(): void
    {
        $dataToInsert = [
            ['name', 'email']
        ];

        $this->pdoMock->expects('prepare')->andThrow(new Exception());
        $this->loggerMock->expects('error')->once();

        $this->expectException(EntitySaveException::class);

        $this->invokeNonPublicMethod($this->repository, 'insertFromArray', $dataToInsert, 'table_name');
    }

    /**
     * Protected/private methods invoker
     *
     * @param object $object
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     * @throws \ReflectionException
     */
    private function invokeNonPublicMethod(object $object, string $method, ...$args): mixed
    {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invoke($object, ...$args);
    }

    /**
     * Private properties setter
     *
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