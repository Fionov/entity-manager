<?php

declare(strict_types=1);

namespace App\Validators\Models;

use App\Exceptions\IncorrectDataException;
use App\Exceptions\NoSuchEntityException;
use app\Interfaces\Data\UserInterface;
use App\Interfaces\ValidatorInterface;
use App\Repositories\ForbiddenEmailDomainRepository;

class UserValidator implements ValidatorInterface
{
    /** @var string */
    private const NAME_PATTERN = '/^[a-z0-9]{8,64}$/';

    /** @var string[] */
    private const NAME_FORBIDDEN_WORDS = [
        'admin',
        'administrator',
        'moder',
        'moderator',
    ];

    /** @var \app\Interfaces\Data\UserInterface */
    private UserInterface $user;

    /** @var \App\Repositories\ForbiddenEmailDomainRepository */
    private ForbiddenEmailDomainRepository $forbiddenEmailDomainRepository;

    /**
     * @param \app\Interfaces\Data\UserInterface $user
     * @param \App\Repositories\ForbiddenEmailDomainRepository|null $forbiddenEmailDomainRepository
     */
    public function __construct(
        UserInterface $user,
        ?ForbiddenEmailDomainRepository $forbiddenEmailDomainRepository = null,
    ) {
        $this->user = $user;
        $this->forbiddenEmailDomainRepository
            = $forbiddenEmailDomainRepository ?? ForbiddenEmailDomainRepository::getInstance();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function validate(): void
    {
        $this->deletedDate();
        $this->name();
        $this->email();
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function deletedDate(): void
    {
        if ($this->user->getDeleted() && $this->user->getCreated()
            && $this->user->getDeleted()->lt($this->user->getCreated())) {
            throw new IncorrectDataException(sprintf(
                "Deleted date (%s) can not be less than Created (%s)",
                $this->user->getDeleted()->toDateTimeString(),
                $this->user->getCreated()->toDateTimeString(),
            ));
        }
    }

    /**
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function name(): void
    {
        $name = $this->user->getName();
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new IncorrectDataException(
            'Username must be 8-64 characters long and contain only letters (a-z) and digits (0-9)'
            );
        }
        foreach (self::NAME_FORBIDDEN_WORDS as $word) {
            if (str_contains($name, $word)) {
                throw new IncorrectDataException(sprintf(
                    'Username can not contain word %s',
                    $word,
                ));
            }
        }
    }

    /**
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function email(): void
    {
        if (!filter_var($this->user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new IncorrectDataException(
                'Email format is invalid',
            );
        }

        $domain = $this->getEmailDomain($this->user->getEmail());
        if ($domain) {
            try {
                $this->forbiddenEmailDomainRepository->getByDomain($domain);
                throw new IncorrectDataException(
                    'Email domain is not allowed',
                );
            } catch (NoSuchEntityException $e) {
                //domain is not forbidden
            }
        }
    }

    /**
     * @param string $email
     * @return string|null
     */
    private function getEmailDomain(string $email): ?string
    {
        $emailParts = explode('@', $email);

        return $emailParts[1] ?? null;
    }
}
