<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\EntitySaveException;
use App\Exceptions\HttpRequestException;
use App\Models\ForbiddenEmailDomain;
use App\Repositories\ForbiddenEmailDomainRepository;
use Framework\Http;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DisposableEmailDomainService
{
    /** @var string */
    private const DISPOSABLE_LIST = 'https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.txt';
    private const REASON_DEFAULT = 'Disposable Email Domain';

    /** @var \Psr\Log\LoggerInterface */
    private readonly LoggerInterface $logger;

    /** @var \App\Repositories\ForbiddenEmailDomainRepository */
    private readonly ForbiddenEmailDomainRepository $domainRepository;

    /** @var \Framework\Http */
    private readonly Http $http;

    /**
     * @param \Psr\Log\LoggerInterface|null $logger
     * @param \App\Repositories\ForbiddenEmailDomainRepository|null $forbiddenEmailDomainRepository
     * @param \Framework\Http|null $http
     */
    public function __construct(
        ?LoggerInterface $logger = null,
        ?ForbiddenEmailDomainRepository $forbiddenEmailDomainRepository = null,
        ?Http $http = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->domainRepository = $forbiddenEmailDomainRepository ?? ForbiddenEmailDomainRepository::getInstance();
        $this->http = $http ?? new Http();
    }

    /**
     * Download and update list of Disposable Email Domains
     *
     * @return void
     */
    public function updateDomainsList(): void
    {
        $data = $this->downloadList();
        if ($data) {
            $data = explode(PHP_EOL, $data);
            $data = array_filter($data);
            $data = array_unique($data);
            $data = array_map(
                fn(string $row): array => [
                    ForbiddenEmailDomain::REASON => self::REASON_DEFAULT,
                    ForbiddenEmailDomain::DOMAIN => $row,
                ],
                $data
            );

            try {
                $this->domainRepository->massInsert($data);
            } catch (EntitySaveException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * @return string|null
     */
    private function downloadList(): string|null
    {
        try {
            $result = $this->http->get(self::DISPOSABLE_LIST);
        } catch (HttpRequestException $e) {
            $this->logger->error(sprintf(
                'Unable to fetch Disposable Email Domains list, error: %s',
                $e->getMessage()
            ));
            $result = null;
        }

        return $result;
    }
}
