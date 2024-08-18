<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\EntitySaveException;
use App\Exceptions\IncorrectDataException;
use App\Interfaces\SoftDeletableInterface;
use App\Models\AbstractModel;
use Exception;
use Framework\DBAdapter;
use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Abstract Singleton instance for Repositories
 */
abstract class AbstractRepository
{
    /** @var \App\Repositories\AbstractRepository[] */
    private static array $instances = [];

    /** @var int */
    private const MAX_INSERT_LINES = 1000;

    /** @var \PDO */
    private readonly PDO $connection;

    /** @var \Psr\Log\LoggerInterface */
    private readonly LoggerInterface $logger;

    /**
     * Disable constructor
     */
    protected function __construct(
        ?LoggerInterface $logger = null,
        ?PDO $connection = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->connection = $connection ?? DBAdapter::getInstance()->getConnection();
    }

    /**
     * Disable clone
     */
    protected function __clone()
    {
    }

    /**
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton instance.');
    }

    /**
     * @param ...$args
     * @return static
     */
    public static function getInstance(...$args): static
    {
        $class = static::class;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static(...$args);
        }

        return self::$instances[$class];
    }

    /**
     * Split big portions of data for mass insert
     *
     * @return int
     */
    protected function getMaxInsertRows(): int
    {
        return self::MAX_INSERT_LINES;
    }

    /**
     * @throws \App\Exceptions\EntitySaveException
     */
    protected function saveModel(AbstractModel $model): AbstractModel
    {
        $this->validateFields($model);

        if ($model->getId()) {
            $this->updateModel($model);
        } else {
            $modelData = $model->toArray();
            $keys = [];
            $values = [];
            $placeholders = [];
            foreach ($modelData as $key => $value) {
                $keys[] = $key;
                $values[':' . $key] = $value;
                $placeholders[] = ':' . $key;
            }
            $keys = implode(', ', $keys);
            $placeholders = implode(', ', $placeholders);

            $isTransactionStarted = false;
            try {
                if (!$this->connection->inTransaction()) {
                    $this->connection->beginTransaction();
                    $isTransactionStarted = true;
                }
                $statement = $this->connection->prepare(
                    "INSERT INTO {$model->getTableName()}($keys) VALUES ($placeholders)"
                );
                $statement->execute($values);
                $this->loadModel($model, $this->connection->lastInsertId());
                $model->setIsNew(true);
                $this->saveCommitBefore($model);
                if ($isTransactionStarted) {
                    $this->connection->commit();
                }
            } catch (Exception $e) {
                if ($isTransactionStarted) {
                    $this->connection->rollBack();
                }
                $this->logger->error($e->getMessage());
                throw new EntitySaveException($e->getMessage(), (int) $e->getCode());
            }
        }

        return $model;
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @return \App\Models\AbstractModel
     * @throws \App\Exceptions\EntitySaveException
     */
    protected function updateModel(AbstractModel $model): AbstractModel
    {
        $modelData = $model->toArray();
        unset($modelData[$model->getPk()]);

        $update = [];
        $values = [':id' => $model->getId()];
        foreach ($modelData as $key => $value) {
            $update[] = "$key = :$key";
            $values[':' . $key] = $value;
        }
        $update = implode(', ', $update);

        $isTransactionStarted = false;
        try {
            if (!$this->connection->inTransaction()) {
                $this->connection->beginTransaction();
                $isTransactionStarted = true;
            }
            $statement = $this->connection->prepare(
                "UPDATE {$model->getTableName()} SET $update WHERE {$model->getPk()} = :id"
            );
            $statement->execute($values);
            $this->saveCommitBefore($model);
            if ($isTransactionStarted) {
                $this->connection->commit();
            }
            $model->setOrigData($model->toArray());
        } catch (Exception $e) {
            if ($isTransactionStarted && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            $this->logger->error($e->getMessage());
            throw new EntitySaveException($e->getMessage(), (int) $e->getCode());
        }

        return $model;
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @param string|int|array $value
     * @param string|array $field
     * @param string $select
     * @return \App\Models\AbstractModel
     * @throws \App\Exceptions\IncorrectDataException
     */
    protected function loadModel(
        AbstractModel $model,
        string|int|array $value,
        string|array $field = AbstractModel::ID,
        string $select = '*',
    ): AbstractModel {
        if (is_array($value) xor is_array($field)) {
            throw new IncorrectDataException('Arguments Value and Fields should be both arrays or both scalars');
        }
        $values = [];
        $fields = [];
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $values[":$key"] = $item;
                $fields[] = "$field[$key] = :$key";
            }
        } else {
            $values[':value'] = $value;
            $fields[] = "$field = :value";
        }
        $fields = implode(' AND ', $fields);
        $statement = $this->connection->prepare(
            "SELECT $select FROM {$model->getTableName()} WHERE $fields ORDER BY {$model->getPk()} DESC LIMIT 1"
        );
        $statement->execute($values);

        $result = $statement->fetchObject();
        if ($result) {
            $result = (array) $result;
            $model->setData($result);
        }

        return $model;
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @param bool $isSoftDelete
     * @return bool
     */
    protected function deleteModel(AbstractModel $model, bool $isSoftDelete = true): bool
    {
        $deletedField = SoftDeletableInterface::DELETED;
        $deleteString = $isSoftDelete
            ? "UPDATE {$model->getTableName()} SET $deletedField = NOW() WHERE {$model->getPk()} = :id"
            : "DELETE FROM {$model->getTableName()} WHERE {$model->getPk()} = :id";
        $statement = $this->connection->prepare($deleteString);
        $statement->execute([
            ':id' => $model->getId()
        ]);

        return true;
    }

    /**
     * @param array $data
     * @param string $tableName
     * @return void
     * @throws \App\Exceptions\EntitySaveException
     */
    protected function insertFromArray(array $data, string $tableName): void
    {
        foreach (array_chunk($data, $this->getMaxInsertRows()) as $dataChunk) {
            $fields = [];
            $values = [];
            $placeholdersTotal = [];
            $update = [];
            foreach ($dataChunk as $key => $row) {
                $placeholders = [];
                foreach ($row as $colName => $value) {
                    if (!$key) {
                        $fields[] = $colName;
                        $update[] = "$colName = VALUES($colName)";
                    }
                    $values[":{$key}_{$colName}"] = $value;
                    $placeholders[] = ":{$key}_{$colName}";
                }
                $placeholdersTotal[] = implode(', ', $placeholders);
            }
            $fields = implode(', ', $fields);
            $placeholdersTotal = implode('), (', $placeholdersTotal);
            $update = implode(', ', $update);
            if (count($dataChunk)) {
                try {
                    $statement = $this->connection->prepare(
                        "INSERT INTO $tableName ($fields) VALUES ($placeholdersTotal) ON DUPLICATE KEY UPDATE $update"
                    );
                    $statement->execute($values);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    throw new EntitySaveException($e->getMessage(), (int)$e->getCode());
                }
            }
        }
    }

    /**
     * @param string $entityType
     * @param string $tableName
     * @param array $fields
     * @param array|null $where
     * @param array|null $ordering
     * @param int|null $limit
     * @param int|null $offset
     * @return AbstractModel[]
     */
    protected function loadCollection(
        string $entityType,
        string $tableName,
        array $fields = ['*'],
        ?array $where = null,
        ?array $ordering = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $query = 'SELECT';
        $query .= ' ' . implode(', ', $fields);
        $query .= " FROM $tableName";
        $values = [];
        if ($where) {
            $whereConditions = [];
            foreach ($where as $condition) {
                if (count($condition) == 3) {
                    if (is_array($condition[2])) {
                        $subConditions = [];
                        foreach ($condition[2] as $key => $subConditionValue) {
                            $subConditions[] = ":$condition[0]_$key";
                            $values[":$condition[0]_$key"] = $subConditionValue;
                        }
                        $whereConditions[] = sprintf(
                            '%s %s (%s)',
                            $condition[0],
                            $condition[1],
                            implode(', ', $subConditions),
                        );
                    } else {
                        $whereConditions[] = "$condition[0] $condition[1] :$condition[0]";
                        $values[":$condition[0]"] = $condition[2];
                    }
                }
            }
            $query .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        if ($ordering) {
            $orderingValues = [];
            foreach ($ordering as $field => $direction) {
                $direction = $direction === strtoupper('ASC') ? 'ASC' : 'DESC';
                $orderingValues[] = "$field $direction";
            }

            $query .= ' ORDER BY ' . implode(', ', $orderingValues);
        }
        if ($limit) {
            $query .= " LIMIT $limit";
        }
        if ($offset) {
            $query .= " OFFSET $offset";
        }

        $statement = $this->connection->prepare($query);
        $statement->execute($values);

        return $this->hydrateModels($statement->fetchAll(), $entityType);
    }

    /**
     * @param array $entities
     * @param string $entityType
     * @return array
     */
    private function hydrateModels(array $entities, string $entityType): array
    {
        $result = [];
        foreach ($entities as $entity) {
            /** @var \App\Models\AbstractModel $model */
            $model = new $entityType();
            $model->setData($entity);
            $result[] = $model;
        }

        return $result;
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @return void
     */
    protected function validateFields(AbstractModel $model): void
    {
    }

    /**
     * @param \App\Models\AbstractModel $model
     * @return void
     */
    protected function saveCommitBefore(AbstractModel $model): void
    {
    }
}
