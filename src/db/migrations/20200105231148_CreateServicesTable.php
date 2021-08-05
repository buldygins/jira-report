<?php

class CreateServicesTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('services', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('nam', 'string', ['limit' => 50]);
        $t->column('url', 'string', ['limit' => 255]);
        $t->column('descr', 'string', ['limit' => 255]);
        $t->column('created_at', 'datetime');
        $t->finish();

        $this->add_index('services', 'nam', ['unique'=>true]);


//        $this->execute("INSERT INTO `services` (`nam`, `url`, `descr`, `created_at`) ".
//            "VALUES ('{$_ENV['SERVICE_NAM']}', '{$_ENV['SERVICE_URL']}', '', NOW());");
    }

    public function down()
    {
        $this->drop_table("services");
    }
}
