<?php

declare(strict_types=1);

namespace App\Interfaces\Data;

use Carbon\Carbon;

interface UserInterface
{
    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set the user's ID.
     *
     * @param int $id
     * @return static
     */
    public function setId(int $id): static;

    /**
     * Get the user's name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set the user's name.
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static;

    /**
     * Get the user's email.
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Set the user's email.
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email): static;

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

    /**
     * Get the user's notes.
     *
     * @return string|null
     */
    public function getNotes(): ?string;

    /**
     * Set the user's notes.
     *
     * @param string|null $notes
     * @return static
     */
    public function setNotes(?string $notes): static;
}
