<?php

namespace YourResult;

use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\User\UserService;

class JiraReport extends \YourResult\MicroService
{

    function addTask()
    {
        ?>


        <?
    }

    function run()
    {
        $jql = $_ENV['JIRA_FILTER'];
        //$jql= "key = MASTER-19";
//        if ($jql != '') {
//            $jql = $jql . ' and ';
//        }

        $currentDate = new \DateTime();
        $currentDate->modify('first day of next month');
        //echo $d->format( 'Y-m-d' ), "\n";
        //$dateTo=$currentDate->format('Y-m-d');


        $report_month = date('m');
        $dateFrom = date('Y-m-01'); //, '');//'2020-01-01';
        //$dateFrom='2020-01-01';
        //$dateTo=$currentDate->format('Y-m-d'); //'2020-01-31';
        //$dateTo='2020-01-31';

        $dateTo = date('Y-m-d', strtotime('last day of this month'));
        $date_part = date('Y-m-');

        //echo $dateTo;
        //exit;

        if ($_ENV['REPORT_SHOW_LOGGED_ONLY'] != false)
        {
            $jql = $jql . " AND (worklogDate >  '$dateFrom') AND (worklogDate <= '$dateTo')";
        }
        else
        {
            $jql = $jql . " AND (updated >  '$dateFrom') AND (updated <= '$dateTo')";
        }

        // if (isset($_ENV['JIRA_FILTER_ORDER'])) { $jql=$jql.$_ENV['JIRA_FILTER_ORDER'];}
        //echo $jql;exit;
        $issueService = new IssueService();

        $startAt = 0;    //the index of the first issue to return (0-based)
        $maxResult = 500;    // the maximum number of issues to return (defaults to 50).
        $totalCount = -1;    // the number of issues to return

        $ret = $issueService->search($jql, $startAt, $maxResult);
        foreach ($ret->issues as $issue)
        {
            $arIssues[$issue->key] = 1;
        }

        $keys = array_keys($arIssues);

//print_r($keys);exit;

//

        $logs01 = [];
        $logs02 = [];
        $j = 0;
        foreach ($keys as $key)
        {

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

            if ($key == 'ORG-14')
            {
                //print_r($issue->fields->worklog);
// description
// priority name iconUrl
// status name
// status statuscategory colorName

//print_r($ret2);
                //exit;
            }

            unset($log);
            if (count($issue->fields->worklog->worklogs) == 0)
            {

                //echo "111"; exit;
                // foreach ($issue->fields as $issue_fields)
                {
                    $j++;

                    $log['summary'] = $issue->fields->summary;
                    $log['description'] = $issue->fields->description;
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
                    $arkeys[$key]['seconds_all'] = 0;
//$arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $timetracking->timeSpentSeconds;

                    $arkeys[$key]['hours'] = 0;
                    $arkeys[$key]['minutes'] = 0;

                    $arkeys[$key]['time'] = '';
                }
            }
            else
            {
                //echo "222"; exit;
                foreach ($issue->fields->worklog->worklogs as $worklog)
                {
                    $wl_key = $_ENV['JIRA_FLD_WORKLOG_AUTHOR'];
                    $a = (array)$worklog->author;
                    //print_r($a); exit;
                    if ((($_ENV['JIRA_FLD_WORKLOG_ANY_AUTHOR'] == 0) && ($a[$wl_key] == $_ENV['JIRA_USER']))
                        || ($_ENV['JIRA_FLD_WORKLOG_ANY_AUTHOR'] == 1)
                    )
                    {
                        $j++;

                        $log['summary'] = $issue->fields->summary;
                        $log['description'] = $issue->fields->description;
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
                        if ($log['hours'] > 0)
                        {
                            $log['time'] .= $log['hours'] . 'ч ';
                        }
                        if ($log['minutes'] > 0)
                        {
                            $log['time'] .= $log['minutes'] . 'м. ';
                        }

//$log['time']=.$log['minutes'].'мин. ';
                        $log['key'] = $key;

                        $currentDate = $log['priority_id'] . $log['day'] . '_' . $key . '_' . $j;

                        if ($log['month'] == $report_month)
                        {
                            $logs02[$currentDate][$key][$log['day']] = $log;
//-----
                            $arkeys[$key]['summary'] = $log['summary'];
                            $arkeys[$key]['seconds_all'] = $timetracking->timeSpentSeconds;
//$arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $timetracking->timeSpentSeconds;

                            $arkeys[$key]['hours'] = intdiv($timetracking->timeSpentSeconds, 3600);
                            $arkeys[$key]['minutes'] = ($timetracking->timeSpentSeconds - $arkeys[$key]['hours'] * 3600) / 60;

                            $arkeys[$key]['time'] = '';
                            if ($arkeys[$key]['hours'] > 0)
                            {
                                $arkeys[$key]['time'] .= $arkeys[$key]['hours'] . 'ч ';
                            }
                            if ($arkeys[$key]['minutes'] > 0)
                            {
                                $arkeys[$key]['time'] .= $arkeys[$key]['minutes'] . 'м. ';
                            }

                        }
                    }
                }
            }


        }


//        print_r($logs01);
//        echo "-------------------";
//        print_r($logs02);
//
        //exit;
        ksort($logs01);
        ksort($logs02);

        if (count($logs01) == 0)
        {
            $logs = $logs02;
        }
        elseif (count($logs02) == 0)
        {
            $logs = $logs01;
        }
        else
        {
            $logs = array_merge($logs01, $logs02);
        }
        //print_r($logs);
        $items = [];
        foreach ($logs as $log)
        {

            foreach ($log as $key => $item)
            {
                foreach ($item as $day => $item01)
                {
                    $items[$key][$day] = $item01;
                }
            }
        }

        foreach ($logs as $log)
        {
            foreach ($log as $key => $item)
            {
                foreach ($item as $day => $item01)
                {

                    $items['ITOG'][$day]['summary'] = 'Итог';
                    $items['ITOG'][$day]['day'] = $day;
                    $items['ITOG'][$day]['seconds_all'] = $items['ITOG'][$day]['seconds_all'] + $item01['seconds_all'];

                    $items['ITOG'][$day]['hours'] = intdiv($items['ITOG'][$day]['seconds_all'], 3600);
                    $items['ITOG'][$day]['minutes'] = ($items['ITOG'][$day]['seconds_all'] - $items['ITOG'][$day]['hours'] * 3600) / 60;

                    $items['ITOG'][$day]['time'] = '';
                    if ($items['ITOG'][$day]['hours'] > 0)
                    {
                        $items['ITOG'][$day]['time'] .= $items['ITOG'][$day]['hours'] . 'ч ';
                    }
                    if ($items['ITOG'][$day]['minutes'] > 0)
                    {
                        $items['ITOG'][$day]['time'] .= $items['ITOG'][$day]['minutes'] . 'м. ';
                    }
                }
            }
        }


        $arkeys['ITOG']['seconds_all'] = 0;
        foreach ($items['ITOG'] as $day => $item)
        {
            $arkeys['ITOG']['seconds_all'] = $arkeys['ITOG']['seconds_all'] + $item['seconds_all'];
        }


        $arkeys['ITOG']['summary'] = 'Итог';
        $arkeys['ITOG']['hours'] = intdiv($arkeys['ITOG']['seconds_all'], 3600);
        $arkeys['ITOG']['minutes'] = ($arkeys['ITOG']['seconds_all'] - $arkeys['ITOG']['hours'] * 3600) / 60;

        $arkeys['ITOG']['time'] = '';
        if ($arkeys['ITOG']['hours'] > 0)
        {
            $arkeys['ITOG']['time'] .= $arkeys['ITOG']['hours'] . 'ч ';
        }
        if ($arkeys['ITOG']['minutes'] > 0)
        {
            $arkeys['ITOG']['time'] .= $arkeys['ITOG']['minutes'] . 'м. ';
        }
//print_r($items);exit;


        echo "<a href='addTask?project={$_REQUEST['project']}'><button>Создать задачу</button></a><br><br>";

        echo "<table style='border: 1px solid gray;'>";
        echo "<tr bgcolor='silver'><td colspan='2'>Задача</td>";
        for ($i = 1; $i <= 31; $i++)
        {
            echo "<td style='min-width: 20px;text-align: center'>$i</td>";
        }
        if ($_ENV['REPORT_SHOW_TRUD'] != 'hide')
        {
            echo '<td>Трудоемкость</td>';
        }
        if ($_ENV['REPORT_SHOW_STATUS'] == 'show')
        {
            echo '<td>Приоритет</td>';
            echo '<td>Статус</td>';
        }
        echo '</tr>';

        $ii = 0;
        foreach ($items as $kitem => $ditem)
        {
            $ii++;
            $bglastline = 'silver';
            if ($ii == count($items))
            {
                echo '<tr style="background-color: ' . $bglastline . '">';
            }
            else
            {
                echo '<tr>';
            }
            echo "<td style='border-bottom: 1px solid gray'>
                    <nobr><a target='_blank' href='{$_ENV['JIRA_HOST']}/browse/$kitem'>$kitem</a></nobr>
                  </td>";
            echo "<td style='border-bottom: 1px solid gray'>{$arkeys[$kitem]['summary']}</td>";


            for ($i = 1; $i <= 31; $i++)
            {
                $bgcolor = 'white';
                $w = date('N', strtotime($date_part . $i));
                if ($w >= 6)
                {
                    $bgcolor = '#DCDCDC';
                }

                if ($ii == count($items))
                {
                    $bgcolor = $bglastline;
                }


                $cell = '';
                foreach ($ditem as $day => $item)
                {
                    if ($item['day'] == $i)
                    {
                        if ($_ENV['REPORT_SHOW_TIME'] == 'hide')
                        {
                            $cell = ' ';
                            $bgcolor = '#b0de98';
                        }
                        else
                        {
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

            if ($_ENV['REPORT_SHOW_TRUD'] != 'hide')
            {
                if ($_ENV['REPORT_SHOW_TIME'] != 'hide')
                {
                    echo "<td style='border-bottom: 1px solid gray;border-left: 1px solid gray;text-align: center;'>";
                    echo $arkeys[$kitem]['time'];
                    echo "</td>";

                }
                elseif ((0 + $arkeys[$kitem]['time']) > 0)
                {
                    echo "<td style='border-bottom: 1px solid gray;border-left: 1px solid gray;text-align: center;'>";
                    echo "</td>";

                }
                else
                {
                    echo "<td style='border-bottom: 1px solid gray;border-left: 1px solid gray;text-align: center;'>";
                    echo "</td>";
                }

            }

            if ($_ENV['REPORT_SHOW_STATUS'] == 'show')
            {
                echo '<td style="text-align:center; border: 1px solid gray;">';
                if ($item['priority_icon'] != '')
                {
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
        if ($_ENV['REPORT_SHOW_COST'] != 'hide')
        {
            if (isset($_ENV['COST_MONTH']) && ($_ENV['COST_MONTH'] > 0))
            {
                $stavka = $_ENV['COST_MONTH'];
                echo "Оплата в месяц: $stavka руб.";
            }
            elseif (isset($_ENV['COST_HOUR']) && ($_ENV['COST_HOUR'] > 0))
            {
                $stavka = $_ENV['COST_HOUR'];
                $hours = $arkeys['ITOG']['hours'] + $arkeys['ITOG']['minutes'] / 60;
                $summ = $hours * $stavka;
                echo "Часовая ставка: $stavka руб. Стоимость: <b>" . $summ . '</b> руб.';
            }
        }
    }


    function getIssueInfo()
    {
        try
        {
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
        } catch (JiraException $e)
        {
            print("Error Occured! " . $e->getMessage());
        }

    }

    function getTimeTracking()
    {
        try
        {
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
        } catch (JiraException $e)
        {
            $this->assertTrue(false, 'testSearch Failed : ' . $e->getMessage());
        }
    }

    function issuesearch()
    {
        $jql = 'assignee = currentUser()';

        try
        {
            $issueService = new IssueService();

            $ret = $issueService->search($jql);
            print_r($ret);
        } catch (JiraException $e)
        {
            $this->assertTrue(false, 'testSearch Failed : ' . $e->getMessage());
        }
    }


    function getUser()
    {
        try
        {
            $us = new UserService();

            $user = $us->get(['username' => 'v.smorodinsky']);

            print_r($user);
        } catch (JiraException $e)
        {
            print("Error Occured! " . $e->getMessage());
        }
    }
}