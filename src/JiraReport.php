<?php

namespace YourResult\JiraReport;

use JiraRestApi\JiraException;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\User\UserService;
use JiraRestApi\Issue\TimeTracking;

class JiraReport extends \YourResult\MicroService
{

    function run()
    {

        $jql = $_ENV['JIRA_FILTER'];
//        if ($jql != '') {
//            $jql = $jql . ' and ';
//        }
        // $jql = $jql . "worklogDate >  '2019-12-01' AND worklogDate <= '2019-12-30'";
        $issueService = new IssueService();

        $startAt = 0;    //the index of the first issue to return (0-based)
        $maxResult = 500;    // the maximum number of issues to return (defaults to 50).
        $totalCount = -1;    // the number of issues to return

        $ret = $issueService->search($jql, $startAt, $maxResult);
        foreach ($ret->issues as $issue) {
            $arIssues[$issue->key] = 1;
        }

        $keys = array_keys($arIssues);

//print_r($keys);
//exit;

//
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
            $timetracking = $issueService->getTimeTracking($key);
            //echo $timetracking; exit;

            if ($key == 'MASTER-19') {
//print_r($issue->fields);
// description
// priority name iconUrl
// status name
// status statuscategory colorName

//print_r($ret2);
// exit;
            }


            if (count($issue->fields->worklog->worklogs) > 0) {
                foreach ($issue->fields->worklog->worklogs as $worklog) {
                    $j++;
                    unset($log);

                    $log['summary'] = $issue->fields->summary;
                    $log['description'] = $issue->fields->description;
                    $log['status'] = $issue->fields->status->name;
                    $log['status_color'] = $issue->fields->status->statuscategory->colorName;
                    $log['priority'] = $issue->fields->priority->name;
                    $log['priority_icon'] = $issue->fields->priority->iconUrl;


                    $log['comment'] = $worklog->comment;
//$log['created']=$worklog->created;
                    $log['started'] = $worklog->started;
                    $log['day'] = date('d', strtotime($worklog->started));
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

                    $d = $log['day'] . '_' . $key . '_' . $j;

                    $logs[$d][$key][$log['day']] = $log;
//-----
                    $arkeys[$key]['summary'] = $log['summary'];
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

            else {
                foreach ($issue->fields as $issue_fields) {
                    $j++;
                    unset($log);

                    $log['summary'] = $issue->fields->summary;
                    $log['description'] = $issue->fields->description;
                    $log['status'] = $issue->fields->status->name;
                    $log['status_color'] = $issue->fields->status->statuscategory->colorName;
                    $log['priority'] = $issue->fields->priority->name;
                    $log['priority_icon'] = $issue->fields->priority->iconUrl;




//$log['time']=.$log['minutes'].'мин. ';
                    $log['key'] = $key;

                    $d = 'XXX' . '_' . $key . '_' . $j;

                    $logs[$d][$key][$log['day']] = $log;
//-----
                    $arkeys[$key]['summary'] = $log['summary'];
                    $arkeys[$key]['seconds_all'] = 0;
//$arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $timetracking->timeSpentSeconds;

                    $arkeys[$key]['hours'] = 0;
                    $arkeys[$key]['minutes'] = 0;

                    $arkeys[$key]['time'] = '';
                }
            }

        }


        ksort($logs);

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
//print_r($arkeys);exit;


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
            echo "<td style='border-bottom: 1px solid gray'><nobr>$kitem</nobr></td>";
            echo "<td style='border-bottom: 1px solid gray'>{$arkeys[$kitem]['summary']}</td>";


            for ($i = 1; $i <= 31; $i++) {
                $bgcolor = 'white';
                $w = date('N', strtotime('2019-12-' . $i));
                if ($w >= 6) {
                    $bgcolor = '#DCDCDC';
                }

                if ($ii == count($items)) {
                    $bgcolor = $bglastline;
                }
                echo "<td style='padding:2px; border: 1px solid gray; background-color: $bgcolor;'>";

                foreach ($ditem as $day => $item) {
                    if ($item['day'] == $i) {
                        if ($_ENV['REPORT_SHOW_TIME'] == 'hide') {
                            echo '+';
                        } else {
                            echo $item['time'];
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

                echo '</td>';
            }

            if ($_ENV['REPORT_SHOW_TRUD'] != 'hide') {
                echo "<td style='border-bottom: 1px solid gray;
border-left: 1px solid gray; 
text-align: center;'>";
                if ($_ENV['REPORT_SHOW_TIME'] != 'hide') {
                    echo $arkeys[$kitem]['time'];
                } elseif ((0 + $arkeys[$kitem]['time']) > 0) {
                    echo '+';
                }
                echo "</td>";
            }

            if ($_ENV['REPORT_SHOW_STATUS'] == 'show') {
                echo '<td style="text-align:center; border: 1px solid gray;">';
                if ($item['priority_icon']!='') {
                    echo "<img src='{$item['priority_icon']}' title='{$item['priority']}' height='30px' width='30px' />";
                }
                echo '</td>';

                echo '<td style="padding: 5px; text-align:center; border: 1px solid gray; background-color: '.$item['status_color'].'">';
                echo "<nobr>{$item['status']}</nobr>";
                echo '</td>';
            }

            echo '</tr>';
        }


        echo "</table>";

//print_r($arkeys['ITOG']);
        if ($_ENV['REPORT_SHOW_COST'] != 'hide') {
            $hours = $arkeys['ITOG']['hours'] + $arkeys['ITOG']['minutes'] / 60;
            $summ = $hours * $_ENV['COST_HOUR'];
            echo "Часовая ставка: $stavka руб. Стоимость: <b>" . $summ . '</b> руб.';
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
}