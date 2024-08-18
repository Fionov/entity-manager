<?php

declare(strict_types=1);

namespace App\Interfaces\Data;

use Carbon\Carbon;

interface ForbiddenEmailDomainInterface
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
     * Get the domain.
     *
     * @return string
     */
    public function getDomain(): string;

    /**
     * Set the domain.
     *
     * @param string $domain
     * @return static
     */
    public function setDomain(string $domain): static;

    /**
     * Get the reason.
     *
     * @return string
     */
    public function getReason(): string;

    /**
     * Set the reason.
     *
     * @param string $reason
     * @return static
     */
    public function setReason(string $reason): static;

    /**
     * Get the created date and time.
     *
     * @return Carbon
     */
    public function getCreated(): Carbon;

    /**
     * Set the created date and time.
     *
     * @param Carbon|string $created
     * @return static
     */
    public function setCreated(Carbon|string $created): static;

    /**
     * Get the updated date and time.
     *
     * @return Carbon
     */
    public function getUpdated(): Carbon;
}