<?php

class CreateUsersTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('users', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('displayName', 'string');
        $t->column('accountId','string');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("users");
    }//down()
}
