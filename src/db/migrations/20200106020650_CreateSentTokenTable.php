<?php

class CreateSentTokenTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('sent_token', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('id_token', 'integer');
        $t->column('service', 'string', ['limit' => 255]);
        $t->column('created_at', 'datetime');
        //$t->column('expired_at', 'datetime');
        $t->finish();
    }

    public function down()
    {
        $this->drop_table("sent_token");
    }
}
