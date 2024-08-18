<?php

namespace Tests\Unit\Services;

use App\Exceptions\HttpRequestException;
use App\Repositories\ForbiddenEmailDomainRepository;
use App\Services\DisposableEmailDomainService;
use Framework\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class DisposableEmailDomainServiceTest extends MockeryTestCase
{
    private DisposableEmailDomainService $disposableEmailDomainService;
    private LoggerInterface & MockInterface $loggerMock;
    private ForbiddenEmailDomainRepository & MockInterface $domainRepositoryMock;
    private Http & MockInterface $httpMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->domainRepositoryMock = Mockery::mock(ForbiddenEmailDomainRepository::class);

        $this->httpMock = Mockery::mock(Http::class);

        $this->disposableEmailDomainService = new DisposableEmailDomainService(
            logger: $this->loggerMock,
            forbiddenEmailDomainRepository: $this->domainRepositoryMock,
            http: $this->httpMock,
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testUpdateDomainsListSuccess()
    {
        $this->httpMock->allows('get')->andReturn('domain.test');
        $this->domainRepositoryMock->expects('massInsert')->once();

        $this->disposableEmailDomainService->updateDomainsList();
    }

    /**
     * @return void
     */
    public function testUpdateDomainsListError()
    {
        $errorText = 'Unable to fetch data';

        $this->httpMock->allows('get')->andThrowExceptions([new HttpRequestException($errorText)]);
        $this->domainRepositoryMock->expects('massInsert')->never();
        $this->loggerMock->expects('error')->with($this->stringContains($errorText))->once();

        $this->disposableEmailDomainService->updateDomainsList();
    }
}
