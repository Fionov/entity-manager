<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\IncorrectDataException;
use App\Interfaces\ValidatorInterface;
use App\Models\AbstractModel;
use App\Services\ValidatorMapperService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ValidatorMapperServiceTest extends MockeryTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
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
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetForModelExistingValidator()
    {
        $mockModel = Mockery::mock(AbstractModel::class);
        $mockModelClass = get_class($mockModel);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $mapping = [
            $mockModelClass => get_class($mockValidator),
        ];
        $validatorMapperService = new ValidatorMapperService(mapping: $mapping);
        $result = $validatorMapperService->getForModel($mockModel);

        $this->assertInstanceOf(ValidatorInterface::class, $result);
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetForModelNotExistingValidator()
    {
        $mockModel = $this->createMock(AbstractModel::class);
        $mockModelClass = get_class($mockModel);

        $validatorService = new ValidatorMapperService(mapping: []);

        $this->expectException(IncorrectDataException::class);
        $this->expectExceptionMessage(sprintf('Unable to get Validator for type %s', $mockModelClass));

        $validatorService->getForModel($mockModel);
    }
}