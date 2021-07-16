<?php


namespace YourResult\models;


class Project extends Model
{
    public $name;
    public $url;
    public $descr;
    public $created_at;

    public static $generatedSettings = [
        [
            'name' => 'JIRA_USER',
            'title' => 'Логин пользователя Jira',
            'value' => '',
        ],
        [
            'name' => 'JIRA_PASS',
            'title' => 'Api ключ',
            'value' => '',
        ], [
            'name' => 'JIRA_HOST',
            'title' => 'Хост Jira',
            'value' => '',
        ], [
            'name' => 'JIRA_USER',
            'title' => 'Логин пользователя Jira',
            'value' => '',
        ], [
            'name' => 'JIRA_FLD_WORKLOG_ANY_AUTHOR',
            'title' => 'Учитывать любого отметившего время',
            'value' => '',
            'type' => 'checkbox',
        ], [
            'name' => 'JIRA_FLD_WORKLOG_AUTHOR',
            'title' => 'Логин пользователя Jira',
            'value' => '',
        ], [
            'name' => 'COST_HOUR',
            'title' => 'Почасовая оплата',
            'value' => '',
        ],
        [
            'name' => 'COST_DAY',
            'title' => 'Оплата в день',
            'value' => '',
        ],
    ];

    public function createdHook()
    {
        foreach (self::$generatedSettings as $setting) {
            $setting = array_merge(['project_id' => $this->id, 'type' => 'text'], $setting);
            SettingsField::create($setting);
        }
    }

}