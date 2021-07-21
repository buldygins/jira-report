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
        if (strpos($this->name, 'COST') !== false && !in_array($this->name, ['COST_HOUR', 'COST_DAY'])) {
            return sprintf('<div class="form-group">%s<button type="button" id="delete_cost" data-url="/setting/%d/delete" class="close text-danger" aria-label="Close">
  <span aria-hidden="true">&times;</span>
</button><input class="form-control" type="text" name="settings[%s]" value="%s">

</div>',
                $this->title, $this->id, $this->id, $this->value);
        }
        switch ($this->type) {
            case 'checkbox':
                $checked = $this->value == 'on' ? 'checked' : '';
                return sprintf('<div class="form-check"><input class="form-check-input" type="checkbox" name="settings[%s]" %s><label>%s</label></div>',
                    $this->id, $checked, $this->title);
            case 'text':
            default:
                return sprintf('<div class="form-group"><label>%s<input class="form-control" type="text" name="settings[%s]" value="%s"></label></div>',
                    $this->title, $this->id, $this->value);
        }
    }
}