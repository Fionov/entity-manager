<?php

declare(strict_types=1);

namespace App\Interfaces\Data;

use Carbon\Carbon;

interface HistoryInterface
{
    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set the ID.
     *
     * @param int $id
     * @return static
     */
    public function setId(int $id): static;

    /**
     * Get the entity ID.
     *
     * @return int
     */
    public function getEntityId(): int;

    /**
     * Set the entity ID.
     *
     * @param int $entityId
     * @return static
     */
    public function setEntityId(int $entityId): static;

    /**
     * Get the entity type.
     *
     * @return string
     */
    public function getEntityType(): string;

    /**
     * Set the entity type.
     *
     * @param string $entityType
     * @return static
     */
    public function setEntityType(string $entityType): static;

    /**
     * Get the changed data.
     *
     * @return string
     */
    public function getChangedData(): string;

    /**
     * Set the changed data.
     *
     * @param array|string $changedData
     * @return static
     */
    public function setChangedData(array|string $changedData): static;

    /**
     * Get the creation date of the user.
     *
     * @return Carbon
     */
    public function getCreated(): Carbon;

    /**
     * Set the creation date of the user.
     *
     * @param Carbon $created
     * @return static
     */
    public function setCreated(Carbon $created): static;
}