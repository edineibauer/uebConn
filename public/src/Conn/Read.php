<?php

/**
 * <b>Read:</b>
 * Classe responsável por leituras genéricas no banco de dados!
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

use Entity\Metadados;

class Read extends Conn
{
    private $select;
    private $result;
    private $rowCount;
    private $error;

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * <b>Contar Registros: </b> Retorna o número de registros encontrados pelo select!
     * @return INT $Var = Quantidade de registros encontrados
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    /**
     * @return mixed
     */
    public function getErro()
    {
        return $this->error;
    }

    public function setSelect($select)
    {
        if(is_array($select)) {
            $this->select = "";
            foreach ($select as $item)
                $this->select .= (!empty($this->select) ? ", " : "") . $item;

        } elseif(is_string($select)) {
            $this->select = str_replace("SELECT ", "", $select);
        }
    }

    /**
     * @param $table
     * @param $termos
     * @param $places
     * @return void
     */
    public function exeRead($table, $termos = null, $places = [])
    {
        if (!empty($places) && is_string($places))
            parse_str($places, $places);

        if(!is_array($places))
            $places = [];

        $sql = "SELECT " . (empty($this->select) || $this->select === "*" ? "*" : $this->select) . " FROM {$table} {$termos}";
        $this->select = "*";

        list($this->result, $react, $this->rowCount, $this->error) = self::exeSql("read", $table, $sql, $places);
    }
}
