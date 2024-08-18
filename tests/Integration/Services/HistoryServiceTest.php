<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Exceptions\NoSuchEntityException;
use App\Models\AbstractModel;
use App\Repositories\HistoryRepository;
use App\Services\HistoryService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class HistoryServiceTest extends MockeryTestCase
{
    private HistoryService $historyService;
    private HistoryRepository $historyRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->historyRepository = HistoryRepository::getInstance();
        $this->historyService = new HistoryService($this->historyRepository);
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
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testSaveHistoryForNewHistorifiableModel(): void
    {
        $uniqueFieldsValue = $this->getUniqueString();

        $modelMock = Mockery::mock(AbstractModel::class);
        $modelMock->allows('getIsHistorifiable')->andReturns(true);
        $modelMock->allows('getIsNew')->andReturns(true);
        $modelMock->allows('toArray')->andReturns(['field' => $uniqueFieldsValue]);
        $modelMock->allows('getId')->andReturns(1);

        $this->historyService->saveHistory($modelMock);

        $historyModel = $this->historyRepository->getLastByEntity($modelMock);
        $this->assertSame($historyModel->getEntityType(), $modelMock::class);
        $this->assertSame($historyModel->getEntityId(), $modelMock->getId());
        $this->assertEqualsCanonicalizing(
            json_decode($historyModel->getChangedData(), true),
            $modelMock->toArray()
        );
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \Random\RandomException
     */
    public function testSaveHistoryForNewNonHistorifiableModel(): void
    {
        $uniqueFieldsValue = $this->getUniqueString();

        $modelMock = Mockery::mock(AbstractModel::class);
        $modelMock->allows('getIsHistorifiable')->andReturns(false);
        $modelMock->allows('getIsNew')->andReturns(true);
        $modelMock->allows('toArray')->andReturns(['field' => $uniqueFieldsValue]);
        $modelMock->allows('getId')->andReturns(1);

        $this->historyService->saveHistory($modelMock);

        try {
            $historyModel = $this->historyRepository->getLastByEntity($modelMock);
        } catch (NoSuchEntityException $e) {
            $historyModel = null;
        }

        if ($historyModel) {
            $this->assertNotEqualsCanonicalizing(
                json_decode($historyModel->getChangedData(), true),
                $modelMock->toArray()
            );
        } else {
            $this->assertNull($historyModel);
        }
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     * @throws \Random\RandomException
     */
    public function testSaveHistoryForExistingHistorifiableModel(): void
    {
        $uniqueFieldsValue = $this->getUniqueString();

        $modelMock = Mockery::mock(AbstractModel::class);
        $modelMock->allows('getIsHistorifiable')->andReturns(true);
        $modelMock->allows('getIsNew')->andReturns(false);
        $modelMock->allows('toArray')->andReturns(['field' => $uniqueFieldsValue]);
        $modelMock->allows('getOrigData')->andReturns(['field' => 'old_value']);
        $modelMock->allows('getId')->andReturns(1);

        $this->historyService->saveHistory($modelMock);

        $historyModel = $this->historyRepository->getLastByEntity($modelMock);
        $this->assertSame($historyModel->getEntityType(), $modelMock::class);
        $this->assertSame($historyModel->getEntityId(), $modelMock->getId());
        $this->assertEqualsCanonicalizing(
            json_decode($historyModel->getChangedData(), true),
            $modelMock->toArray()
        );
    }

    /**
     * @param int $bytesLength
     * @return string
     * @throws \Random\RandomException
     */
    private function getUniqueString(int $bytesLength = 64): string
    {
        return bin2hex(random_bytes($bytesLength));
    }
}