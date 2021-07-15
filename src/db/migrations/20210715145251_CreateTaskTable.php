<?php

class CreateTaskTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('tasks', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('key', 'string');
        $t->column('summary','string');
        $t->column('status','string');
        $t->column('priority','string');
        $t->column('description','text');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("tasks");
    }//down()
}
