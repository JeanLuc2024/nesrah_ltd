<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Base Model
 * 
 * All models should extend this class to inherit database operations
 */
class Model {
    /**
     * The database table associated with the model
     * 
     * @var string
     */
    protected $table;
    
    /**
     * The primary key for the model
     * 
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * The attributes that are mass assignable
     * 
     * @var array
     */
    protected $fillable = [];
    
    /**
     * The attributes that should be hidden for arrays
     * 
     * @var array
     */
    protected $hidden = [];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [];
    
    /**
     * The database connection instance
     * 
     * @var PDO
     */
    protected $pdo;
    
    /**
     * The model's attributes
     * 
     * @var array
     */
    protected $attributes = [];
    
    /**
     * The model attribute's original state
     * 
     * @var array
     */
    protected $original = [];
    
    /**
     * Indicates if the model exists
     * 
     * @var bool
     */
    public $exists = false;
    
    /**
     * Indicates if the model was inserted during the current request lifecycle
     * 
     * @var bool
     */
    public $wasRecentlyCreated = false;
    
    /**
     * The name of the "created at" column
     * 
     * @var string
     */
    const CREATED_AT = 'created_at';
    
    /**
     * The name of the "updated at" column
     * 
     * @var string
     */
    const UPDATED_AT = 'updated_at';
    
    /**
     * Create a new model instance
     * 
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = []) {
        global $pdo;
        $this->pdo = $pdo;
        
        // Set table name based on class name if not set
        if (empty($this->table)) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
        }
        
        // Set attributes
        $this->fill($attributes);
    }
    
    /**
     * Get all records from the database
     * 
     * @param array|string $columns
     * @return array
     */
    public static function all($columns = ['*']) {
        $instance = new static();
        $columns = is_array($columns) ? implode(', ', $columns) : $columns;
        $stmt = $instance->pdo->query("SELECT $columns FROM {$instance->table} ORDER BY {$instance->primaryKey} DESC");
        return $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
    }
    
    /**
     * Find a record by its primary key
     * 
     * @param mixed $id
     * @param array|string $columns
     * @return mixed
     */
    public static function find($id, $columns = ['*']) {
        $instance = new static();
        $columns = is_array($columns) ? implode(', ', $columns) : $columns;
        $stmt = $this->pdo->prepare("SELECT $columns FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create(array $data) {
        // Filter data to only include fillable fields
        $data = $this->filterFillable($data);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            $this->pdo->beginTransaction();
            $stmt->execute($data);
            $id = $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $this->find($id);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function update($id, array $data) {
        // Filter data to only include fillable fields
        $data = $this->filterFillable($data);
        
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "$key = :$key";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;
        
        try {
            $this->pdo->beginTransaction();
            $stmt->execute($data);
            $this->pdo->commit();
            return $this->find($id);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function where($column, $operator, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE $column $operator ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }
    
    public function paginate($perPage = 15, $page = null, $columns = ['*']) {
        $page = $page ?? ($_GET['page'] ?? 1);
        $offset = ($page - 1) * $perPage;
        
        $columns = is_array($columns) ? implode(', ', $columns) : $columns;
        
        // Get total count
        $total = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
        
        // Get paginated data
        $stmt = $this->pdo->prepare("SELECT $columns FROM {$this->table} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int) $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll();
        
        return [
            'data' => $items,
            'total' => (int) $total,
            'per_page' => (int) $perPage,
            'current_page' => (int) $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    protected function filterFillable(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    public function getTable() {
        return $this->table;
    }
    
    public function getPrimaryKey() {
        return $this->primaryKey;
    }
}
