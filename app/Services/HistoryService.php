<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbstractModel;
use App\Models\History;
use App\Repositories\HistoryRepository;

readonly class HistoryService
{
    /** @var \App\Repositories\HistoryRepository */
    private HistoryRepository $historyRepository;

    /**
     * @param \App\Repositories\HistoryRepository|null $historyRepository
     */
    public function __construct(
        ?HistoryRepository $historyRepository = null,
    ) {
        $this->historyRepository = $historyRepository ?? HistoryRepository::getInstance();
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     */
    public function saveHistory(AbstractModel $model): void
    {
        if ($model->getIsHistorifiable()) {
            if ($model->getIsNew()) {
                $changedData = $model->toArray();
            } else {
                $changedData = array_diff($model->toArray(), $model->getOrigData());
            }
            if (count($changedData)) {
                $history = new History([
                    History::ENTITY_TYPE => $model::class,
                    History::ENTITY_ID => $model->getId(),
                ]);
                $history->setChangedData($changedData);
                $this->historyRepository->save($history);
            }
        }
    }
}
