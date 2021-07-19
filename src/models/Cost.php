<?php


namespace YourResult\models;


class Cost extends SettingsField
{

    public static function calculate($worklogs, $project, $user_id, $daily = true)
    {
        $name = $daily ?'COST_DAY' : 'COST_HOUR';
        $cost = self::find(['name' => $name . '_U_' . $user_id, 'project_id' => $project->project_id]);
        if (!$cost) {
            $cost = self::find(['name' => $name . '_P_' . $project->id, 'project_id' => $project->project_id]);
            if (!$cost) {
                $cost = self::find(['name' => $name, 'project_id' => $project->project_id]);
                if (!$cost) {
                    $cost = new self(['value' => 0]);
                }
            }
        }
        $all_time = 0;
        foreach ($worklogs as $worklog) {
            $all_time += $worklog->seconds_all;
        }
        $all_time = $all_time / 3600;
        $rate = $cost->value;
        $rate .= $daily ? ' р/д' : ' р/ч';
        return ['calculated' => $all_time * $cost->value, 'rate' => $rate];
    }
}