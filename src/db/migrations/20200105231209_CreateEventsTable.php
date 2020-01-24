<?php

class CreateEventsTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('events', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('nam', 'string', ['limit' => 255]);
        $t->column('service', 'string', ['limit' => 255]);
        $t->column('subject', 'string', ['limit' => 255]);
        $t->column('body', 'text');
        $t->column('created_at', 'datetime');
        $t->column('expired_at', 'datetime');
        //$t->column('expired_at', 'datetime');
        $t->finish();
    }

    public function down()
    {
        $this->drop_table("events");
    }
}
