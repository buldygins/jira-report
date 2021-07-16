<?php

class CreateJiraProjectsTable extends Ruckusing_Migration_Base
{
    public function up()
    {
        $t = $this->create_table('jira_projects', ['id' => true, 'options' => 'Engine=InnoDB']);
        $t->column('jira_key', 'string');
        $t->column('project_id','string');
        $t->finish();
    }//up()

    public function down()
    {
        $this->drop_table("jira_projects");
    }//down()
}
