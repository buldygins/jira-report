<?php


namespace YourResult\models;


class SettingsField extends Model
{
    public $name;
    public $title;
    public $value = null;
    public $type = 'text';
    public $project_id;

    public function renderField()
    {
        switch ($this->type) {
            case 'checkbox':
                $checked = $this->value == 'on' ? 'checked' : '';
                return sprintf('<div class="form-group"><label>%s<input class="form-control" type="checkbox" name="settings[%s]" %s></label></div>',
                    $this->title, $this->id, $checked);
            case 'text':
            default:
                return sprintf('<div class="form-group"><label>%s<input class="form-control" type="text" name="settings[%s]" value="%s"></label></div>',
                    $this->title, $this->id, $this->value);
        }
    }
}