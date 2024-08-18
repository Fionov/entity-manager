<?php

declare(strict_types=1);

namespace Tests\Unit\Validators\Models;

use App\Exceptions\IncorrectDataException;
use App\Exceptions\NoSuchEntityException;
use App\Models\User;
use App\Repositories\ForbiddenEmailDomainRepository;
use App\Validators\Models\UserValidator;
use Carbon\Carbon;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class UserValidatorTest extends MockeryTestCase
{
    private UserValidator $validator;
    private User & MockInterface $user;
    private ForbiddenEmailDomainRepository & MockInterface $domainRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = Mockery::mock(User::class);
        $this->domainRepository = Mockery::mock(ForbiddenEmailDomainRepository::class);
        $this->validator = new UserValidator($this->user, $this->domainRepository);
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
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testValidate(): void
    {
        $validator = Mockery::mock(UserValidator::class)
            ->makePartial();

        $validator->expects('deletedDate')->once();
        $validator->expects('name')->once();
        $validator->expects('email')->once();

        $validator->validate();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testDeletedDateSuccess(): void
    {
        $this->user->allows('getCreated')->andReturn(new Carbon('2024-01-01'));
        $this->user->allows('getDeleted')->andReturn(new Carbon('2024-01-02'));
        $this->addToAssertionCount(1);
        $this->validator->deletedDate();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testDeletedDateError(): void
    {
        $this->user->allows('getCreated')->andReturn(new Carbon('2024-01-02'));
        $this->user->allows('getDeleted')->andReturn(new Carbon('2024-01-01'));
        $this->expectException(IncorrectDataException::class);
        $this->validator->deletedDate();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testNameSuccess(): void
    {
        $this->user->allows('getName')->andReturn('user123456');
        $this->addToAssertionCount(1);
        $this->validator->name();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testNameErrorPattern(): void
    {
        $this->user->allows('getName')->andReturn('USER');
        $this->expectException(IncorrectDataException::class);
        $this->validator->name();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testNameErrorForbiddenWords(): void
    {
        $this->user->allows('getName')->andReturn('admin123456');
        $this->expectException(IncorrectDataException::class);
        $this->validator->name();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testEmailSuccess(): void
    {
        $this->user->allows('getEmail')->andReturn('user@domain.com');
        $this->domainRepository->allows('getByDomain')->andThrow(NoSuchEntityException::class);
        $this->addToAssertionCount(1);
        $this->validator->email();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testEmailErrorFormat(): void
    {
        $this->user->allows('getEmail')->andReturn('user@@domain.com');
        $this->domainRepository->allows('getByDomain')->andThrow(NoSuchEntityException::class);
        $this->expectException(IncorrectDataException::class);
        $this->validator->email();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function testDeletedEmailErrorDomainForbidden(): void
    {
        $this->user->allows('getEmail')->andReturn('user@domain.com');
        $this->domainRepository->allows('getByDomain');
        $this->expectException(IncorrectDataException::class);
        $this->validator->email();
    }
}
