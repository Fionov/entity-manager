<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\Data\HistoryInterface;

class History extends AbstractModel implements HistoryInterface
{
    /** @var string */
    public const TABLE_NAME = 'history';

    /** @var string */
    public const ID = 'id';
    public const ENTITY_ID = 'entity_id';
    public const ENTITY_TYPE = 'entity_type';
    public const CHANGED_DATA = 'changed_data';
    public const CREATED = 'created';

    /**
     * Get the entity ID.
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Set the entity ID.
     *
     * @param int $entityId
     * @return static
     */
    public function setEntityId(int $entityId): static
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get the entity type.
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->getData(self::ENTITY_TYPE);
    }

    /**
     * Set the entity type.
     *
     * @param string $entityType
     * @return static
     */
    public function setEntityType(string $entityType): static
    {
        return $this->setData(self::ENTITY_TYPE, $entityType);
    }

    /**
     * Get the changed data.
     *
     * @return string
     */
    public function getChangedData(): string
    {
        return $this->getData(self::CHANGED_DATA);
    }

    /**
     * Set the changed data.
     *
     * @param array|string $changedData
     * @return static
     */
    public function setChangedData(array|string $changedData): static
    {
        if (is_array($changedData)) {
            $changedData = json_encode($changedData, JSON_UNESCAPED_UNICODE);
        }

        return $this->setData(self::CHANGED_DATA, $changedData);
    }
}