<?php

/**
 * Created by PhpStorm.
 * User: Edinei
 * Date: 23/01/2017
 * Time: 11:13
 *
 * ForeignKey
 *
 * Busca chaves estrangeiras de campos de uma tabela
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

class ForeignKey extends ForeignKeyMiddle
{

    private $db = DATABASE ?? null;
    private $table;
    private $column;
    private $oneToOne;
    private $oneToMany;
    private $manyToOne;
    private $manyToMany;

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
        $this->getForeignKey();
    }

    /**
     * @param mixed $column
     */
    public function setColumn($column)
    {
        $this->column = $column;
    }

    /**
     * @return mixed
     */
    public function getOneToOne()
    {
        return $this->oneToOne;
    }

    /**
     * @return mixed
     */
    public function getOneToMany()
    {
        return $this->oneToMany;
    }

    /**
     * @return mixed
     */
    public function getManyToOne()
    {
        return $this->manyToOne;
    }

    /**
     * @param null $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * @return mixed
     */
    public function getManyToMany()
    {
        return $this->manyToMany;
    }

    private function getForeignKey()
    {
        if ($this->column && $this->table):
            $this->findOneToOneColumn();

        elseif ($this->table):
            $this->findOneToOne();
            $this->findOneToMany();
            $this->findManyToMany();
        endif;
    }

    private function findOneToOneColumn()
    {
        $readI = new InfoTable();
        $readI->ExeRead("KEY_COLUMN_USAGE", "WHERE TABLE_SCHEMA = :nb && REFERENCED_TABLE_SCHEMA = :nb && TABLE_NAME =:tn && COLUMN_NAME = :tb", "nb={$this->db}&tn={$this->table}&tb={$this->column}");
        if ($readI->getResult()):
            foreach ($readI->getResult() as $g):
                $this->oneToOne[][$g['COLUMN_NAME']] = $g['REFERENCED_TABLE_NAME'];
                parent::setColumnTable($this->table, $g['COLUMN_NAME'], $g['REFERENCED_TABLE_NAME']);
            endforeach;
        endif;
    }

    private function getPk($table)
    {

        $db = DATABASE;
        $readI = new InfoTable();
        $readI->ExeRead("COLUMNS", "WHERE TABLE_SCHEMA = :nb && TABLE_NAME = :nt", "nb={$db}&nt={$table}");
        if ($readI->getResult()):
            foreach ($readI->getResult() as $g):
                if ($g['COLUMN_KEY'] === "PRI"):
                    return true;
                endif;
            endforeach;
        endif;

        return false;
    }

    private function findOneToOne()
    {
        $readI = new InfoTable();
        $readI->ExeRead("KEY_COLUMN_USAGE", "WHERE TABLE_SCHEMA = :nb && REFERENCED_TABLE_SCHEMA = :nb && REFERENCED_TABLE_NAME != '" . PRE . "user' && TABLE_NAME = :tb", "nb={$this->db}&tb={$this->table}");
        if ($readI->getResult()):
            foreach ($readI->getResult() as $g):
                $readI->ExeRead("REFERENTIAL_CONSTRAINTS", "WHERE CONSTRAINT_SCHEMA = :nb && CONSTRAINT_NAME = :cn", "nb={$this->db}&cn={$g['CONSTRAINT_NAME']}");
                if ($readI->getResult() && $readI->getResult()[0]['DELETE_RULE'] === "RESTRICT"):
                    if ($this->acceptThisFluxo($g['TABLE_NAME'], $g['COLUMN_NAME'])):
                        $this->oneToOne[][$g['COLUMN_NAME']] = $g['REFERENCED_TABLE_NAME'];
                        parent::setColumnTable($this->table, $g['COLUMN_NAME'], $g['REFERENCED_TABLE_NAME']);
                    endif;
                else:

                    $this->findManyToOne($readI->getResult(), $g);

                endif;
            endforeach;
        endif;
    }

    private function findManyToOne($result, $g)
    {
        if ($result && $result[0]['DELETE_RULE'] === "CASCADE" && $this->acceptThisFluxo($g['TABLE_NAME'], $g['COLUMN_NAME'])):
            $this->manyToOne[][$g['COLUMN_NAME']] = $g['REFERENCED_TABLE_NAME'];
            parent::setColumnTable($this->table, $g['COLUMN_NAME'], $g['REFERENCED_TABLE_NAME']);
        endif;
    }

    private function findOneToMany()
    {
        $readI = new InfoTable();
        $readI->ExeRead("KEY_COLUMN_USAGE", "WHERE TABLE_SCHEMA = :nb && REFERENCED_TABLE_SCHEMA = :nb && TABLE_NAME != '" . PRE . "user' && REFERENCED_TABLE_NAME = :tb", "nb={$this->db}&tb={$this->table}");
        if ($readI->getResult()):
            foreach ($readI->getResult() as $g):
                if ($this->getPk($g['TABLE_NAME']) && $this->acceptThisFluxo($g['TABLE_NAME'], $g['COLUMN_NAME'])):
                    $readI->ExeRead("REFERENTIAL_CONSTRAINTS", "WHERE CONSTRAINT_SCHEMA = :nb && CONSTRAINT_NAME = :cn", "nb={$this->db}&cn={$g['CONSTRAINT_NAME']}");
                    if ($readI->getResult() && $readI->getResult()[0]['DELETE_RULE'] === "CASCADE"):
                        $this->oneToMany[][$g['REFERENCED_COLUMN_NAME']] = $g['TABLE_NAME'];
                        parent::setColumnTable($this->table, $g['COLUMN_NAME'], $g['TABLE_NAME']);
                    endif;
                endif;
            endforeach;
        endif;
    }

    private function findManyToMany()
    {
        $readI = new InfoTable();
        $readI->ExeRead("KEY_COLUMN_USAGE", "WHERE TABLE_SCHEMA = :nb && REFERENCED_TABLE_SCHEMA = :nb && REFERENCED_TABLE_NAME = :tb && TABLE_NAME != :tb", "nb={$this->db}&tb={$this->table}");
        if ($readI->getResult()):
            foreach ($readI->getResult() as $g):
                $this->getRelationTableManyToMany($g['TABLE_NAME'], $g['COLUMN_NAME']);
            endforeach;
        endif;
    }

    private function getRelationTableManyToMany($table, $column)
    {
        if (!$this->getPk($table) && $this->acceptThisFluxo($table, $column)):
            $readI = new InfoTable();
            $readI->ExeRead("KEY_COLUMN_USAGE", "WHERE TABLE_SCHEMA = :nb && REFERENCED_TABLE_SCHEMA = :nb && TABLE_NAME = :tn", "nb={$this->db}&tn={$table}");
            if ($readI->getResult() && $readI->getRowCount() > 1):
                foreach ($readI->getResult() as $g):
                    if ($g['REFERENCED_TABLE_NAME'] !== $this->table && $g['REFERENCED_TABLE_NAME'] !== $table):

                        $this->manyToMany[] = $g['REFERENCED_TABLE_NAME'];
                        parent::setTableRelation($this->table, $g['REFERENCED_TABLE_NAME'], $table);
                        parent::setColumnTable($table, $g['COLUMN_NAME'], $g['REFERENCED_TABLE_NAME']);
                        parent::setColumnTable($this->table, $column, $table);
                    endif;
                endforeach;
            endif;
        endif;
    }

    private function acceptThisFluxo($table, $column)
    {
        $db = DATABASE;
        $readI = new InfoTable();
        $readI->ExeRead("COLUMNS", "WHERE TABLE_SCHEMA = :nb && TABLE_NAME = :nt", "nb={$db}&nt={$table}");
        if ($readI->getResult()):
            foreach ($readI->getResult() as $g):
                if ($g['COLUMN_NAME'] === $column && preg_match('/<<nofk>>/i', $g['COLUMN_COMMENT'])):
                    return false;
                endif;
            endforeach;
        endif;

        return true;
    }

}