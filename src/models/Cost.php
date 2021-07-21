<?php


namespace YourResult\models;


class Cost extends SettingsField
{

    public static function calculate($worklogs, $project, $user_id = null, $daily = true)
    {
        $name = $daily ? 'COST_DAY' : 'COST_HOUR';
        if ($user_id){
            $costs[] = self::find(['name' => $name . '_U_' . $user_id, 'project_id' => $project->project_id]);
        }
        if ($project){
            $costs[] = self::find(['name' => $name . '_P_' . $project->id, 'project_id' => $project->project_id]);
        }
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
                if (strpos($cost->name, '_U_') !== false) {
                    $additional = '( индивидуальная )';
                } elseif (strpos($cost->name, '_P_') !== false) {
                    $additional = '( проектная )';
                } else {
                    $additional = '( базовая )';
                }
                $return[] = ['calculated' => count($days_worked) * $cost->value, 'rate' => $rate, 'additional' => $additional];
            }
        } else {
            $all_time = 0;
            foreach ($worklogs as $worklog) {
                $all_time += $worklog->seconds_all;
            }
            $all_time = $all_time / 3600;
            foreach ($costs as $cost) {
                $rate = $cost->value . ' р/ч';
                if (strpos($cost->name, '_U_') !== false) {
                    $additional = '( индивидуальная )';
                } elseif (strpos($cost->name, '_P_') !== false) {
                    $additional = '( проектная )';
                } else {
                    $additional = '( базовая )';
                }
                $return[] = ['calculated' => $all_time * $cost->value, 'rate' => $rate, 'additional' => $additional];
            }

        }
        return $return;
    }
}