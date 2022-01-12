<?php

/**
 * <b>Delete.class:</b>
 * Classe responsável por deletar genéricamente no banco de dados!
 *
 * @copyright (c) 2017, Edinei J.  Bauer
 */

namespace Conn;

class Delete extends Conn
{
    private $result;
    private $rowCount;
    private $react;
    private $error;

    public function getResult()
    {
        return $this->result;
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getErro()
    {
        return $this->error;
    }

    public function getReact()
    {
        return ["data" => $this->react, "response" => (!empty($this->error) ? 2 : 1) , "error" => $this->error];
    }

    /**
     * @param $tabela
     * @param $termos
     * @param null $parseString
     */
    public function exeDelete($tabela, $termos, $places = [])
    {
        if(!empty($places) && is_string($places))
            parse_str($places, $places);

        $sql = "DELETE FROM " . (parent::getDatabase() === DATABASE ? PRE : "") . $tabela . " {$termos}";

        list($this->result, $this->react, $this->rowCount, $this->error) = parent::exeSql("delete", $tabela, $sql, $places);
    }
}
