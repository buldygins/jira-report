<?php


namespace YourResult\models;


class JiraTask extends Model
{
    public $project_key;
    public $summary;
    public $status;
    public $priority;
    public $description;

    public function getShortName()
    {
        if (strlen($this->summary) > 20){
            return substr($this->summary, 0, strpos($this->summary, ' ',20)) . '...';
        }
        return $this->summary;
    }

}