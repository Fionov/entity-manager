<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ForbiddenEmailDomainsTable extends AbstractMigration
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
        $this->table('forbidden_email_domains')
            ->addColumn('domain', 'string', ['null' => false, 'limit' => 256])
            ->addColumn('reason', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn(
                'updated',
                'datetime',
                ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP']
            )
            ->addIndex(['domain'], [
                    'unique' => true,
                    'name' => 'idx_forbidden_email_domains_domain',
                ]
            )
            ->create();
    }
}
