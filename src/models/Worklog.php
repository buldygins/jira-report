<?php


namespace YourResult\models;


class Worklog extends Model
{
    public $task_id;
    public $task_key;
    public $started;
    public $seconds_all;
    public $hours;
    public $minutes;
    public $time;
    public $author_id;

    public function getDayTime()
    {

    }

    public static function getWorklogsTime($worklogs)
    {
        $time = [];
        $all_time = 0;
        foreach ($worklogs as $worklog) {
            $date = date_parse($worklog->started);
            $date_key = $date['day'];
            if (isset($time[$worklog->task_key]['all'])) {
                $time[$worklog->task_key]['all'] += $worklog->seconds_all;
            } else {
                $time[$worklog->task_key]['all'] = $worklog->seconds_all;
            }
            if (isset($time[$worklog->task_key][$date_key])) {
                $time[$worklog->task_key][$date_key] += $worklog->seconds_all;
            } else {
                $time[$worklog->task_key][$date_key] = $worklog->seconds_all;
            }
            if (isset($time[$date_key]['all'])) {
                $time[$date_key]['all'] += $worklog->seconds_all;
            } else {
                $time[$date_key]['all'] = $worklog->seconds_all;
            }
            $all_time += $worklog->seconds_all;

        }
        foreach ($time as &$day_time) {
            foreach ($day_time as &$seconds) {
                $seconds = static::convertSecondsToTime($seconds);
            }
        }
        asort($time);
        $time['all'] = static::convertSecondsToTime($all_time);
        return $time;
    }

    public static function convertSecondsToTime($seconds){
        $hours = intdiv($seconds, 3600);
        $minutes = ($seconds - $hours * 3600) / 60;
        $seconds = $hours . 'ч';
        if ($minutes != 0) {
            $seconds .= ' ' . $minutes . 'м';
        }
        return $seconds;
    }

}