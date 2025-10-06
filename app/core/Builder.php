<?php

namespace App\Core;

use PDO;
use PDOStatement;
use PDOException;
use App\Core\Model;

class Builder
{
    /**
     * The model instance
     * 
     * @var Model
     */
    protected $model;
    
    /**
     * The database connection instance
     * 
     * @var PDO
     */
    protected $pdo;
    
    /**
     * The current query value bindings
     * 
     * @var array
     */
    protected $bindings = [];
    
    /**
     * The where constraints for the query
     * 
     * @var array
     */
    protected $wheres = [];
    
    /**
     * The orderings for the query
     * 
     * @var array
     */
    protected $orders = [];
    
    /**
     * The maximum number of records to return
     * 
     * @var int
     */
    protected $limit;
    
    /**
     * The number of records to skip
     * 
     * @var int
     */
    protected $offset;
    
    /**
     * The columns that should be returned
     * 
     * @var array
     */
    protected $columns = ['*'];
    
    /**
     * Create a new query builder instance
     * 
     * @param Model $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->pdo = $model->getConnection();
    }
    
    /**
     * Add a basic where clause to the query
     * 
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we'll assume it's an array of key-value pairs
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }
        
        // If the operator is not a valid operator, we'll assume the developer wants
        // to perform an equals operation and set the operator to "=" and set the
        // value to the given operator value.
        if (func_num_args() === 2) {
            list($value, $operator) = [$operator, '='];
        } elseif ($this->invalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }
        
        // If the value is a Closure, we'll assume the developer wants to perform a
        // sub-select within the query, which is wrapped in parentheses and added
        // to the query as a nested where clause.
        if ($value instanceof \Closure) {
            return $this->whereNested($value, $boolean);
        }
        
        // If the value is "null", we'll just set the value to null and use the
        // correct operator for the comparison.
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }
        
        // If the column is actually a Closure instance, we'll assume the developer
        // wants to begin a nested where statement which is wrapped in parentheses.
        if ($column instanceof \Closure) {
            return $this->whereNested($column, $boolean);
        }
        
        // Now that we have the column, operator, and value, we can add the where
        // clause to the query. The "where" method will handle the actual binding
        // of the values to the query.
        $type = 'Basic';
        
        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );
        
        $this->addBinding($value, 'where');
        
        return $this;
    }
    
    /**
     * Add an "or where" clause to the query
     * 
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }
    
    /**
     * Add a "where in" clause to the query
     * 
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';
        
        // If the value is a query builder instance, we'll assume the developer wants
        // to perform a sub-select within the query, which is wrapped in parentheses
        // and added to the query as a nested where clause.
        if ($values instanceof static) {
            return $this->whereInExistingQuery(
                $column, $values, $boolean, $not
            );
        }
        
        // If the value is a Closure, we'll assume the developer wants to perform
        // a sub-select within the query, which is wrapped in parentheses and added
        // to the query as a nested where clause.
        if ($values instanceof \Closure) {
            return $this->whereInSub($column, $values, $boolean, $not);
        }
        
        // If the value is an array, we'll add each value as a binding to the query
        // and add a placeholder for each value in the where clause.
        if (!is_array($values)) {
            $values = [$values];
        }
        
        $this->wheres[] = compact('type', 'column', 'values', 'boolean');
        
        foreach ($values as $value) {
            $this->addBinding($value, 'where');
        }
        
        return $this;
    }
    
    /**
     * Add a "where not in" clause to the query
     * 
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }
    
    /**
     * Add a "where null" clause to the query
     * 
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';
        
        $this->wheres[] = compact('type', 'column', 'boolean');
        
        return $this;
    }
    
    /**
     * Add a "where not null" clause to the query
     * 
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->whereNull($column, $boolean, true);
    }
    
    /**
     * Add a "where between" clause to the query
     * 
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';
        
        $this->wheres[] = compact('type', 'column', 'values', 'boolean', 'not');
        
        $this->addBinding($values, 'where');
        
        return $this;
    }
    
    /**
     * Add an "order by" clause to the query
     * 
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        
        $this->orders[] = compact('column', 'direction');
        
        return $this;
    }
    
    /**
     * Set the "limit" value of the query
     * 
     * @param int $value
     * @return $this
     */
    public function limit($value)
    {
        $this->limit = $value;
        
        return $this;
    }
    
    /**
     * Set the "offset" value of the query
     * 
     * @param int $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = $value;
        
        return $this;
    }
    
    /**
     * Set the columns to be selected
     * 
     * @param array|mixed $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        
        return $this;
    }
    
    /**
     * Execute the query as a "select" statement
     * 
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        if (!empty($columns)) {
            $this->select($columns);
        }
        
        $sql = $this->toSql();
        
        $bindings = $this->getBindings();
        
        $statement = $this->pdo->prepare($sql);
        
        $this->bindValues($statement, $this->prepareBindings($bindings));
        
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_CLASS, get_class($this->model));
    }
    
    /**
     * Execute the query and get the first result
     * 
     * @param array $columns
     * @return Model|static|null
     */
    public function first($columns = ['*'])
    {
        $results = $this->limit(1)->get($columns);
        
        return count($results) > 0 ? $results[0] : null;
    }
    
    /**
     * Find a model by its primary key
     * 
     * @param mixed $id
     * @param array $columns
     * @return Model|static|null
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where($this->model->getKeyName(), '=', $id)->first($columns);
    }
    
    /**
     * Insert a new record into the database
     * 
     * @param array $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }
        
        // If the values are not an array of arrays, we'll assume the developer
        // wants to insert a single record with the given values.
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        
        $columns = array_keys(reset($values));
        
        // We need to build a list of parameter place-holders of values that are
        // bound to the query. Each insert should have the same number of bindings
        // so we will build the list based on the first record in the array.
        $parameters = [];
        
        foreach ($values as $record) {
            $parameters[] = '(' . implode(', ', array_fill(0, count($record), '?')) . ')';
            
            foreach ($record as $value) {
                $this->addBinding($value, 'insert');
            }
        }
        
        $columns = $this->quoteColumns($columns);
        
        $sql = 'insert into ' . $this->quoteTable($this->model->getTable()) . ' (' . implode(', ', $columns) . ') values ' . implode(', ', $parameters);
        
        return $this->pdo->prepare($sql)->execute($this->getBindings());
    }
    
    /**
     * Update a record in the database
     * 
     * @param array $values
     * @return int
     */
    public function update(array $values)
    {
        $bindings = array_values(array_merge($values, $this->getBindings()));
        
        $sql = $this->compileUpdate($values);
        
        return $this->pdo->prepare($sql)->execute($bindings);
    }
    
    /**
     * Delete a record from the database
     * 
     * @param mixed $id
     * @return int
     */
    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->where($this->model->getKeyName(), '=', $id);
        }
        
        $sql = $this->compileDelete();
        
        return $this->pdo->prepare($sql)->execute($this->getBindings());
    }
    
    /**
     * Get the SQL representation of the query
     * 
     * @return string
     */
    public function toSql()
    {
        return $this->compileSelect();
    }
    
    /**
     * Get the current query value bindings
     * 
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
    
    /**
     * Add a binding to the query
     * 
     * @param mixed $value
     * @param string $type
     * @return $this
     */
    public function addBinding($value, $type = 'where')
    {
        if (is_array($value)) {
            $this->bindings[$type] = array_merge($this->bindings[$type] ?? [], $value);
        } else {
            $this->bindings[$type][] = $value;
        }
        
        return $this;
    }
    
    /**
     * Compile a select query into SQL
     * 
     * @return string
     */
    protected function compileSelect()
    {
        $sql = 'select ' . $this->compileColumns() . ' from ' . $this->quoteTable($this->model->getTable());
        
        if (!empty($this->wheres)) {
            $sql .= ' where ' . $this->compileWheres();
        }
        
        if (!empty($this->orders)) {
            $sql .= ' ' . $this->compileOrders();
        }
        
        if (isset($this->limit)) {
            $sql .= ' limit ' . (int) $this->limit;
        }
        
        if (isset($this->offset)) {
            $sql .= ' offset ' . (int) $this->offset;
        }
        
        return $sql;
    }
    
    /**
     * Compile an update statement into SQL
     * 
     * @param array $values
     * @return string
     */
    protected function compileUpdate(array $values)
    {
        $columns = [];
        
        foreach (array_keys($values) as $column) {
            $columns[] = $this->quoteColumn($column) . ' = ?';
        }
        
        $sql = 'update ' . $this->quoteTable($this->model->getTable()) . ' set ' . implode(', ', $columns);
        
        if (!empty($this->wheres)) {
            $sql .= ' where ' . $this->compileWheres();
        }
        
        return $sql;
    }
    
    /**
     * Compile a delete statement into SQL
     * 
     * @return string
     */
    protected function compileDelete()
    {
        $sql = 'delete from ' . $this->quoteTable($this->model->getTable());
        
        if (!empty($this->wheres)) {
            $sql .= ' where ' . $this->compileWheres();
        }
        
        return $sql;
    }
    
    /**
     * Compile the "select" portion of the query
     * 
     * @return string
     */
    protected function compileColumns()
    {
        if ($this->columns === ['*']) {
            return '*';
        }
        
        return implode(', ', array_map([$this, 'quoteColumn'], $this->columns));
    }
    
    /**
     * Compile the "where" portions of the query
     * 
     * @return string
     */
    protected function compileWheres()
    {
        $sql = [];
        
        foreach ($this->wheres as $where) {
            $method = 'compileWhere' . ucfirst($where['type']);
            
            if (method_exists($this, $method)) {
                $sql[] = $where['boolean'] . ' ' . $this->$method($where);
            }
        }
        
        if (count($sql) > 0) {
            $sql = implode(' ', $sql);
            
            // Remove the leading boolean from the first where clause
            return preg_replace('/^and |^or /i', '', $sql, 1);
        }
        
        return '';
    }
    
    /**
     * Compile a basic where clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereBasic($where)
    {
        return $this->quoteColumn($where['column']) . ' ' . $where['operator'] . ' ?';
    }
    
    /**
     * Compile a "where in" clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereIn($where)
    {
        if (empty($where['values'])) {
            return '0 = 1';
        }
        
        $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
        
        return $this->quoteColumn($where['column']) . ' in (' . $placeholders . ')';
    }
    
    /**
     * Compile a "where not in" clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereNotIn($where)
    {
        if (empty($where['values'])) {
            return '1 = 1';
        }
        
        $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
        
        return $this->quoteColumn($where['column']) . ' not in (' . $placeholders . ')';
    }
    
    /**
     * Compile a "where null" clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereNull($where)
    {
        return $this->quoteColumn($where['column']) . ' is null';
    }
    
    /**
     * Compile a "where not null" clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereNotNull($where)
    {
        return $this->quoteColumn($where['column']) . ' is not null';
    }
    
    /**
     * Compile a "where between" clause
     * 
     * @param array $where
     * @return string
     */
    protected function compileWhereBetween($where)
    {
        $between = $where['not'] ? 'not between' : 'between';
        
        return $this->quoteColumn($where['column']) . ' ' . $between . ' ? and ?';
    }
    
    /**
     * Compile the "order by" portions of the query
     * 
     * @return string
     */
    protected function compileOrders()
    {
        $orders = [];
        
        foreach ($this->orders as $order) {
            $orders[] = $this->quoteColumn($order['column']) . ' ' . $order['direction'];
        }
        
        return 'order by ' . implode(', ', $orders);
    }
    
    /**
     * Quote a table name
     * 
     * @param string $table
     * @return string
     */
    protected function quoteTable($table)
    {
        return '`' . str_replace('`', '``', $table) . '`';
    }
    
    /**
     * Quote a column name
     * 
     * @param string $column
     * @return string
     */
    protected function quoteColumn($column)
    {
        if (strpos($column, '.') !== false) {
            return $this->quoteTable($column);
        }
        
        return '`' . str_replace('`', '``', $column) . '`';
    }
    
    /**
     * Quote an array of column names
     * 
     * @param array $columns
     * @return array
     */
    protected function quoteColumns(array $columns)
    {
        return array_map([$this, 'quoteColumn'], $columns);
    }
    
    /**
     * Prepare the query bindings for execution
     * 
     * @param array $bindings
     * @return array
     */
    protected function prepareBindings(array $bindings)
    {
        $results = [];
        
        foreach ($bindings as $binding) {
            if (is_array($binding)) {
                $results = array_merge($results, $binding);
            } else {
                $results[] = $binding;
            }
        }
        
        return $results;
    }
    
    /**
     * Bind values to their parameters in the given statement
     * 
     * @param PDOStatement $statement
     * @param array $bindings
     * @return void
     */
    protected function bindValues(PDOStatement $statement, array $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }
    
    /**
     * Determine if the given operator is invalid
     * 
     * @param string $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return !in_array(strtolower($operator), [
            '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
            'like', 'like binary', 'not like', 'ilike',
            '&', '|', '^', '<<', '>>',
            'rlike', 'regexp', 'not regexp',
            '~', '~*', '!~', '!~*', 'similar to',
            'not similar to', 'not ilike', '~~*', '!~~*',
        ], true);
    }
    
    /**
     * Add an array of where clauses to the query
     * 
     * @param array $column
     * @param string $boolean
     * @param string $method
     * @return $this
     */
    protected function addArrayOfWheres($column, $boolean, $method = 'where')
    {
        return $this->whereNested(function ($query) use ($column, $method, $boolean) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    $query->$method(...array_values($value));
                } else {
                    $query->$method($key, '=', $value, $boolean);
                }
            }
        }, $boolean);
    }
    
    /**
     * Add a nested where statement to the query
     * 
     * @param \Closure $callback
     * @param string $boolean
     * @return $this
     */
    protected function whereNested(\Closure $callback, $boolean = 'and')
    {
        $query = $this->newQuery();
        
        call_user_func($callback, $query);
        
        return $this->addNestedWhereQuery($query, $boolean);
    }
    
    /**
     * Add another query builder as a nested where to the query builder
     * 
     * @param Builder $query
     * @param string $boolean
     * @return $this
     */
    protected function addNestedWhereQuery($query, $boolean = 'and')
    {
        if (count($query->wheres)) {
            $type = 'Nested';
            
            $this->wheres[] = compact('type', 'query', 'boolean');
            
            $this->addBinding($query->getBindings(), 'where');
        }
        
        return $this;
    }
    
    /**
     * Create a new query instance for nested where condition
     * 
     * @return static
     */
    protected function newQuery()
    {
        return new static($this->model);
    }
    
    /**
     * Add a "where in" clause to the query
     * 
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    protected function whereInExistingQuery($column, $query, $boolean, $not)
    {
        $type = $not ? 'NotInSub' : 'InSub';
        
        $this->wheres[] = compact('type', 'column', 'query', 'boolean');
        
        $this->addBinding($query->getBindings(), 'where');
        
        return $this;
    }
    
    /**
     * Add a "where in" clause to the query with a sub-select
     * 
     * @param string $column
     * @param \Closure $callback
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    protected function whereInSub($column, $callback, $boolean, $not)
    {
        $query = $this->newQuery();
        
        call_user_func($callback, $query);
        
        return $this->whereInExistingQuery($column, $query, $boolean, $not);
    }
}
