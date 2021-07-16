<?php


namespace YourResult\models;


class JiraProject extends Model
{
    public $jira_key;
    public $project_id;

    public function alwaysHook()
    {
        $field = SettingsField::find([
            'name' => $this->jira_key . '_FILTER',
            'project_id' => $this->project_id,
        ]);
        if (!$field){
            SettingsField::create([
                'name' => $this->jira_key . '_FILTER',
                'title' => 'Фильтр для ' . $this->jira_key,
                'value' => $_ENV['JIRA_FILTER'],
                'type' => 'text',
                'project_id' => $this->project_id,
            ]);
        }
    }

}