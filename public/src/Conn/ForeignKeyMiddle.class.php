<?php

/**
 * Created by PhpStorm.
 * User: Edinei
 * Date: 23/01/2017
 * Time: 11:13
 */

namespace Conn;

abstract class ForeignKeyMiddle
{

    private $origin;
    private $target;
    private $id;
    private $relation;
    private $relationColumn;

    /**
     * @param mixed $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * @param mixed $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $table
     */
    protected function setColumnTable($table, $column, $table2)
    {
        $this->relationColumn[$table][$table2] = $column;
        $this->relationColumn[$table2][$table] = $column;
    }

    protected function setTableRelation($table1, $table2, $table3)
    {
        $this->relation[$table1][$table2] = $table3;
        $this->relation[$table2][$table1] = $table3;
    }

    public function getRelationTable($table, $table2)
    {
        return $this->relation[$table][$table2];
    }

    public function getResult()
    {
        if ($this->id && $this->origin && $this->target):
            return $this->getForeignKeyValues();
        endif;
        return null;
    }

    /**
     * @return mixed
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @return mixed
     */
    public function getRelationColumn()
    {
        return $this->relationColumn;
    }

    public function getColumnTable($table, $table2)
    {
        return $this->relationColumn[$table][$table2];
    }

    private function getForeignKeyValues()
    {
        $ids = array();

        if (isset($this->relation[$this->origin][$this->target])):
            $banco = $this->relation[$this->origin][$this->target];

            $read = new Read();
            $read->ExeRead($banco, "WHERE {$this->getColumnTable($banco, $this->origin)} = :id", "id={$this->id}");
            if ($read->getResult()):
                foreach ($read->getResult() as $result):
                    $ids[] = $result[$this->getColumnTable($banco, $this->target)];
                endforeach;
            endif;
        endif;

        return $ids;
    }
}