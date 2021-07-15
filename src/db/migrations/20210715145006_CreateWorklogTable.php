<?php

class CreateWorklogTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('worklog', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('task_id', 'string');
        $t->column('task_key', 'string');
        $t->column('started','string');
        $t->column('seconds_all','integer');
        $t->column('hours','integer');
        $t->column('minutes','integer');
        $t->column('time','string');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("users");
    }//down()
}
