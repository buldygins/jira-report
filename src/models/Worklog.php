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
        foreach ($worklogs as $worklog) {
            $date = date_parse($worklog->started);
            $date_key = $date['day'];
            if (isset($time[$date_key][$worklog->task_key])) {
                $time[$worklog->task_key][$date_key] += $worklog->seconds_all;
            } else {
                $time[$worklog->task_key][$date_key] = $worklog->seconds_all;
            }
        }
        asort($time);
        return $time;
    }

}