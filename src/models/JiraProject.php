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
        if (!$field) {
            SettingsField::create([
                'name' => $this->jira_key . '_FILTER',
                'title' => 'Фильтр для ' . $this->jira_key,
                'value' => $_ENV['JIRA_FILTER'],
                'type' => 'text',
                'project_id' => $this->project_id,
            ]);
        }
    }

    public function tasks()
    {
        return JiraTask::whereGet(['project_key LIKE:' => $this->jira_key . '%', 'project_id' => $this->project_id]);
    }

    public function workedTasks($date_from = null, $user_id = null)
    {
        global $dbpdo;
        $date_from = $date_from ?? date('Y-m-d 00:00:00');
        $date_to = $date_to ?? date('Y-m-d 00:00:00', strtotime('+1 month', strtotime($date_from)));
        $where = "WHERE t.project_id = {$this->project_id} AND t.project_key LIKE '{$this->jira_key}%' AND w.started > '{$date_from}' AND w.started < '{$date_to}'";
        if ($user_id){
            $where .= " AND w.author_id = {$user_id} ";
        }
        $ids = $dbpdo->query("SELECT t.id FROM jira_tasks t
                                       LEFT JOIN worklogs w ON t.id = w.task_id {$where}
                                       GROUP BY t.id");
        $ids = $ids->fetchAll(\PDO::FETCH_NAMED);
        foreach ($ids as $id) {
            $arr_ids[] = $id['id'];
        }
        return JiraTask::whereGet(['id'=> $arr_ids]);
    }

    public function desks(){
        return JiraProject::whereGet(['project_id' => $this->id]);
    }
}