<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\NoSuchEntityException;
use app\Interfaces\Data\HistoryInterface;
use App\Models\AbstractModel;
use App\Models\History;

class HistoryRepository extends AbstractRepository
{
    /** @var \app\Interfaces\Data\HistoryInterface[] */
    private array $entitiesLast = [];

    /**
     * @param \app\Interfaces\Data\HistoryInterface|\App\Models\History $history
     * @return \app\Interfaces\Data\HistoryInterface
     * @throws \App\Exceptions\EntitySaveException
     */
    public function save(HistoryInterface|History $history): HistoryInterface
    {
        $this->saveModel($history);

        $key = History::ENTITY_TYPE . '_' . History::ENTITY_ID;
        $values = $history->getEntityType() . '_' . $history->getEntityId();

        $this->entitiesLast[$key][$values] = $history;

        return $history;
    }

    /**
     * @param \App\Models\AbstractModel $historifiableEntity
     * @param bool $forceReload
     * @return \app\Interfaces\Data\HistoryInterface
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function getLastByEntity(AbstractModel $historifiableEntity, bool $forceReload = false): HistoryInterface
    {
        $key = History::ENTITY_TYPE . '_' . History::ENTITY_ID;
        $values = $historifiableEntity::class . '_' . $historifiableEntity->getId();
        if (!isset($this->entitiesLast[$key][$values]) || $forceReload) {
            $history = new History();
            $this->loadModel(
                $history,
                [$historifiableEntity::class, $historifiableEntity->getId()],
                [History::ENTITY_TYPE, History::ENTITY_ID]
            );
            if (!$history->getId()) {
                throw new NoSuchEntityException(sprintf(
                    'There is no History with Entity Type %s and Entity Id %s',
                    $historifiableEntity::class,
                    $historifiableEntity->getId(),
                ));
            }
            $this->entitiesLast[$key][$values] = $history;
        }

        return $this->entitiesLast[$key][$values];
    }
}
