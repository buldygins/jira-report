<?php

class CreateListenEventsTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('listen_events', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('nam', 'string', ['limit' => 255]);
        $t->column('service', 'string', ['limit' => 50]);
        $t->column('id_in_service', 'integer');
        $t->column('subject', 'string', ['limit' => 255]);
        $t->column('body', 'text');
        $t->column('created_at', 'datetime');
        $t->column('expired_at', 'datetime');
        $t->column('come_at', 'datetime');
        //$t->column('expired_at', 'datetime');
        $t->finish();

        $this->add_index("listen_events", array('service', 'id_in_service'), array('unique' => true));
    }

    public function down()
    {
        $this->drop_table("listen_events");
    }
}
