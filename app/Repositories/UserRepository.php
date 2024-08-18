<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\NoSuchEntityException;
use app\Interfaces\Data\UserInterface;
use App\Models\AbstractModel;
use App\Models\User;
use App\Services\HistoryService;
use App\Validators\Models\UserValidator;
use PDO;
use Psr\Log\LoggerInterface;

class UserRepository extends AbstractRepository
{
    /** @var \app\Interfaces\Data\UserInterface[] */
    private array $entities = [];

    /** @var \App\Services\HistoryService */
    private readonly HistoryService $historyService;

    /**
     * @param \Psr\Log\LoggerInterface|null $logger
     * @param \PDO|null $connection
     * @param \App\Services\HistoryService|null $historyService
     */
    protected function __construct(
        ?LoggerInterface $logger = null,
        ?PDO $connection = null,
        ?HistoryService $historyService = null,
    ) {
        parent::__construct(logger: $logger, connection: $connection);

        $this->historyService = $historyService ?? new HistoryService();
    }

    /**
     * @param int $id
     * @param bool $forceReload
     * @return \app\Interfaces\Data\UserInterface
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function getById(int $id, bool $forceReload = false): UserInterface
    {
        if (!isset($this->entities[User::ID][$id]) || $forceReload) {
            $user = new User();
            $this->loadModel($user, $id);
            if (!$user->getId()) {
                throw new NoSuchEntityException(sprintf(
                    'There is no User with ID %s',
                    $id
                ));
            }
            $this->entities[User::ID][$id] = $user;
        }

        return $this->entities[User::ID][$id];
    }

    /**
     * @param string $email
     * @param bool $forceReload
     * @return \app\Interfaces\Data\UserInterface
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function getByEmail(string $email, bool $forceReload = false): UserInterface
    {
        if (!isset($this->entities[User::EMAIL][$email]) || $forceReload) {
            $user = new User();
            $this->loadModel($user, $email, User::EMAIL);
            if (!$user->getId()) {
                throw new NoSuchEntityException(sprintf(
                    'There is no User with Email %s',
                    $email
                ));
            }
            $this->entities[User::EMAIL][$email] = $user;
        }

        return $this->entities[User::EMAIL][$email];
    }

    /**
     * @param \app\Interfaces\Data\UserInterface|\App\Models\User $model
     * @return \app\Interfaces\Data\UserInterface
     * @throws \App\Exceptions\EntitySaveException
     */
    public function save(UserInterface|User $model): UserInterface
    {
        $this->saveModel($model);

        $this->entities[User::ID][$model->getId()] = $model;
        $this->entities[User::EMAIL][$model->getEmail()] = $model;

        return $model;
    }

    /**
     * @param int $id
     * @param bool $isSoftDelete
     * @return bool
     * @throws \App\Exceptions\IncorrectDataException
     * @throws \App\Exceptions\NoSuchEntityException
     */
    public function deleteById(int $id, bool $isSoftDelete = true): bool
    {
        $user = $this->getById($id);

        unset($this->entities[User::ID][$id]);
        unset($this->entities[User::EMAIL][$id]);

        return $this->deleteModel($user, $isSoftDelete);
    }

    /**
     * @param array|null $where
     * @param int $limit
     * @param int $offset
     * @param array $ordering
     * @return UserInterface[]
     */
    public function getCollection(
        ?array $where = null,
        int $limit = 25,
        int $offset = 0,
        array $ordering = [User::ID => 'ASC'],
    ): array {
        return $this->loadCollection(
            entityType: User::class,
            tableName: User::getTableName(),
            where: $where,
            ordering: $ordering,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param \App\Models\AbstractModel|\app\Interfaces\Data\UserInterface $model
     * @return void
     * @throws \App\Exceptions\IncorrectDataException
     */
    protected function validateFields(AbstractModel|UserInterface $model): void
    {
        $userValidator = new UserValidator($model);
        $userValidator->validate();
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     */
    protected function saveCommitBefore(AbstractModel $model): void
    {
        parent::saveCommitBefore($model);

        $this->historyService->saveHistory($model);
    }
}