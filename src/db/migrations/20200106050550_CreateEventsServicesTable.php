<?php

class CreateEventsServicesTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('events_services', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('service', 'string', ['limit' => 50]);
        $t->column('event_nam', 'string', ['limit' => 50]);
        $t->column('created_at', 'datetime');
        //$t->column('expired_at', 'datetime');
        $t->finish();

        $this->add_index('events_services', ['service', 'event_nam'], ['unique'=>true]);
    }

    public function down()
    {
        $this->drop_table("events_services");
    }
}
