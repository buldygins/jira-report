<?php

class CreateProjectDesksTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('project_desks', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('key', 'string');
        $t->column('project_id','string');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("project_desks");
    }//down()
}
