<?php

declare(strict_types=1);

namespace App\Interfaces;

use Carbon\Carbon;

/**
 * Interfaces for Entities which can be soft deleted
 */
interface SoftDeletableInterface
{
    /** @var string */
    public const DELETED = 'deleted';

    /**
     * Get the deletion date of the user.
     *
     * @return Carbon|null
     */
    public function getDeleted(): ?Carbon;

    /**
     * Set the deletion date of the user.
     *
     * @param Carbon|string|null $deleted
     * @return static
     */
    public function setDeleted(Carbon|string|null $deleted): static;

    /**
     * Check if Entity is Soft Deleted
     *
     * @return bool
     */
    public function getIsDeleted(): bool;
}