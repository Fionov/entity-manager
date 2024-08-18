<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\Data\UserInterface;
use App\Interfaces\SoftDeletableInterface;
use Carbon\Carbon;

class User extends AbstractModel implements UserInterface, SoftDeletableInterface
{
    /** @var string */
    public const TABLE_NAME = 'users';

    /** @var string */
    public const ID = 'id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const CREATED = 'created';
    public const DELETED = 'deleted';
    public const NOTES = 'notes';

    /** @var bool */
    protected bool $isHistorifiable = true;

    /**
     * Get the user's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * Set the user's name.
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get the user's email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return (string) $this->getData(self::EMAIL);
    }

    /**
     * Set the user's email.
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email): static
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Get the user's notes.
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->getData(self::NOTES);
    }

    /**
     * Set the user's notes.
     *
     * @param string|null $notes
     * @return static
     */
    public function setNotes(?string $notes): static
    {
        return $this->setData(self::NOTES, $notes);
    }

    /**
     * Get the deletion date of the user.
     *
     * @return Carbon|null
     */
    public function getDeleted(): ?Carbon
    {
        return $this->getData(self::DELETED) !== null
            ? new Carbon($this->getData(self::DELETED))
            : null;
    }

    /**
     * Set the deletion date of the user.
     *
     * @param Carbon|string|null $deleted
     * @return static
     */
    public function setDeleted(Carbon|string|null $deleted): static
    {
        if (is_string($deleted)) {
            $deleted = new Carbon($deleted);
        }
        if ($deleted) {
            $deleted = $deleted->toDateTimeString();
        }

        return $this->setData(self::DELETED, $deleted);
    }

    /**
     * Check if Entity is Soft Deleted
     *
     * @return bool
     */
    public function getIsDeleted(): bool
    {
        return $this->getDeleted() === null;
    }
}