<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\History;
use App\Models\User;
use App\Repositories\HistoryRepository;
use App\Services\HistoryService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class HistoryServiceTest extends MockeryTestCase
{
    private HistoryRepository & MockInterface $historyRepository;
    private User & MockInterface $modelMock;
    private HistoryService $historyService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelMock = Mockery::mock(User::class);
        $this->modelMock->allows('getIsNew')->andReturnTrue();
        $this->modelMock->allows('toArray')->andReturn(['key' => 'value']);
        $this->modelMock->allows('getId')->andReturn(0);

        $this->historyRepository = Mockery::mock(HistoryRepository::class);
        $this->historyService = new HistoryService(historyRepository: $this->historyRepository);
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
     */
    public function testSaveHistorySuccess(): void
    {
        $this->modelMock->allows('getIsHistorifiable')->andReturn(true);

        $this->historyRepository
            ->expects('save')
            ->with(History::class)
            ->once();

        $this->historyService->saveHistory($this->modelMock);
    }

    /**
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     */
    public function testSaveHistorySkip(): void
    {
        $this->modelMock->allows('getIsHistorifiable')->andReturn(false);

        $this->historyRepository
            ->expects('save')
            ->never();

        $this->historyService->saveHistory($this->modelMock);
    }
}
