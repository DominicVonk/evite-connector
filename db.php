<?php

/*
 *      ######  #     # ######     ######
 *      #     # #     # #     #    #     #   ##   #####   ##   #####    ##    ####  ######
 *      #     # #     # #     #    #     #  #  #    #    #  #  #    #  #  #  #      #
 *      ######  ####### ######     #     # #    #   #   #    # #####  #    #  ####  #####
 *      #       #     # #          #     # ######   #   ###### #    # ######      # #
 *      #       #     # #          #     # #    #   #   #    # #    # #    # #    # #
 *      #       #     # #          ######  #    #   #   #    # #####  #    #  ####  ######
 *
 *
 *      Developed by Dominic Vonk
 *      Date: 29-04-2019
 *      Hypertext PreProcessor Database Class (using MySQL)
 *      Version 2.1.0
 *      Readme:  https://github.com/DominicVonk/PHP-Database
 */
class DatabaseFunc
{
    private $func;
    public function __construct($func)
    {
        $this->func = $func;
    }
    public function getFunction()
    {
        return $this->func;
    }
}
class DatabaseStatement
{
    private $statement;
    private $args;
    public function __construct($statement, $args = array())
    {
        $this->statement = $statement;
        $this->args = $args;
    }
    public function getStatement()
    {
        return $this->statement;
    }
    public function getArgs()
    {
        return $this->args;
    }
}
class DatabaseColumn
{
    private $column;
    public function __construct($column)
    {
        $this->column = $column;
    }
    public function getColumn()
    {
        return $this->column;
    }
}
class Database extends PDO
{
    private $whereValues;
    public static function NOW($date = null)
    {
        $date = $date == null ? time() : $date;
        return date('Y-m-d H:i:s', $date);
    }
    public $returnWithNumbers = false;
    public function Select($table, $cells = null, $where = null, $limit = false, $orderby = false, $asc = true, $prep = '')
    {
        $query = null;
        $args = null;
        if ($cells == null) {
            $args = '*';
        } else {
            if (is_array($cells)) {
                foreach ($cells as $key => $value) {
                    if (is_numeric($key)) {
                        if ($value instanceof DatabaseColumn) {
                            $args .= $value->getColumn() . ',';
                        } else {
                            if ($value == '*') {
                                $args .= '*,';
                            } else {
                                $args .= '`' . $value . '`,';
                            }
                        }
                    } else {
                        $args .= '`' . $key . '` `' . $value . '`,';
                    }

                }
                $args = substr($args, 0, strlen($args) - 1);
            } else {
                $args = $cells;
            }
        }
        $query = 'SELECT' . (!empty($prep) ? ' ' . $prep : '') . ' ' . $args . ' FROM `' . $table . '`';

        if ($where) {
            $query .= ' WHERE';
            $this->whereValues = array();
            $query .= ' (' . $this->WhereRecursive($where) . ')';
        }
        if ($orderby) {
            $_orderby = $orderby;
            if (is_array($orderby)) {
                $items = array();
                foreach ($orderby as $order => $asc) {
                    array_push($items, '`' . $order . '` ' . ($asc ? 'ASC' : 'DESC'));
                }
                $_orderby = implode(',', $items);
            } else {
                if ($orderby instanceof DatabaseFunc) {
                    $_orderby = $orderby->getFunction();
                } else {
                    $_orderby = '`' . $orderby . '`';
                }
            }
            $query .= ' ORDER BY ' . $_orderby . '';
        }
        if (isset($orderby) && is_string($orderby)) {
            if ($asc) {
                $query .= ' ASC';
            } else {
                $query .= ' DESC';
            }
        }
        if ($limit) {
            if ($limit === 1 || $limit === true) {
                $query .= ' LIMIT 1';
            } else {
                $query .= ' LIMIT ' . $limit;
            }
        }
        $query .= ';';
        if (defined('DEBUG_MODE')) {
            var_dump($query);
        }
        $preparedStatement = $this->prepare($query);
        if ($where !== null) {
            foreach ($this->whereValues as $key => $value) {
                $preparedStatement->bindValue($key, $value);
            }

            if (defined('DEBUG_MODE')) {
                var_dump($this->whereValues);
            }
        }
        $preparedStatement->execute();
        if ($limit === true || $limit === 1) {
            $output = array();

            if ($this->returnWithNumbers) {
                while ($row = $preparedStatement->fetch()) {
                    $output = $row;
                }
            } else {
                while ($row = $preparedStatement->fetch(PDO::FETCH_ASSOC)) {
                    $output = $row;
                }
            }
            return $output;
        } else {
            $output = array();
            if ($this->returnWithNumbers) {
                $output = $preparedStatement->fetchAll();
            } else {
                $output = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
            }

            return $output;
        }
    }
    public function SelectColumn($table, $cells, $where = null, $limit = false, $orderby = false, $asc = true)
    {
        if (!is_array($cells)) {
            $cells = [$cells];
        }
        $results = $this->Select($table, $cells, $where, $limit, $orderby, $asc);
        $obj = false;

        $resolvedCells = [];
        foreach ($cells as $cell) {
            if ($cell instanceof DatabaseColumn) {
                $cell = $cell->getColumn();
            }
            $resolvedCells[] = $cell;
        }

        if ($results) {
            if ($limit === true || $limit === 1) {
                if (count($resolvedCells) === 1) {
                    $obj = $results[$resolvedCells[0]];
                } else if (count($resolvedCells) === 2) {
                    $obj = [$results[$resolvedCells[0]] => $results[$resolvedCells[1]]];
                } else {
                    $key = $results[$resolvedCells[0]];
                    unset($results[$resolvedCells[0]]);
                    $obj = [$key => $results];
                }
            } else {
                $obj = [];
                foreach ($results as $result) {
                    if (count($resolvedCells) === 1) {
                        $obj[] = $result[$resolvedCells[0]];
                    } else if (count($resolvedCells) === 2) {
                        $obj[$result[$resolvedCells[0]]] = $result[$resolvedCells[1]];
                    } else {
                        $key = $result[$resolvedCells[0]];
                        unset($result[$resolvedCells[0]]);
                        $obj[$key] = $result;
                    }
                }
            }
        }
        return $obj;
    }
    public function SelectDistinct($table, $cells = null, $where = null, $limit = false, $orderby = false, $asc = true)
    {
        return $this->Select($table, $cells, $where, $limit, $orderby, $asc, 'DISTINCT');
    }
    public function SelectCount($table, $where = null)
    {
        return $this->SelectColumn($table, new DatabaseColumn('COUNT(*)'), $where, 1);
    }
    public function SelectDistinctOne($table, $cells = null, $where = null, $orderby = false, $asc = true)
    {
        return $this->Select($table, $cells, $where, 1, $orderby, $asc, 'DISTINCT');
    }
    public function SelectOne($table, $cells = null, $where = null, $orderby = false, $asc = true)
    {
        return $this->Select($table, $cells, $where, 1, $orderby, $asc);
    }
    public function QueryOutputFetch($query, $values = [], $limit = false)
    {
        $vals = [];
        foreach ($values as $k => $val) {
            if ($val instanceof DatabaseFunc) {
                $query = str_replace($k, $val->getFunction(), $query);
            } else {
                $vals[$k] = $val;
            }
        }

        if (defined('DEBUG_MODE')) {
            var_dump($query);
        }
        $preparedStatement = $this->prepare($query);
        $preparedStatement->execute($vals);
        if ($limit === true || $limit === 1) {
            $output = array();

            if ($this->returnWithNumbers) {
                $row = $preparedStatement->fetch();
                $output = $row;
            } else {
                $row = $preparedStatement->fetch(PDO::FETCH_ASSOC);
                $output = $row;
            }
            return $output;
        } else {
            $output = array();
            if ($this->returnWithNumbers) {
                $output = $preparedStatement->fetchAll();
            } else {
                $output = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
            }

            return $output;
        }
    }
    public function QueryOutputId($query, $values)
    {
        $preparedStatement = $this->prepare($query);
        $preparedStatement->execute($values);
        return $this->lastInsertId();
    }
    private function WhereRecursive($where, $layer = 0)
    {
        $query = '';
        $newlayer = $layer + 1;
        $glue = ($layer % 2 == 0) ? ' && ' : ' || ';

        if ($layer > 0) {
            $query = '(';
        }
        $queryInner = array();
        foreach ($where as $key => $value) {
            $done = false;
            $whereKey = ':where' . count($this->whereValues);
            if ($value instanceof DatabaseStatement) {
                $statement = $value->getStatement();
                foreach ($value->getArgs() as $val) {
                    $statement = preg_replace('/\?/', $whereKey, $statement, 1);
                    $this->whereValues[$whereKey] = $val;
                    $whereKey = ':where' . count($this->whereValues);
                }
                array_push($queryInner, $statement);
            } else if (!is_numeric($key) && is_array($value)) {

                $vals = array();
                foreach ($value as $wk) {
                    if ($wk instanceof DatabaseFunc) {
                        $wk = $wk->getFunction();
                        array_push($vals, $wk);
                    } else {
                        array_push($vals, $whereKey);
                        $this->whereValues[$whereKey] = $wk;
                        $whereKey = ':where' . count($this->whereValues);
                    }
                }
                switch (substr($key, 0, 2)) {
                    case '!~':array_push($queryInner, '`' . substr($key, 2) . '` NOT BETWEEN ' . implode(' AND ', $vals) . ')');
                        $done = true;
                        break;
                }
                if (!$done) {
                    switch (substr($key, 0, 1)) {
                        case '!':array_push($queryInner, '`' . substr($key, 1) . '` NOT IN (' . implode(', ', $vals) . ')');
                            $done = true;
                            break;
                        case '~':array_push($queryInner, '(`' . substr($key, 1) . '` BETWEEN ' . implode(' AND ', $vals) . ')');
                            $done = true;
                            break;
                        case '?':array_push($queryInner, '(`' . substr($key, 1) . '` NOT BETWEEN ' . implode(' AND ', $vals) . ')');
                            $done = true;
                            break;
                    }
                }
                if (!$done) {
                    array_push($queryInner, '`' . $key . '` IN (' . implode(', ', $vals) . ')');
                }
            } else if (!is_numeric($key)) {
                if ($value instanceof DatabaseFunc) {
                    $whereKey = $value->getFunction();
                } else {
                    $this->whereValues[$whereKey] = $value;
                }
                switch (substr($key, 0, 2)) {
                    case '>=':array_push($queryInner, '`' . substr($key, 2) . '` >= ' . $whereKey);
                        $done = true;
                        break;
                    case '<=':array_push($queryInner, '`' . substr($key, 2) . '` <= ' . $whereKey);
                        $done = true;
                        break;
                    case '<>':array_push($queryInner, '`' . substr($key, 2) . '` != ' . $whereKey);
                        $done = true;
                        break;
                    case '%=':array_push($queryInner, '`' . substr($key, 2) . '` LIKE ' . $whereKey);
                        $done = true;
                        break;
                    case '!~':array_push($queryInner, '`' . substr($key, 2) . '` NOT LIKE ' . $whereKey);
                        $done = true;
                        break;
                }
                if (!$done) {
                    switch (substr($key, 0, 1)) {
                        case '>':array_push($queryInner, '`' . substr($key, 1) . '` > ' . $whereKey);
                            $done = true;
                            break;
                        case '<':array_push($queryInner, '`' . substr($key, 1) . '` < ' . $whereKey);
                            $done = true;
                            break;
                        case '^':array_push($queryInner, '`' . substr($key, 1) . '` >= ' . $whereKey);
                            $done = true;
                            break;
                        case '%':array_push($queryInner, '`' . substr($key, 1) . '` <= ' . $whereKey);
                            $done = true;
                            break;
                        case '!':array_push($queryInner, '`' . substr($key, 1) . '` != ' . $whereKey);
                            $done = true;
                            break;
                        case '~':array_push($queryInner, '`' . substr($key, 1) . '` LIKE ' . $whereKey);
                            $done = true;
                            break;
                        case '?':array_push($queryInner, '`' . substr($key, 1) . '` NOT LIKE ' . $whereKey);
                            $done = true;
                            break;
                    }
                }
                if (!$done) {
                    array_push($queryInner, '`' . $key . '` = ' . $whereKey);
                }
            } else {
                array_push($queryInner, $this->WhereRecursive($value, $newlayer));
            }
        }
        $query .= implode($glue, $queryInner);
        if ($layer > 0) {
            $query .= ')';
        }
        return $query;
    }
    public function Delete($table, $where = null)
    {
        $table = explode(', ', $table);
        $table = implode('`,`', $table);

        $query = "DELETE FROM `" . $table . "`";

        if ($where) {
            $this->whereValues = array();
            $query .= ' WHERE (' . $this->WhereRecursive($where) . ')';
        }

        if (defined('DEBUG_MODE')) {
            var_dump($query);
        }
        $preparedStatement = $this->prepare($query);
        if ($where !== null) {
            foreach ($this->whereValues as $key => $value) {
                $preparedStatement->bindValue($key, $value);
            }
        }
        $preparedStatement->execute();
    }
    public function Update($table, $where = null, $input = null)
    {
        $values = array();
        $table = explode(', ', $table);
        $table = implode('`,`', $table);
        $_values = array();
        foreach ($input as $key => $value) {
            $kValue = ':value' . count($values);
            if ($value instanceof DatabaseFunc) {
                array_push($_values, '`' . $key . '` = ' . $value->getFunction());
            } else {
                array_push($_values, '`' . $key . '` = ' . $kValue);
                $values[$kValue] = $value;
            }
        }
        $_values = implode(', ', $_values);
        $query = "UPDATE `" . $table . "` SET " . $_values;

        if ($where) {
            $this->whereValues = array();
            $query .= ' WHERE ' . $this->WhereRecursive($where);
        }

        if (defined('DEBUG_MODE')) {
            var_dump($query);
        }
        $preparedStatement = $this->prepare($query);

        foreach ($values as $key => $value) {
            $preparedStatement->bindValue($key, $value);
        }

        if ($where !== null) {
            foreach ($this->whereValues as $key => $value) {
                $preparedStatement->bindValue($key, $value);
            }
        }

        $preparedStatement->execute();
    }
    public function Insert($table, $insertKeys, $insertValues = null)
    {
        $variables = array();

        $columns = array();
        $values = array();
        $first = true;
        $variable = ':variable' . count($variables);
        if ($insertValues === null) {
            $columns = array_keys($insertKeys);
            $insertValues = array(array_values($insertKeys));
        } else {
            $columns = $insertKeys;
            if (!is_array($insertValues[0])) {
                $insertValues = array($insertValues);
            }
        }

        foreach ($insertValues as $list) {
            $array = array();
            foreach ($list as $value) {
                if ($value instanceof DatabaseFunc) {
                    array_push($array, $value->getFunction());
                } else {
                    $variables[$variable] = $value;
                    array_push($array, $variable);
                    $variable = ':variable' . count($variables);
                }
            }
            array_push($values, $array);
        }

        $columns = '(`' . implode('`, `', $columns) . '`)';

        $_values = array();
        foreach ($values as $value) {
            array_push($_values, '(' . implode(', ', $value) . ')');
        }

        $values = implode(',', $_values);

        $query = 'INSERT INTO `' . $table . '` ' . $columns . ' VALUES ' . $values . ';';

        if (defined('DEBUG_MODE')) {
            var_dump($query);
        }
        $statement = $this->prepare($query);
        $statement->execute($variables);
        return $this->lastInsertId();
    }
}
