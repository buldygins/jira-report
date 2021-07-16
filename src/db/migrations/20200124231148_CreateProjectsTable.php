<?php

class CreateProjectsTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('projects', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('name', 'string', ['limit' => 50]);
        $t->column('url', 'string', ['limit' => 255]);
        $t->column('descr', 'string', ['limit' => 255]);
        $t->column('created_at', 'datetime');
        $t->finish();

        $this->add_index('projects', 'name', ['unique'=>true]);

        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('klienti', 'Клиенты', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('4lapy', 'Четыре лапы', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('bg', 'Банковские гарантии', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('hotels', 'Отели', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('ipserver', 'IP Server', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('mastergrad', 'Мастерград', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('dacha', 'Дача', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('vkusvill', 'ВкусВилл Доставка', NOW());");
        $this->execute("INSERT INTO `projects` (`name`, `descr`, `created_at`) VALUES ('kul', 'Кулинариум', NOW());");

//        $this->execute("INSERT INTO `services` (`nam`, `url`, `descr`, `created_at`) ".
//            "VALUES ('{$_ENV['SERVICE_NAM']}', '{$_ENV['SERVICE_URL']}', '', NOW());");
    }

    public function down()
    {
        $this->drop_table("projects");
    }
}
