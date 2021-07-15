<?php


namespace YourResult;


class Model
{
    public $table;

    public function __construct(\PDO $db = null)
    {
        if ($db) {
            $this->db = $db;
        }
        $this->table = lcfirst(basename(static::class));
    }

    public static function find($id)
    {
        $where = [];
        if (!is_array($id)) {
            $id = intval($id);
            $where[] = 'id = ' . $id;
        } else {
            foreach ($id as $field_key => $value) {
                $where[] = $field_key . ' = ' . $value;
            }
        }
        $where = implode(' AND ', $where);
        if (empty($where)){
            return false;
        }


        global $dbpdo;
        $table = self::$table;
        return $dbpdo->query("SELECT * FROM {$table} WHERE {$where} LIMIT 1")->fetchObject() ?? false;
    }

    public static function create($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $keys = implode(',', array_keys($data));
        $values = implode(',', $data);

        global $dbpdo;
        $table = self::$table;
        $dbpdo->query("INSERT INTO {$table} ({$keys}) VALUES ({$data})");
        $id = $dbpdo->query("SELECT LAST_INSERT_ID() as id")->fetchAll();
        return $id[0]['id'] ?? false;
    }

    public static function update($find_data, $data)
    {
        if (!is_array($find_data) || !is_array($data)) {
            return false;
        }

        $set = [];
        foreach ($data as $field_key => $value) {
            $set[] = $field_key . ' = ' . $value;
        }

        if ( empty($set)) {
            return false;
        }

        $set = implode(',', $set);
        global $dbpdo;
        $worklog = self::find($find_data);
            if ($worklog) {
                $table = self::$table;
            $dbpdo->query("UPDATE {$table} SET {$set} WHERE id = {$worklog->id}");
            return array_merge((array)$worklog, $data);
        }

        return false;
    }

    public function updateOrCreate($find_data, $data){
        $worklog = Worklog::find($find_data);
        if ($worklog){
            return Worklog::update(['id' => $worklog->id], $data);
        } else {
            return Worklog::create($data);
        }

    }

}