<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class HistoryTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $this->table('history')
            ->addColumn('entity_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('entity_type', 'string', ['null' => false, 'limit' => 256])
            ->addColumn('changed_data', 'json', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['entity_type', 'entity_id'], [
                    'name' => 'idx_history_entity_type_entity_id',
                ]
            )
            ->create();

    }
}
