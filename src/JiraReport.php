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
        if ($_REQUEST['isAjax']) {
            return $this->ajaxRoutes();
        }
        parent::route();
        switch ($this->url_parts['params'][1]) {
            case 'addTask':
                return $this->addTask();
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
//                                case 'desk':
//                                    if (isset($this->url_parts['params'][4])) {
//                                        $this->curr_desk = JiraProject::find(['jira_key' => $this->url_parts['params'][4]]);
//                                        return $this->makeReport();
//                                    }
                                case 'report':
                                    return $this->makeReport();
                                default:
                                    return $this->set404();
                            }
                        //return $this->getDesks();
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
                                    $response = $this->generateReportTable($project, $_REQUEST['month'], $_REQUEST['user_id']);
                                    echo json_encode(['view' => $response]);
                                    die();
                            }
                    }
                }
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
        if ($_REQUEST['isAjax']) {
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
                $issueField = new IssueField(new ArrayConfiguration($this->configurations));

                $issueField->setProjectKey("BG")->setSummary($_REQUEST['subject'])->setAssigneeName("v.smorodinsky")
                    //->setPriorityName("Critical")
                    ->setIssueType("Задача")->setDescription($_REQUEST['text'])
                    //->addVersion(["1.0.1", "1.0.3"])
                    //->addComponents(['Component-1', 'Component-2'])
                    // set issue security if you need.
                    // ->setSecurityId(10001 /* security scheme id */)
                    //->setDueDate('2020-01-19')
                ;

                $issueService = new IssueService(new ArrayConfiguration($this->configurations));

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
        $desks = $this->curr_project->desks();
        return $this->render($this->loadTemplate(realpath('templates/projects/index.php'), ['projects' => $desks]));
    }

    function sync()
    {
        if ($this->curr_project) {
            try {
                $project_service = new ProjectService(new ArrayConfiguration($this->configurations));
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
            header('Location: /project/' . $this->curr_project->id . '/report');
            return;
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
        $issueService = new IssueService(new ArrayConfiguration($this->configurations));

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
                        'task_id' => $task->id,
                        'task_key' => $task->project_key,
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
        $daily_cost = $_REQUEST['daily_cost'] == 'false' || !isset($_REQUEST['daily_cost']);
        foreach ($projects as $project) {
            $tasks = $_REQUEST['all_tasks'] == 'true' ? $project->tasks() : $project->workedTasks($find['started >:'], $find['author_id'] ?? null);
//            $worklogs = [];
//            foreach ($tasks as $task){
//                $worklogs = array_merge($worklogs, Worklog::whereGet(array_merge($find, ['task_key LIKE:' => $project->jira_key . '%'])));
//            }
            $worklogs = Worklog::whereGet(array_merge($find, ['task_key LIKE:' => $project->jira_key . '%']));
            $time = Worklog::getWorklogsTime($worklogs);
            $costs = Cost::calculate($worklogs, $project, $user_id, $daily_cost);
            $output .= $this->loadTemplate(realpath('templates/projects/time_table.php'),
                [
                    'project_name' => $project->jira_key,
                    'tasks' => $tasks,
                    'time' => $time,
                    'days' => cal_days_in_month(CAL_GREGORIAN, $month, $year),
                    'date' => sprintf('%d-%s-', $year, $month),
                    'costs' => $costs,
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
        header('Location: /project/'.$this->curr_project->id.'/settings');
    }
}