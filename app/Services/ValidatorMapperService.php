<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\IncorrectDataException;
use App\Interfaces\ValidatorInterface;
use App\Models\AbstractModel;
use App\Models\User;
use App\Validators\Models\UserValidator;

class ValidatorMapperService
{
    /** @var array|\class-string[] */
    private const MAPPING_DEFAULT = [
        User::class => UserValidator::class,
    ];

    /**
     * @param array $mapping
     */
    public function __construct(
        private readonly array $mapping = self::MAPPING_DEFAULT,
    ) {
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @return \App\Interfaces\ValidatorInterface
     * @throws \App\Exceptions\IncorrectDataException
     */
    public function getForModel(AbstractModel $model): ValidatorInterface
    {
        $validatorClass = $this->mapping[$model::class] ?? null;
        if ($validatorClass) {
            $result = new $validatorClass($model);
        } else {
            throw new IncorrectDataException(sprintf('Unable to get Validator for type %s', $model::class));
        }

        return $result;
    }
}