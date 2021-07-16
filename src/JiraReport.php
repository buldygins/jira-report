<?php

namespace YourResult;

use JiraRestApi\Project\ProjectService;
use PDO;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\User\UserService;
use YourResult\models\JiraProject;
use YourResult\models\JiraTask;
use YourResult\models\JiraUser;
use YourResult\models\Project;
use YourResult\models\SettingsField;
use YourResult\models\Worklog;

class JiraReport extends \YourResult\MicroService
{

    public $curr_desk = null;
    public $curr_project = null;

    public function __construct(PDO $db = NULL)
    {
        parent::__construct($db);
    }

    function route()
    {
        parent::route();
        switch ($this->url_parts['params'][1]) {
            case 'addTask':
                $this->addTask();
                break;
            case 'projects':
                $this->projects();
                break;
            case 'project':
                if (is_numeric($this->url_parts['params'][2])) {
                    $this->curr_project = Project::find($this->url_parts['params'][2]);
                    if ($this->curr_project) {
                        switch ($this->url_parts['params'][3]) {
                            case 'settings':
                                $this->settings();
                                break;
                            case 'sync':
                                $this->sync();
                                break;
                            case 'desks':
                                $this->getDesks();
                                break;
                            case 'desk':
                                if (isset($this->url_parts['params'][4])) {
                                    $this->curr_desk = JiraProject::find(['jira_key' => $this->url_parts['params'][4]]);
                                    $this->makeReport();
                                    break;
                                }
                            case 'report':
                                $this->makeReport();
                                break;
                        }
                    }
                }
                switch ($this->url_parts['params'][2]) {
                    case 'create':
                        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                            $this->render($this->loadTemplate(realpath('templates/projects/create.php')));
                        } else {
                            $this->createProject();
                        }
                        break;
                }
                $this->project();
                break;


        }
    }

    function addTask()
    {

        if (!isset($_REQUEST['create'])) {
            ?>
            <form method="post">
                <table>
                    <tr>
                        <td>Тема</td>
                        <td><input type="text" name="subject"></td>
                    </tr>
                    <tr>
                        <td>Описание</td>
                        <td><textarea rows="10" cols="40" name="text"></textarea></td>
                    </tr>
                </table>
                <input type="hidden" name="project" value="<?= $_REQUEST['project']; ?>">
                <input type="hidden" name="create" value="1">
                <input type="submit">
            </form>
            <?
        } else {
            //print_r($_REQUEST);
            try {
                $issueField = new IssueField();

                $issueField->setProjectKey("BG")->setSummary($_REQUEST['subject'])->setAssigneeName("v.smorodinsky")
                    //->setPriorityName("Critical")
                    ->setIssueType("Задача")->setDescription($_REQUEST['text'])
                    //->addVersion(["1.0.1", "1.0.3"])
                    //->addComponents(['Component-1', 'Component-2'])
                    // set issue security if you need.
                    // ->setSecurityId(10001 /* security scheme id */)
                    //->setDueDate('2020-01-19')
                ;

                $issueService = new IssueService();

                $ret = $issueService->create($issueField);

                //If success, Returns a link to the created issue.
                // var_dump($ret);
                echo "<SCRIPT>document.location='/run?project={$_REQUEST['project']}';</SCRIPT>";
            } catch (JiraException $e) {
                print("Error Occured! " . $e->getMessage());
            }
        }
    }

    function projects()
    {
        $output = '';
        $output .= $this->loadTemplate(realpath('templates/projects/project_table.php'), ['projects' => Project::all()]);
        return $this->render($output);
    }

    function project()
    {
    }


    function getDesks()
    {
        $desks = JiraProject::whereGet(['project_id' => $this->curr_project->id]);
        return $this->render($this->loadTemplate(realpath('templates/projects/index.php'), ['projects' => $desks]));
    }

    function sync()
    {
        if ($this->curr_project) {
            $jira_projects = [];
            try {
                $project_service = new ProjectService();
                $jira_projects = $project_service->getAllProjects();
                //fetch users
                foreach ($jira_projects as $jira_project) {
                    JiraProject::firstOrCreate([
                        'jira_key' => $jira_project->key,
                        'project_id' => $this->curr_project->id,
                    ]);
                    $assignees = $project_service->getAssignable($jira_project->id);
                    foreach ($assignees as $assignee) {
                        JiraUser::firstOrCreate([
                            'displayName' => $assignee->displayName,
                            'accountId' => $assignee->accountId,
                        ]);
                    }
                    $this->syncJiraTasks($jira_project->key);
                }
            } catch (JiraRestApi\JiraException $e) {
                print("Error Occured! " . $e->getMessage());
            }
            header('Location: /project/' . $this->curr_project->id . '/desks');
        }
    }


    function syncJiraTasks($project_key)
    {
        $jql = "project = {$project_key}";

        $filter = SettingsField::find(['name' => $project_key . '_FILTER', 'project_id' => $this->curr_project->id]);
        if (!$filter) {
            return;
        } else {
            if (!empty($filter->value)) {
                $jql .= ' AND ' . $filter->value;
            }
        }
        $issueService = new IssueService();

        $search_result = $issueService->search($jql, 0, 500);

        foreach ($search_result->issues as $issue) {
            if (
                ($issue->fields->assignee->name == $_ENV['JIRA_USER']) ||
                ($issue->fields->watches->isWatching >= 0)
            ) {
                $arIssues[$issue->key] = 1;
            }
        }

        $keys = !empty($arIssues) ? array_keys($arIssues) : [];
        foreach ($keys as $key) {

            $queryParam = [
                'fields' => [],
                'expand' => [
                    'renderedFields',
                    'names',
                    'schema',
                    'transitions',
                    'operations',
                    'editmeta',
                    'changelog',
                ]
            ];


            $issue = $issueService->get($key, $queryParam);

            $task = JiraTask::find([
                'project_key' => $issue->key,
                'project_id' => $this->curr_project->id,
            ]);
            if (!$task) {
                $task = JiraTask::create([
                    'project_key' => $issue->key,
                    'summary' => $issue->fields->summary,
                    'status' => $issue->fields->status->name,
                    'priority' => $issue->fields->priority->name,
                    'description' => $issue->fields->description,
                    'project_id' => $this->curr_project->id,
                ]);
            } else {
                $task = JiraTask::update([
                    'project_key' => $issue->key,
                    'project_id' => $this->curr_project->id,
                ], [
                    'project_key' => $issue->key,
                    'summary' => $issue->fields->summary,
                    'status' => $issue->fields->status->name,
                    'priority' => $issue->fields->priority->name,
                    'description' => $issue->fields->description,
                    'project_id' => $this->curr_project->id,
                ]);
                // TODO: log
            }

            unset($log);

//                     TODO: ticket заказчика
//                    if (isset($issue->fields->customFields['customfield_10300'])) {
//                        $log['ticket'] = $issue->fields->customFields['customfield_10300'];
//                    }

            if (count($issue->fields->worklog->worklogs) >= 5) {
                $worklogs = $issueService->getWorklog($key)->getWorklogs();
            } else {
                $worklogs = $issue->fields->worklog->worklogs;
            }
            foreach ($worklogs as $worklog) {

//                if (in_array($task['id'],[67,68,69,70,71,72])){
//                    var_dump($task['id']);
//                    var_dump($worklog);
//                    exit();
//                }

                $wl_key = SettingsField::find(['project_id' => $this->curr_project->id, 'name' => 'JIRA_FLD_WORKLOG_AUTHOR']);
                $a = (array)$worklog->author;

                $accountId = $worklog->author->accountId;
                if (strpos($accountId, ':') !== false) {
                    $jira_user1 = JiraUser::find(['accountId' => $accountId]);
                    $newAccId = substr($accountId, 0, strpos($accountId, ':'));
                    $jira_user2 = JiraUser::find(['accountId LIKE:' => $newAccId . '%']);
                    $worker = ($jira_user1) ? $jira_user1 : $jira_user2;
                } else {
                    $worker = JiraUser::find(['accountId' => $accountId]);
                }
//                if (!$worker){
//                    var_dump($worklog);
//                    var_dump(strpos($accountId,':'));
//                    var_dump($accountId);
//                    var_dump(substr($accountId, 0, strpos($accountId,':')));
//                    exit();
//                }
                if ((($_ENV['JIRA_FLD_WORKLOG_ANY_AUTHOR'] == 0) && ($a[$wl_key] == $_ENV['JIRA_USER']))
                    || ($_ENV['JIRA_FLD_WORKLOG_ANY_AUTHOR'] == 1)
                ) {
                    $date_array = date_parse($worklog->started);
                    $date_string = date('Y-m-d H:i:s', mktime($date_array['hour'], $date_array['minute'], $date_array['second'], $date_array['month'], $date_array['day'], $date_array['year']));

                    $hours = intdiv($worklog->timeSpentSeconds, 3600);
                    $wl_data = [
                        'task_id' => $task['id'],
                        'task_key' => $task['project_key'],
                        'started' => $date_string,
                        'seconds_all' => $worklog->timeSpentSeconds,
                        'hours' => $hours,
                        'minutes' => (int)($worklog->timeSpentSeconds - $hours * 3600) / 60,
                        'author_id' => $worker->id,
                    ];
                    $wl_data['time'] = '';
                    if ($wl_data['hours'] > 0) {
                        $wl_data['time'] .= $wl_data['hours'] . 'ч ';
                    }
                    if ($wl_data['minutes'] > 0) {
                        $wl_data['time'] .= $wl_data['minutes'] . 'м. ';
                    }
                    $found_worklog = Worklog::find(['task_id' => $task['id'], 'started' => $worklog->started]);
                    if (!$found_worklog) {
                        $found_worklog = Worklog::create($wl_data);
                    } else {
                        $found_worklog = Worklog::update($found_worklog->id, $wl_data);
                        // TODO: log если другое время
                    }
                }
            }
        }
    }

    function query($sql)
    {
        return $this->db->query($sql, PDO::FETCH_ASSOC);
    }

    function settings()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_REQUEST['settings'] as $setting_id => $value) {
                SettingsField::update($setting_id, ['value' => $value]);
            }
            header('Location: /projects');
        }
        $settings = SettingsField::whereGet(['project_id' => $this->curr_project->id]);
        foreach ($settings as &$setting) {
            $setting = $setting->renderField();
        }
        $output = $this->loadTemplate(realpath('templates/settings/index.php'), compact('settings'));
        return $this->render($output);
    }

    function render($output = '')
    {
        return include 'templates/index.php';
    }

    function loadTemplate($template_name, array $data = [], $output = '')
    {
        foreach ($data as $key => $value) {
            $$key = $value;
            unset($data[$key]);
        }
        if (file_exists($template_name)) {
            ob_start();
            require $template_name;
            $html = ob_get_contents();
            ob_end_clean();
            $output .= $html;
        }
        return $output;
    }

    function createProject()
    {
        $project = Project::create(array_merge($_REQUEST, ['created_at' => date('Y-m-d H:i:s')]));
        if ($project) {
            header('Location: /project/' . $project->id . '/settings');
        } else {
            header('Location: /projects');
        }
    }

    function makeReport()
    {
        $output = '';
//        if ($this->curr_desk) {
//            $find['task_key LIKE:'] = $this->curr_desk->jira_key . '%';
//        } else {
        $projects = JiraProject::whereGet(['project_id' => $this->curr_project->id]);
//            $keys = [];
//            foreach ($projects as $project) {
//                $keys[] = $project->jira_key . '%';
//            }
//            $find['task_key LIKE:'] = $keys;
//        }
        $month = date('m');
        $year = date('Y');
        $find['started >:'] = date('Y-m-01 00:00:00');
        foreach ($projects as $project) {
            $tasks = JiraTask::whereGet(['project_key LIKE:' => $project->jira_key . '%']);
            $worklogs = Worklog::whereGet(array_merge($find, ['task_key LIKE:' => $project->jira_key . '%']));
            $time = Worklog::getWorklogsTime($worklogs);
            $output = $this->loadTemplate(realpath('templates/projects/time_table.php'),
                [
                    'tasks' => $tasks,
                    'time' => $time,
                    'days' => cal_days_in_month(CAL_GREGORIAN, $month,$year),
                ]);
        }
        $this->render($output);


        exit();
        var_dump($this->curr_desk);
        $worklogs = Worklog::whereGet(['task_key LIKE:' => $this->curr_desk->jira_key . '%', 'started >:' => date('Y-m-01 00:00:00')]);
        var_dump($worklogs);
        exit();
    }
}