<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Framework\DataObject;

/**
 * Abstract Model for DB entities
 */
class AbstractModel extends DataObject
{
    /** @var string */
    public const TABLE_NAME = '';

    /** @var string */
    public const ID = 'id';
    public const CREATED = 'created';

    /** @var string */
    public const PRIMARY_KEY = self::ID;

    /**
     * Original loaded data
     *
     * @var array
     */
    private array $origData = [];

    /**
     * New entities flag
     *
     * @var bool
     */
    private bool $isNew = false;

    /**
     * Entity changes should be tracked
     *
     * @var bool
     */
    protected bool $isHistorifiable = false;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return static::TABLE_NAME;
    }

    /**
     * @return string
     */
    public function getPk(): string
    {
        return static::PRIMARY_KEY;
    }

    /**
     * @return bool
     */
    public function getIsHistorifiable(): bool
    {
        return $this->isHistorifiable;
    }

    /**
     * @return bool
     */
    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @param bool $isNew
     * @return $this
     */
    public function setIsNew(bool $isNew): static
    {
        $this->isNew = $isNew;

        return $this;
    }

    /**
     * Get original loaded data
     *
     * @return array
     */
    public function getOrigData(): array
    {
        return $this->origData;
    }

    /**
     * Set original loaded data
     *
     * @param array $data
     * @return $this
     */
    public function setOrigData(array $data): static
    {
        $this->origData = $data;

        return $this;
    }

    /**
     * Get the user's ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getData($this->getPk());
    }

    /**
     * Set the user's ID.
     *
     * @param int $id
     * @return static
     */
    public function setId(int $id): static
    {
        return $this->setData(self::PRIMARY_KEY, $id);
    }

    /**
     * Get the creation date of the user.
     *
     * @return Carbon
     */
    public function getCreated(): Carbon
    {
        return new Carbon($this->getData(self::CREATED));
    }

    /**
     * Set the creation date of the user.
     *
     * @param Carbon|string $created
     * @return static
     */
    public function setCreated(Carbon|string $created): static
    {
        if (is_string($created)) {
            $created = new Carbon($created);
        }
        $created = $created->toDateTimeString();

        return $this->setData(self::CREATED, $created);
    }

    /**
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function setData(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->setOrigData($key);
        }

        return parent::setData($key, $value);
    }
}
