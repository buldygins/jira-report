<?php


namespace YourResult\models;


class Model
{
    /**
     * @var string
     */
    public $table;
    public $id;

    public function getTable()
    {
        return $this->table;
    }

    public function __construct($data = [])
    {
        foreach ((array)$data as $field => $value) {
            if (property_exists($this, $field)) {
                $this->$field = $value;
            } else {
                //TODO: throw
            }
        }
        $this->table = lcfirst(basename(static::class));
    }


    public
    static function getTableName()
    {
        if (get_parent_class(static::class) == self::class) {
            return Inflect::pluralize(strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', (new \ReflectionClass(static::class))->getShortName())));
        }
        return get_parent_class(static::class)::getTableName();
    }

    public
    static function find($id)
    {
        global $dbpdo;

        $where = [];
        if (!is_array($id)) {
            $id = intval($id);
            $where[] = 'id = ' . $id;
        } else {
            foreach ($id as $field_key => $value) {
                if (strpos($field_key, ':') !== false) {
                    $field_key = substr($field_key, 0, -1);
                    $where[] = sprintf("%s '%s'", $field_key, $value);
                } else {
                    $where[] = sprintf("%s = '%s' ", $field_key, $value);
                }
            }
        }
        $where = implode(' AND ', $where);
        if (empty($where)) {
            return false;
        }

        $table = self::getTableName();
        $model = $dbpdo->query("SELECT * FROM {$table} WHERE {$where} LIMIT 1");

        $data = $model->fetch();
        if ($data) {
            $model = new static((array)$data);
            $model->alwaysHook();
            return $model;
        }
        return false;
    }

    public
    static function create($data)
    {
        global $dbpdo;

        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            $arr_values[':' . $key] = $value;
        }

        $table = self::getTableName();

        $keys = implode(',', array_keys($data));
        $values = implode(",", array_keys($arr_values));

        $pre_query = $dbpdo->prepare("INSERT INTO {$table} ({$keys}) VALUES ({$values})");

        $pre_query->execute($arr_values);

        $id = $dbpdo->query("SELECT LAST_INSERT_ID() as id")->fetchAll();
        if ($id[0]['id']) {
            $model = new static(array_merge(['id' => $id[0]['id']], $data));
            $model->createdHook();
            return $model;
        }
        return false;
    }

    public
    static function update($find_data, $data)
    {
        global $dbpdo;

        if (!is_array($data)) {
            return false;
        }

        $set = [];
        foreach ($data as $field_key => $value) {
            $set[] = sprintf("%s = '%s' ", $field_key, $value);
        }

        if (empty($set)) {
            return false;
        }

        $set = implode(',', $set);

        $model = self::find($find_data);
        if ($model) {
            $table = self::getTableName();
            $dbpdo->query("UPDATE {$table} SET {$set} WHERE id = {$model->id}");
            return new static(array_merge((array)$model, $data));
        }
        return false;
    }

    public
    static function firstOrCreate($data)
    {
        $model = self::find($data);
        if (!$model) {
            return self::create($data);
        }
        return $model;
    }

    public
    static function updateOrCreate($find_data, $data)
    {
        $model = self::find($find_data);
        if ($model) {
            return self::update(['id' => $model->id], $data);
        } else {
            return self::create($data);
        }

    }

    public static function all()
    {
        global $dbpdo;

        $table = self::getTableName();

        $return_objects = [];
        $data = $dbpdo->query("SELECT * FROM {$table}");
        if (empty($data)){
            return [];
        }
        $data = $data->fetchAll(\PDO::FETCH_NAMED);
        foreach ($data as $object) {
            $return_objects[] = new static($object);
        }
        return $return_objects;
    }

    public static function whereGet($data)
    {
        global $dbpdo;

        $where = [];
        $or_where = [];
        foreach ($data as $field_key => $value) {
            if (empty($value)) continue;
            if (is_array($value) && count($value) >= 1) {
                foreach ($value as $or_value) {
                    if (strpos($field_key, ':') !== false) {
                        $new_field_key = substr($field_key, 0, -1);
                        $format = "%s '%s'";
                    } else {
                        $format = "%s = '%s'";
                    }
                    $or_where[] = sprintf($format, $new_field_key ?? $field_key, $or_value);
                }
                $where[] = '(' . implode(' OR ', $or_where) . ')';
            } else {
                if (strpos($field_key, ':') !== false) {
                    $new_field_key = substr($field_key, 0, -1);
                    $format = "%s '%s'";
                } else {
                    $format = "%s = '%s'";
                }
                $where[] = sprintf($format, $new_field_key ?? $field_key, $value);
            }
            unset($new_field_key);
        }
        $where = implode(' AND ', $where);
        if (empty($where)) {
            return [];
        }

        $table = self::getTableName();
        $return_objects = [];
        $found_objects = $dbpdo->query("SELECT * FROM {$table} WHERE {$where}");
        if (empty($found_objects)){
            return [];
        }
        $found_objects = $found_objects->fetchAll(\PDO::FETCH_NAMED);
        foreach ($found_objects as $object) {
            $return_objects[] = new static($object);
        }
        return $return_objects;
    }

    public function createdHook()
    {

    }

    public function alwaysHook()
    {

    }

    public function deletedHook()
    {

    }

    public function delete()
    {
        global $dbpdo;
        $table = static::getTableName();
        if ($dbpdo->exec("DELETE FROM {$table} WHERE id = {$this->id}")) {
            $this->deletedHook();
            return true;
        }
        return false;
    }

}