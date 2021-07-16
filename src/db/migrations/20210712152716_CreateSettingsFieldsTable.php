<?php

class CreateSettingsFieldsTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('settings_fields', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('name', 'string');
        $t->column('title','string');
        $t->column('value','string');
        $t->column('type','string',['default' => 'text']);
        $t->column('project_id','integer');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("settings_fields");
    }//down()
}
