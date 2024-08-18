<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\NoSuchEntityException;
use app\Interfaces\Data\ForbiddenEmailDomainInterface;
use App\Models\ForbiddenEmailDomain;

class ForbiddenEmailDomainRepository extends AbstractRepository
{
    /** @var \app\Interfaces\Data\ForbiddenEmailDomainInterface[] */
    private array $entities = [];

    /**
     * @param string $domain
     * @param bool $forceReload
     * @return \app\Interfaces\Data\ForbiddenEmailDomainInterface
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function getByDomain(string $domain, bool $forceReload = false): ForbiddenEmailDomainInterface
    {
        if (!isset($this->entities[ForbiddenEmailDomain::DOMAIN][$domain]) || $forceReload) {
            $forbiddenDomain = new ForbiddenEmailDomain();
            $this->loadModel($forbiddenDomain, $domain, ForbiddenEmailDomain::DOMAIN);
            if (!$forbiddenDomain->getId()) {
                throw new NoSuchEntityException(sprintf(
                    'There is no Forbidden Domain with Domain %s',
                    $domain
                ));
            }
            $this->entities[ForbiddenEmailDomain::DOMAIN][$domain] = $forbiddenDomain;
        }

        return $this->entities[ForbiddenEmailDomain::DOMAIN][$domain];
    }

    /**
     * @param array $data
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     */
    public function massInsert(array $data): void
    {
        $this->insertFromArray($data, ForbiddenEmailDomain::getTableName());
    }
}
