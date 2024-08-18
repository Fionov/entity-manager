<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\Data\ForbiddenEmailDomainInterface;
use Carbon\Carbon;

class ForbiddenEmailDomain extends AbstractModel implements ForbiddenEmailDomainInterface
{
    /** @var string */
    public const TABLE_NAME = 'forbidden_email_domains';

    /** @var string */
    public const ID = 'id';
    public const DOMAIN = 'domain';
    public const REASON = 'reason';
    public const CREATED = 'created';
    public const UPDATED = 'updated';

    /**
     * Get the domain.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return (string) $this->getData(self::DOMAIN);
    }

    /**
     * Set the domain.
     *
     * @param string $domain
     * @return static
     */
    public function setDomain(string $domain): static
    {
        return $this->setData(self::DOMAIN, $domain);
    }

    /**
     * Get the reason.
     *
     * @return string
     */
    public function getReason(): string
    {
        return (string) $this->getData(self::REASON);
    }

    /**
     * Set the reason.
     *
     * @param string $reason
     * @return static
     */
    public function setReason(string $reason): static
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * Get the updated date and time.
     *
     * @return Carbon
     */
    public function getUpdated(): Carbon
    {
        return new Carbon($this->getData(self::UPDATED));
    }
}