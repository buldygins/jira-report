<?php

class CreateVersionTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('version', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('nam', 'string', ['limit' => 64]);
        $t->column('descr', 'string', ['limit' => 64]);
        $t->column('created_at', 'datetime');
        $t->finish();

        $this->execute("INSERT INTO `version` (`id`, `nam`, `descr`, `created_at`) ".
                    "VALUES (NULL, '1.0.0', 'Микросервис получения данных из Трелло', NULL);");
    }

    public function down()
    {
        $this->drop_table("version");
    }
}
