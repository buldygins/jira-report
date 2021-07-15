<?php

class CreateSettingsTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('settings', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('setting_id', 'integer');
        $t->column('setting_field_id', 'integer');
        $t->column('value', 'integer');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("settings");
    }//down()
}
