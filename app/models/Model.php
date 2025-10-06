<?php
namespace App\Models;

use App\Utils\DB;

abstract class Model {
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $attributes = [];

    public function __construct($attributes = []) {
        $this->fill($attributes);
    }

    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value) {
        if (in_array($key, $this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    public function save() {
        $db = DB::getInstance()->getConnection();
        
        if (empty($this->attributes[$this->primaryKey])) {
            // Insert
            $columns = implode(', ', array_keys($this->attributes));
            $placeholders = ':' . implode(', :', array_keys($this->attributes));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $db->prepare($sql);
            
            foreach ($this->attributes as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            $stmt->execute();
            $this->attributes[$this->primaryKey] = $db->lastInsertId();
            return true;
        } else {
            // Update
            $updates = [];
            foreach ($this->attributes as $key => $value) {
                if ($key !== $this->primaryKey) {
                    $updates[] = "{$key} = :{$key}";
                }
            }
            
            $updates = implode(', ', $updates);
            $sql = "UPDATE {$this->table} SET {$updates} WHERE {$this->primaryKey} = :id";
            $stmt = $db->prepare($sql);
            
            foreach ($this->attributes as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            return $stmt->execute();
        }
    }

    public static function find($id) {
        $instance = new static();
        $db = DB::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = :id");
        $stmt->execute(['id' => $id]);
        
        if ($row = $stmt->fetch()) {
            return (new static())->fill($row);
        }
        
        return null;
    }

    public static function all() {
        $instance = new static();
        $db = DB::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT * FROM {$instance->table}");
        $results = [];
        
        while ($row = $stmt->fetch()) {
            $results[] = (new static())->fill($row);
        }
        
        return $results;
    }

    public function delete() {
        if (empty($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $db = DB::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $this->attributes[$this->primaryKey]]);
    }

    public static function where($column, $operator, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $instance = new static();
        $db = DB::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM {$instance->table} WHERE {$column} {$operator} :value");
        $stmt->execute(['value' => $value]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = (new static())->fill($row);
        }
        
        return $results;
    }
}
