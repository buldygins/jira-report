<?php

class CreateEventsSubscribeTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('events_subscribe', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('service_in', 'string', ['limit' => 50]);
        $t->column('service_out','string', ['limit' => 50]);
        $t->column('event_nam',  'string', ['limit' => 50]);
        $t->column('func',  'string', ['limit' => 50]);
        $t->column('created_at', 'datetime');
        //$t->column('expired_at', 'datetime');
        $t->finish();

        $this->add_index('events_subscribe', ['service_in', 'service_out', 'event_nam'], ['unique'=>true]);
    }

    public function down()
    {
        $this->drop_table("events_subscribe");
    }
}
