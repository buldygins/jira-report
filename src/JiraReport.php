<?php

namespace YourResult;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Project\ProjectService;
use PDO;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\User\UserService;
use YourResult\models\Cost;
use YourResult\models\JiraProject;
use YourResult\models\JiraTask;
use YourResult\models\JiraUser;
use YourResult\models\Model;
use YourResult\models\Project;
use YourResult\models\SettingsField;
use YourResult\models\Worklog;

class JiraReport extends \YourResult\MicroService
{

    public $curr_desk = null;
    public $curr_project = null;
    public $configurations = [];

    public function __construct(PDO $db = NULL)
    {
        parent::__construct($db);
        if (empty($this->configurations)) {
            $this->configurations = [
                'jiraHost' => $_ENV['JIRA_HOST'],
                'jiraUser' => $_ENV['JIRA_USER'],
                'jiraPassword' => $_ENV['JIRA_PASS'],
            ];
        }
    }

    function route()
    {
        if (isset($_REQUEST['isAjax']) && $_REQUEST['isAjax']) {
            return $this->ajaxRoutes();
        }
        switch ($this->url_parts['params'][1]) {
            case '':
            case 'projects':
                return $this->projects();
            case 'project':
                if (is_numeric($this->url_parts['params'][2])) {
                    $this->curr_project = Project::find($this->url_parts['params'][2]);
                    if ($this->curr_project) {
                        if (isset($this->url_parts['params'][3]))
                            switch ($this->url_parts['params'][3]) {
                                case 'settings':
                                    return $this->settings();
                                case 'sync':
                                    $this->loadProjectToEnv();
                                    return $this->sync();
                                case 'costRate':
                                    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                                        return $this->getCostRateForm();
                                    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                        return $this->createCostRate();
                                    }
                                case 'report':
                                    return $this->makeReport();
                                default:
                                    return $this->set404();
                            }
                        return $this->set404('WE DON\'T HAVE THAT PAGE YET!');
                    } else {
                        return $this->set404('PROJECT NOT FOUND!');
                    }
                }
                switch ($this->url_parts['params'][2]) {
                    case 'create':
                        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                            return $this->render($this->loadTemplate(realpath('templates/projects/create.php')));
                        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            return $this->createProject();
                        }
                }
            default:
                return $this->set404();
        }
    }

    function ajaxRoutes()
    {
        foreach ($_REQUEST as $key => $value) {
            if ($value == '-1') {
                unset($_REQUEST[$key]);
            }
        }
        switch ($this->url_parts['params'][1]) {
            case 'project':
                if (is_numeric($this->url_parts['params'][2])) {
                    $this->curr_project = Project::find($this->url_parts['params'][2]);
                    if ($this->curr_project) {
                        $this->loadProjectToEnv();
                        if (isset($this->url_parts['params'][3]))
                            switch ($this->url_parts['params'][3]) {
                                case 'report':
                                    $project = !empty($_REQUEST['project_id']) ? JiraProject::whereGet(['id' => $_REQUEST['project_id']]) : [];
                                    $month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m');
                                    $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
                                    $response = $this->generateReportTable($project, $month, $user_id);
                                    echo json_encode(['view' => $response]);
                                    die();
                            }
                    }
                }
                break;
            case 'setting':
                if (is_numeric($this->url_parts['params'][2])) {
                    switch ($this->url_parts['params'][3]) {
                        case 'delete':
                            $field = SettingsField::find($this->url_parts['params'][2]);
                            if ($field) {
                                echo json_encode($field->delete());
                                die();
                            }
                            echo json_encode(false);
                            die();
                    }
                }
                break;
            default:
                return $this->set404();
        }
    }

    function loadProjectToEnv()
    {
        $host = SettingsField::find(['name' => 'JIRA_HOST', 'project_id' => $this->curr_project->id]);
        $user = SettingsField::find(['name' => 'JIRA_USER', 'project_id' => $this->curr_project->id]);
        $pass = SettingsField::find(['name' => 'JIRA_PASS', 'project_id' => $this->curr_project->id]);
        if ((!$host || !$user || !$pass) || (empty($host->value) || empty($user->value) || empty($pass->value))) {
            $this->set404('ERROR, CHECK PROJECT SETTINGS!');
        }
        $this->configurations['jiraHost'] = $host->value;
        $this->configurations['jiraUser'] = $user->value;
        $this->configurations['jiraPassword'] = $pass->value;
    }

    function set404($message = 'NOT FOUND!')
    {
        if (isset($_REQUEST['isAjax']) && $_REQUEST['isAjax']) {
            echo json_encode([
                'success' => false,
                'code' => 404,
                'message' => 'ERROR 404 NOT FOUND!',
            ]);
            die();
        }
        echo $this->loadTemplate(realpath('templates/404.php'), compact('message'));
        return false;
    }

    function projects()
    {
        $output = '';
        $output .= $this->loadTemplate(realpath('templates/projects/project_table.php'), ['projects' => Project::all()]);
        return $this->render($output);
    }

    function getDesks()
    {
        $desks = $this->curr_project->desks();
        return $this->render($this->loadTemplate(realpath('templates/projects/index.php'), ['projects' => $desks]));
    }

    function sync()
    {
        ini_set('max_execution_time', 0);
        if ($this->curr_project) {
            try {
                $project_service = new ProjectService(new ArrayConfiguration($this->configurations));
                $jira_projects = $project_service->getAllProjects();
                //fetch users
                foreach ($jira_projects as $jira_project) {
                    try {
                        JiraProject::firstOrCreate([
                            'jira_key' => $jira_project->key,
                            'project_id' => $this->curr_project->id,
                        ]);
                        $assignees = $project_service->getAssignable($jira_project->key);
                        foreach ($assignees as $assignee) {
                            JiraUser::firstOrCreate([
                                'displayName' => $assignee->displayName,
                                'accountId' => $assignee->accountId ?? $assignee->key,
                            ]);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                    $this->syncJiraTasks($jira_project->key);
                }
            } catch (JiraRestApi\JiraException $e) {
                //print("Error Occured! " . $e->getMessage());
            }
            header('Location: /project/' . $this->curr_project->id . '/report');
            return;
        }
    }


    function syncJiraTasks($project_key)
    {
        $jql = "project = \"{$project_key}\"";

        $filter = SettingsField::find(['name' => $project_key . '_FILTER', 'project_id' => $this->curr_project->id]);
        if (!$filter) {
            return;
        } else {
            if (!empty($filter->value)) {
                $jql .= ' AND ' . $filter->value;
            }
        }

        $issueService = new IssueService(new ArrayConfiguration($this->configurations));

        $search_result = $issueService->search($jql, 0, 500);

        foreach ($search_result->issues as $issue) {
            $arIssues[$issue->key] = 1;
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

                $wl_key = SettingsField::find(['project_id' => $this->curr_project->id, 'name' => 'JIRA_FLD_WORKLOG_AUTHOR']);
                $a = (array)$worklog->author;

                if (isset($worklog->author->accountId)) {
                    $accountId = $worklog->author->accountId;
                } elseif (isset($worklog->author->key)) {
                    $accountId = $worklog->author->key;
                } elseif (is_array($worklog->author) && isset($worklog->author['accountId'])) {
                    $accountId = $worklog->author['accountId'];
                } elseif (is_array($worklog->author) && isset($worklog->author['key'])) {
                    $accountId = $worklog->author['key'];
                }
                if (strpos($accountId, ':') !== false) {
                    $jira_user1 = JiraUser::find(['accountId' => $accountId]);
                    $newAccId = substr($accountId, 0, strpos($accountId, ':'));
                    $jira_user2 = JiraUser::find(['accountId LIKE:' => $newAccId . '%']);
                    $worker = ($jira_user1) ? $jira_user1 : $jira_user2;
                } else {
                    $worker = JiraUser::find(['accountId' => $accountId]);
                }
                $date_array = date_parse($worklog->started);
                $date_string = date('Y-m-d H:i:s', mktime($date_array['hour'], $date_array['minute'], $date_array['second'], $date_array['month'], $date_array['day'], $date_array['year']));


                $hours = intdiv($worklog->timeSpentSeconds, 3600);
                $wl_data = [
                    'task_id' => $task->id,
                    'task_key' => $task->project_key,
                    'started' => $date_string,
                    'seconds_all' => $worklog->timeSpentSeconds,
                    'hours' => $hours,
                    'minutes' => (int)($worklog->timeSpentSeconds - $hours * 3600) / 60,
                    'author_id' => $worker->id ?? null,
                ];

                $wl_data['time'] = '';
                if ($wl_data['hours'] > 0) {
                    $wl_data['time'] .= $wl_data['hours'] . 'ч ';
                }
                if ($wl_data['minutes'] > 0) {
                    $wl_data['time'] .= $wl_data['minutes'] . 'м. ';
                }

                $found_worklog = Worklog::find(['task_id' => $task->id, 'started' => $date_string]);
                if (!$found_worklog) {
                    $found_worklog = Worklog::create($wl_data);
                } else {
                    $found_worklog = Worklog::update($found_worklog->id, $wl_data);
                    // TODO: log если другое время
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
        if (isset($_REQUEST['_method'])) {
            switch ($_REQUEST['_method']) {
                case 'DELETE':
                    $this->curr_project->delete();
                    header('Location: /projects');
                    break;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_REQUEST['settings'] as $setting_id => $value) {
                SettingsField::update($setting_id, ['value' => $value]);
            }
            header('Location: /projects');
        }
        $settings = $this->curr_project->settings();
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
        return $project;
    }

    function makeReport()
    {
        $output = '';

        $users = $this->query("SELECT u.id, u.displayName FROM jira_users u
                  LEFT JOIN worklogs w on u.id = w.author_id
                  LEFT JOIN jira_tasks t on t.id = w.task_id
                  WHERE t.project_id = {$this->curr_project->id}
                  GROUP BY u.id")->fetchAll();
        foreach ($users as $user) {
            $user_filter[$user['id']] = $user['displayName'];
        }
        $projects = $this->curr_project->desks();
        foreach ($projects as $project) {
            $project_filter[$project->id] = $project->jira_key;
        }
        $output .= $this->loadTemplate(realpath('templates/projects/report_filters.php'),
            [
                'users' => $user_filter ?? [],
                'projects' => $project_filter ?? [],
            ]);
        $output .= $this->generateReportTable($projects);
        return $this->render($output);
    }

    function generateReportTable($projects = [], $date = '', $user_id = null)
    {

        $date = !empty($date) ? $date : date('Y-m');
        $date = explode('-', $date);
        $year = $date[0];
        $month = $date[1];

        $find['started >:'] = sprintf("%d-%s-01 00:00:00", $year, $month);

        $next_month = date('m', strtotime('+1 month', strtotime(sprintf('%d-%s-01', $year, $month))));
        $find['started <:'] = sprintf("%d-%s-01 00:00:00", $year, $next_month);
        if ($user_id) {
            $find['author_id'] = $user_id;
        }

        $output = '<div id="report">';

        if (empty($projects)) {
            $projects = $this->curr_project->desks();
        }
        $daily_cost = !isset($_REQUEST['daily_cost']) || isset($_REQUEST['daily_cost']) && $_REQUEST['daily_cost'] == 'false';
        foreach ($projects as $project) {
            $tasks = isset($_REQUEST['all_tasks']) && $_REQUEST['all_tasks'] == 'true' ? $project->tasks() : $project->workedTasks($find['started >:'], $find['author_id'] ?? null);
            $worklogs = Worklog::whereGet(array_merge($find, ['task_key LIKE:' => $project->jira_key . '%']));
            $time = Worklog::getWorklogsTime($worklogs);
            $costs = Cost::calculate($worklogs, $project, $user_id, $daily_cost);
            $host = SettingsField::whereGet(['project_id' => $this->curr_project->id, 'name' => 'JIRA_HOST'])[0]->value;
            $output .= $this->loadTemplate(realpath('templates/projects/time_table.php'),
                [
                    'project_name' => $project->jira_key,
                    'tasks' => $tasks,
                    'time' => $time,
                    'days' => cal_days_in_month(CAL_GREGORIAN, $month, $year),
                    'date' => sprintf('%d-%s-', $year, $month),
                    'costs' => $costs,
                    'host' => $host,
                ]);
        }
        $output .= '</div>';

        return $output;
    }

    function getCostRateForm()
    {
        $desks = $this->curr_project->desks();
        $users = JiraUser::all();
        return $this->render($this->loadTemplate(realpath('templates/settings/cost_rate.php'), compact('desks', 'users')));
    }

    function createCostRate()
    {
        $data['name'] = 'COST_';
        $data['name'] .= isset($_REQUEST['hourly']) ? 'HOUR_' : 'DAY_';
        $data['name'] .= $_REQUEST['entity_id'];
        $data['title'] = 'Ставка для ' . $_REQUEST['title'];
        $data['title'] .= isset($_REQUEST['hourly']) ? ' (день)' : ' (час)';
        $data['value'] = $_REQUEST['rate'];
        $data['project_id'] = $this->curr_project->id;
        Cost::create($data);
        header('Location: /project/' . $this->curr_project->id . '/settings');
    }
}