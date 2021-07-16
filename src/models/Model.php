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
        return Inflect::pluralize(strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', basename(static::class))));
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

        $keys = '';
        $values = '';
        while ($key = array_search(null, $data)) {
            $keys .= $key . ',';
            $values .= 'null,';
            unset($data[$key]);
        }
        $data = array_map(function ($el) {
            return "'{$el}'";
        }, $data);
        $keys .= implode(',', array_keys($data));
        $values .= implode(",", $data);

        $table = self::getTableName();
        $dbpdo->query("INSERT INTO {$table} ({$keys}) VALUES ({$values})");
        $id = $dbpdo->query("SELECT LAST_INSERT_ID() as id")->fetchAll();
        if ($id[0]['id']) {
            $model = new static(array_merge(['id' => $id[0]['id']], $data));
            $model->createdHook();
            return $id[0]['id'];
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
            return array_merge((array)$model, $data);
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
        $data = $dbpdo->query("SELECT * FROM {$table}")->fetchAll(\PDO::FETCH_NAMED);
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
            if (is_array($value) && count($value) > 1) {
                foreach ($value as $or_value) {
                    if (strpos($field_key, ':') !== false) {
                        $new_field_key = substr($field_key, 0, -1);
                        $or_where[] = sprintf("%s '%s'", $new_field_key, $or_value);
                    } else {
                        $or_where[] = sprintf("%s = '%s' ", $field_key, $or_value);
                    }
                }
                $where[] = '(' . implode(' OR ', $or_where) . ')';
            } else {
                if (strpos($field_key, ':') !== false) {
                    $new_field_key = substr($field_key, 0, -1);
                    $where[] = sprintf("%s '%s'", $new_field_key, $value);
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
        $return_objects = [];
        $found_objects = $dbpdo->query("SELECT * FROM {$table} WHERE {$where}")->fetchAll(\PDO::FETCH_NAMED);
        foreach ($found_objects as $object) {
            $return_objects[] = new static($object);
        }
        return $return_objects;
    }

    public
    function createdHook()
    {

    }

    public
    function alwaysHook()
    {

    }

}