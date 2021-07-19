<?php


namespace YourResult\models;


class Cost extends SettingsField
{

    public static function calculate($worklogs, $project, $user_id, $daily = true)
    {
        $name = $daily ? 'COST_DAY' : 'COST_HOUR';
        $costs[] = self::find(['name' => $name . '_U_' . $user_id, 'project_id' => $project->project_id]);
        $costs[] = self::find(['name' => $name . '_P_' . $project->id, 'project_id' => $project->project_id]);
        $costs[] = self::find(['name' => $name, 'project_id' => $project->project_id]);
        $costs = array_filter($costs, function ($el) {
            return $el != false;
        });
        if (empty($costs)) {
            $costs[] = new self(['value' => 0]);
        }

        if ($daily) {
            $days_worked = [];
            foreach ($worklogs as $worklog) {
                $days_worked[$worklog->task_key][] = date('d', strtotime($worklog->started));
            }
            foreach ($days_worked as &$days) {
                $days = array_unique($days);
            }
            foreach ($costs as $cost) {
                $rate = $cost->value . ' р/д';
                $return[] = ['calculated' => count($days_worked) * $cost->value, 'rate' => $rate];
            }
        } else {
            $all_time = 0;
            foreach ($worklogs as $worklog) {
                $all_time += $worklog->seconds_all;
            }
            $all_time = $all_time / 3600;
            foreach ($costs as $cost) {
                $rate = $cost->value . ' р/ч';
                $return[] = ['calculated' => $all_time * $cost->value, 'rate' => $rate];
            }

        }
        return $return;
    }
}