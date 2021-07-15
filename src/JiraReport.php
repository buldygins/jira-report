<?php

namespace YourResult;

use JiraRestApi\Project\ProjectService;
use PDO;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\User\UserService;

class JiraReport extends \YourResult\MicroService
{
    public $issueService;

    public function __construct(PDO $db = NULL)
    {
        $this->issueService = $issueService = new IssueService();
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
            case 'settings':
                $this->settings();
                break;
            case 'project':
                $this->project();
                break;
            case 'getDesk':
                $this->getDesk();
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
        $sql = "SELECT * FROM projects";
        $rows = $this->query($sql)->fetchAll();
        $output .= $this->loadTemplate(realpath('templates/projects/project_table.php'), ['projects' => $rows]);
        $this->render($output);

    }

    function project()
    {
        try {
            $proj = new ProjectService();
            $prjs = $proj->getAllProjects();
        } catch (JiraRestApi\JiraException $e) {
            print("Error Occured! " . $e->getMessage());
        }
        $output = $this->loadTemplate('templates/projects/index.php', ['projects' => $prjs]);
        $this->render($output);
    }

    function getDesk(){

        $jql[] = 'project = ' . $_GET['desk'];

        $report_month = date('m');
        if (isset($_REQUEST['month'])) {
            $report_month = $_REQUEST['month'];
        }

        $dateFrom = date('Y') . '-' . $report_month . '-01';  //-m-01'); //, '');//'2020-01-01';

        $report_month_str = date('F', strtotime($dateFrom));

        $dateTo = date('Y-m-d', strtotime('last day of ' . $report_month_str));

        if (boolval($_ENV['REPORT_SHOW_LOGGED_ONLY']) != false) {
            $jql[] = "worklogAuthor = currentUser() AND worklogDate >= \"$dateFrom\" AND worklogDate <= \"$dateTo\"";
        }

        $startAt = 0;    //the index of the first issue to return (0-based)
        $maxResult = 500;    // the maximum number of issues to return (defaults to 50).

        $jql = implode(' AND ',$jql);
        var_dump($jql);
        $res = $this->issueService->search($jql, $startAt, $maxResult);
        $this->makeReport($res);
        exit();
    }

    function getIssueWorklog(){

    }

//    function oldrun(){
//        if (!$jql){
//            $jql = $_ENV['JIRA_FILTER'];
//        }
//        //$jql= "key = MASTER-19";
////        if ($jql != '') {
////            $jql = $jql . ' and ';
////        }
//
//        // $currentDate = new \DateTime();
//        //$currentDate->modify('first day of next month');
//        //echo $d->format( 'Y-m-d' ), "\n";
//        //$dateTo=$currentDate->format('Y-m-d');
//
//
//        $report_month = date('m');
//        if (isset($_REQUEST['month'])) {
//            $report_month = $_REQUEST['month'];
//        }
//
//        $dateFrom = date('Y') . '-' . $report_month . '-01';  //-m-01'); //, '');//'2020-01-01';
//
//        $report_month_str = date('F', strtotime($dateFrom));
//
//        //$dateFrom='2020-01-01';
//        //$dateTo=$currentDate->format('Y-m-d'); //'2020-01-31';
//        //$dateTo='2020-01-31';
//
//        $dateTo = date('Y-m-d', strtotime('last day of ' . $report_month_str));
//        $date_part = date('Y-m-', strtotime('last day of ' . $report_month_str));
//
////        if (isset($_REQUEST['dateFrom'])) {$dateFrom=$_REQUEST['dateFrom'];}
////        if (isset($_REQUEST['dateTo'])) {$dateTo=$_REQUEST['dateTo'];}
////
////        echo $dateFrom;
////        exit;
//
//        if (boolval($_ENV['REPORT_SHOW_LOGGED_ONLY']) != false) {
//            $jql .= (!empty($jql)) ? ' AND ' : '';
//            $jql = $jql . "(worklogDate >  '$dateFrom') AND (worklogDate <= '$dateTo')";
//        } else {
//            //$jql = $jql . " AND (updated >  '$dateFrom') AND (updated <= '$dateTo')";
//            // $jql = $jql . " AND (updated >  '$dateFrom')";
//        }
//
//        // if (isset($_ENV['JIRA_FILTER_ORDER'])) { $jql=$jql.$_ENV['JIRA_FILTER_ORDER'];}
//
//        //$jql='assignee = currentUser()';
//        //$jql='issuekey = SVM-67';
//        //echo $jql;//exit;
//        $issueService = new IssueService();
//
//        //print_r($issueService); exit;
//
//        $startAt = 0;    //the index of the first issue to return (0-based)
//        $maxResult = 500;    // the maximum number of issues to return (defaults to 50).
//        $totalCount = 500;    // the number of issues to return
//
//        $ret = $issueService->search($jql, $startAt, $maxResult);
//
//        //print_r($ret); exit;
//    }

    function makeReport($search_result){

        foreach ($search_result->issues as $issue) {
            //print_r($issue);
            if (
                ($issue->fields->assignee->name == $_ENV['JIRA_USER']) ||
                ($issue->fields->watches->isWatching >= 0)
            ) {
                $arIssues[$issue->key] = 1;
            }
        }

        $keys = array_keys($arIssues);


//print_r($keys);//exit;

//
        $issueService = $this->issueService;

        $logs01 = [];
        $logs02 = [];
        $j = 0;
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
//            print_r($issue->fields);

            if ($_ENV['JIRA_REST_API_V3'] != 'true') {
                $timetracking = $issueService->getTimeTracking($key);
            } else {
                $timetracking = $issue->timeTracking;
            }
//            var_dump($timetracking); exit;

            if ($key == 'SVM-67') {

// description
// priority name iconUrl
// status name
// status statuscategory colorName

//[customFields][customfield_10300]
//print_r($issue);
//exit;

            }


            unset($log);

            if (count($issue->fields->worklog->worklogs) == 0) {

                //echo "111"; exit;
                // foreach ($issue->fields as $issue_fields)
                {
                    $j++;

                    $log['summary'] = $issue->fields->summary;
                    $log['description'] = $issue->fields->description;
                    if (isset($issue->fields->customFields['customfield_10300'])) {
                        $log['ticket'] = $issue->fields->customFields['customfield_10300'];
                    }
                    $log['status'] = $issue->fields->status->name;
                    $log['status_color'] = $issue->fields->status->statuscategory->colorName;
                    $log['priority'] = $issue->fields->priority->name;
                    $log['priority_id'] = $issue->fields->priority->id;
                    $log['priority_icon'] = $issue->fields->priority->iconUrl;


//$log['time']=.$log['minutes'].'мин. ';
                    $log['key'] = $key;

                    $currentDate = $log['priority_id'] . 'xxx_' . $key . '_' . $j;

                    $logs01[$currentDate][$key][$log['day']] = $log;
//-----
                    $arkeys[$key]['summary'] = $log['summary'];
                    $arkeys[$key]['ticket'] = $log['ticket'];
                    $arkeys[$key]['seconds_all'] = 0;
//$arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $timetracking->timeSpentSeconds;

                    $arkeys[$key]['hours'] = 0;
                    $arkeys[$key]['minutes'] = 0;

                    $arkeys[$key]['time'] = '';
                }
            } else {
                //echo "222";
                //

                if (count($issue->fields->worklog->worklogs) >= 5) {
                    $worklogs = $issueService->getWorklog($key)->getWorklogs();
                } else {
                    $worklogs = $issue->fields->worklog->worklogs;
                }
//                print_r($worklogs);
//                exit;
                foreach ($worklogs as $worklog) {


                    $wl_key = $_ENV['JIRA_FLD_WORKLOG_AUTHOR'];
                    $a = (array)$worklog->author;

                    //echo $a['name']. ' ';
//                        var_dump($_ENV['JIRA_USER']);
                    //

                    if ((($_ENV['JIRA_FLD_WORKLOG_ANY_AUTHOR'] == 0) && ($a[$wl_key] == $_ENV['JIRA_USER']))
                        || ($_ENV['JIRA_FLD_WORKLOG_ANY_AUTHOR'] == 1)
                    ) {
                        $j++;


                        $log['summary'] = $key . ' ' . $issue->fields->summary;
                        $log['description'] = $issue->fields->description;
                        if (isset($issue->fields->customFields['customfield_10300'])) {
                            $log['ticket'] = $issue->fields->customFields['customfield_10300'];
                        }
                        $log['status'] = $issue->fields->status->name;
                        $log['status_color'] = $issue->fields->status->statuscategory->colorName;
                        $log['priority'] = $issue->fields->priority->name;
                        $log['priority_id'] = $issue->fields->priority->id;
                        $log['priority_icon'] = $issue->fields->priority->iconUrl;


                        $log['comment'] = $worklog->comment;
//$log['created']=$worklog->created;
                        $log['started'] = $worklog->started;
                        $log['day'] = date('d', strtotime($worklog->started));
                        $log['month'] = date('m', strtotime($worklog->started));
                        $log['rand'] = $j;
                        $log['seconds_all'] = $worklog->timeSpentSeconds;
                        $log['hours'] = intdiv($worklog->timeSpentSeconds, 3600);
                        $log['minutes'] = ($worklog->timeSpentSeconds - $log['hours'] * 3600) / 60;


                        $log['time'] = '';
                        if ($log['hours'] > 0) {
                            $log['time'] .= $log['hours'] . 'ч ';
                        }
                        if ($log['minutes'] > 0) {
                            $log['time'] .= $log['minutes'] . 'м. ';
                        }

//$log['time']=.$log['minutes'].'мин. ';
                        $log['key'] = $key;

                        $currentDate = $log['priority_id'] . $log['day'] . '_' . $key . '_' . $j;

                        if ($log['month'] == $report_month) {
                            $logs02[$currentDate][$key][$log['day']] = $log;
//-----
                            $arkeys[$key]['summary'] = $log['summary'];
                            $arkeys[$key]['ticket'] = $log['ticket'];
                            $arkeys[$key]['seconds_all'] = $timetracking->timeSpentSeconds;
//$arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $timetracking->timeSpentSeconds;

                            $arkeys[$key]['hours'] = intdiv($timetracking->timeSpentSeconds, 3600);
                            $arkeys[$key]['minutes'] = ($timetracking->timeSpentSeconds - $arkeys[$key]['hours'] * 3600) / 60;

                            $arkeys[$key]['time'] = '';
                            if ($arkeys[$key]['hours'] > 0) {
                                $arkeys[$key]['time'] .= $arkeys[$key]['hours'] . 'ч ';
                            }
                            if ($arkeys[$key]['minutes'] > 0) {
                                $arkeys[$key]['time'] .= $arkeys[$key]['minutes'] . 'м. ';
                            }

                        }
                    }
                }
            }


        }


        //print_r($logs01);
        //echo "-------------------";
        //print_r($logs02);
//
        //exit;
        ksort($logs01);
        ksort($logs02);

        if (count($logs01) == 0) {
            $logs = $logs02;
        } elseif (count($logs02) == 0) {
            $logs = $logs01;
        } else {
            $logs = array_merge($logs01, $logs02);
        }
        //print_r($logs);
        $items = [];
        foreach ($logs as $log) {

            foreach ($log as $key => $item) {
                foreach ($item as $day => $item01) {
                    $items[$key][$day] = $item01;
                }
            }
        }

        foreach ($logs as $log) {
            foreach ($log as $key => $item) {
                foreach ($item as $day => $item01) {

                    $items['ITOG'][$day]['summary'] = 'Итог';
                    $items['ITOG'][$day]['day'] = $day;
                    $items['ITOG'][$day]['seconds_all'] = $items['ITOG'][$day]['seconds_all'] + $item01['seconds_all'];

                    $items['ITOG'][$day]['hours'] = intdiv($items['ITOG'][$day]['seconds_all'], 3600);
                    $items['ITOG'][$day]['minutes'] = ($items['ITOG'][$day]['seconds_all'] - $items['ITOG'][$day]['hours'] * 3600) / 60;

                    $items['ITOG'][$day]['time'] = '';
                    if ($items['ITOG'][$day]['hours'] > 0) {
                        $items['ITOG'][$day]['time'] .= $items['ITOG'][$day]['hours'] . 'ч ';
                    }
                    if ($items['ITOG'][$day]['minutes'] > 0) {
                        $items['ITOG'][$day]['time'] .= $items['ITOG'][$day]['minutes'] . 'м. ';
                    }
                }
            }
        }

        if (empty($item['ITOG'])) {
            $item['ITOG'] = [];
        }
        $arkeys['ITOG']['seconds_all'] = 0;
        foreach ($items['ITOG'] as $day => $item) {
            $arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $item['seconds_all'];
        }


        $arkeys['ITOG']['summary'] = 'Итог';
        $arkeys['ITOG']['hours'] = intdiv($arkeys['ITOG']['seconds_all'], 3600);
        $arkeys['ITOG']['minutes'] = ($arkeys['ITOG']['seconds_all'] - $arkeys['ITOG']['hours'] * 3600) / 60;

        $arkeys['ITOG']['time'] = '';
        if ($arkeys['ITOG']['hours'] > 0) {
            $arkeys['ITOG']['time'] .= $arkeys['ITOG']['hours'] . 'ч ';
        }
        if ($arkeys['ITOG']['minutes'] > 0) {
            $arkeys['ITOG']['time'] .= $arkeys['ITOG']['minutes'] . 'м. ';
        }
//print_r($items);exit;


        if ($_ENV['REPORT_SHOW_TASK'] == 'show') {
            echo "<a href='/addTask?project={$_REQUEST['project']}'><button>Создать задачу</button></a><br><br>";
        }

        echo "<table style='border: 1px solid gray;'>";
        echo "<tr bgcolor='silver'><td colspan='2'>Задача</td>";
        for ($i = 1; $i <= 31; $i++) {
            echo "<td style='min-width: 20px;text-align: center'>$i</td>";
        }
        if ($_ENV['REPORT_SHOW_TRUD'] != 'hide') {
            echo '<td>Трудоемкость</td>';
        }
        if ($_ENV['REPORT_SHOW_STATUS'] == 'show') {
            echo '<td>Приоритет</td>';
            echo '<td>Статус</td>';
        }
        echo '</tr>';

        $ii = 0;
        foreach ($items as $kitem => $ditem) {
            $ii++;
            $bglastline = 'silver';
            if ($ii == count($items)) {
                echo '<tr style="background-color: ' . $bglastline . '">';
            } else {
                echo '<tr>';
            }
            echo "<td style='border-bottom: 1px solid gray'>";

            if ($_ENV['LINK_OUR'] == 'true') {
                echo "<nobr><a target='_blank' href='{$_ENV['JIRA_HOST']}/browse/$kitem'>$kitem</a></nobr>&nbsp;";
            }

            if ($_ENV['LINK_CLIENT'] == 'true') {
                if (trim($arkeys[$kitem]['ticket']) != '') {
                    echo "<nobr><a target='_blank' href='{$_ENV['LINK_CLIENT_BASE']}{$arkeys[$kitem]['ticket']}'>{$arkeys[$kitem]['ticket']}</a></nobr>";
                }
            }

            echo "</td>";
            echo "<td style='border-bottom: 1px solid gray'>{$arkeys[$kitem]['summary']}</td>";


            for ($i = 1; $i <= 31; $i++) {
                $bgcolor = 'white';
                $w = date('N', strtotime($date_part . $i));
                if ($w >= 6) {
                    $bgcolor = '#DCDCDC';
                }

                if ($ii == count($items)) {
                    $bgcolor = $bglastline;
                }


                $cell = '';
                foreach ($ditem as $day => $item) {
                    if ($item['day'] == $i) {
                        if ($_ENV['REPORT_SHOW_TIME'] == 'hide') {
                            $cell = ' ';
                            $bgcolor = '#b0de98';
                        } else {
                            $cell = $item['time'];
                            $bgcolor = '#b0de98';
                        }
                    }


                    //
                    //
                    //            if ($item['day'] == $i) {
                    //                echo "<td style='border: 1px solid gray; background-color: bisque'>{$item['time']}</td>";
                    //            } elseif ($w >= 6) {
                    //                echo "<td style='border: 1px solid gray; background-color: darksalmon'></td>";
                    //            } else {
                    //                echo "<td style='border: 1px solid gray'></td>";
                    //            }
                }

                echo "<td style='padding:2px; text-align: center; border: 1px solid gray; background-color: $bgcolor;'>";
                echo "<nobr>$cell</nobr>";
                echo '</td>';
            }

            if ($_ENV['REPORT_SHOW_TRUD'] != 'hide') {
                if ($_ENV['REPORT_SHOW_TIME'] != 'hide') {
                    echo "<td style='border-bottom: 1px solid gray;border-left: 1px solid gray;text-align: center;'>";
                    echo $arkeys[$kitem]['time'];
                    echo "</td>";

                } elseif ((0 + $arkeys[$kitem]['time']) > 0) {
                    echo "<td style='border-bottom: 1px solid gray;border-left: 1px solid gray;text-align: center;'>";
                    echo "</td>";

                } else {
                    echo "<td style='border-bottom: 1px solid gray;border-left: 1px solid gray;text-align: center;'>";
                    echo "</td>";
                }

            }

            if ($_ENV['REPORT_SHOW_STATUS'] == 'show') {
                echo '<td style="text-align:center; border: 1px solid gray;">';
                if ($item['priority_icon'] != '') {
                    echo "<img src='{$item['priority_icon']}' title='{$item['priority']}' height='30px' width='30px' />";
                }
                echo '</td>';

                echo '<td style="padding: 5px; text-align:center; border: 1px solid gray; background-color: ' . $item['status_color'] . '">';
                echo "<nobr>{$item['status']}</nobr>";
                echo '</td>';
            }

            echo '</tr>';
        }


        echo "</table>";

//print_r($arkeys['ITOG']);
        if ($_ENV['REPORT_SHOW_COST'] != 'hide') {
            if (isset($_ENV['COST_MONTH']) && ($_ENV['COST_MONTH'] > 0)) {
                $stavka = $_ENV['COST_MONTH'];
                echo "Оплата в месяц: $stavka руб.";
            } elseif (isset($_ENV['COST_HOUR']) && ($_ENV['COST_HOUR'] > 0)) {
                $stavka = $_ENV['COST_HOUR'];
                $hours = $arkeys['ITOG']['hours'] + $arkeys['ITOG']['minutes'] / 60;
                $summ = $hours * $stavka;
                echo "Часовая ставка: $stavka руб. Стоимость: <b>" . $summ . '</b> руб.';
            }
        }
    }


    function getIssueInfo()
    {
        try {
            $issueService = new IssueService();

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

            $issue = $issueService->get('ORG-12', $queryParam);

            print_r($issue->fields->worklog->worklogs);
        } catch (JiraException $e) {
            print("Error Occured! " . $e->getMessage());
        }

    }

    function getTimeTracking()
    {
        try {
            $issueService = new IssueService();

// get issue's time tracking info
            $ret = $issueService->getTimeTracking($issueKey);
            print_r($ret);

//    $timeTracking = new TimeTracking;
//
//    $timeTracking->setOriginalEstimate('3w 4d 6h');
//    $timeTracking->setRemainingEstimate('1w 2d 3h');
//
//    // add time tracking
//    $ret = $issueService->timeTracking($this->issueKey, $timeTracking);
//    var_dump($ret);
        } catch (JiraException $e) {
            $this->assertTrue(false, 'testSearch Failed : ' . $e->getMessage());
        }
    }

    function issuesearch()
    {
        $jql = 'assignee = currentUser()';

        try {
            $issueService = new IssueService();

            $ret = $issueService->search($jql);
            print_r($ret);
        } catch (JiraException $e) {
            $this->assertTrue(false, 'testSearch Failed : ' . $e->getMessage());
        }
    }


    function getUser()
    {
        try {
            $us = new UserService();

            $user = $us->get(['username' => 'v.smorodinsky']);

            print_r($user);
        } catch (JiraException $e) {
            print("Error Occured! " . $e->getMessage());
        }
    }

    function query($sql)
    {
        return $this->db->query($sql, PDO::FETCH_ASSOC);
    }

    function settings()
    {
        $output = '';
        $setting_id_sql = "SELECT value FROM settings WHERE setting_field_id = 1";
        $setting_id = $this->query($setting_id_sql)->fetch();
        $all_projects = $this->query("SELECT nam, id FROM projects ORDER BY id")->fetchAll();
        $output .= '<select id="project-settings">';
        foreach ($all_projects as $project) {
            $output .= '<option value="' . $project['id'] . '">' . $project['nam'] . '</option>';
        }
        $output .= '</select>';

        return $this->render($output);
    }

    function render($output = ''){
        include 'templates/index.php';
    }

    function loadTemplate($template_name,array $data, $output = ''){
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
}