<?php

/**
 * <b>Delete.class:</b>
 * Classe responsável por deletar genéricamente no banco de dados!
 *
 * @copyright (c) 2017, Edinei J.  Bauer
 */

namespace Conn;

use Entity\React;

class Delete extends Conn
{
    private $tabela;
    private $termos;
    private $places;
    private $result;
    private $erro;
    private $react;
    private $resultsUpdates;

    /** @var PDOStatement */
    private $delete;

    /** @var PDO */
    private $conn;

    /**
     * @return mixed
     */
    public function getErro()
    {
        return $this->erro;
    }

    /**
     * @return mixed
     */
    public function getReact()
    {
        return ($this->react ? $this->react->getResponse() : null);
    }

    /**
     * @param $tabela
     * @param $termos
     * @param null $parseString
     */
    public function exeDelete($tabela, $termos, $parseString = null)
    {
        $read = new Read();
        $read->exeRead($tabela, $termos, $parseString);
        if ($read->getResult()) {
            $this->resultsUpdates = $read->getResult()[0];
            $this->setTabela($tabela);
            $this->termos = (string)$termos;

            if (!empty($parseString))
                parse_str($parseString, $this->places);
            else
                $this->places = [];

            $this->execute();
        }
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getRowCount()
    {
        return $this->delete->rowCount();
    }

    public function setPlaces($ParseString)
    {
        parse_str($ParseString, $this->places);
        $this->execute();
    }

    /**
     * ****************************************
     * *********** PRIVATE METHODS ************
     * ****************************************
     */

    private function setTabela($tabela)
    {
        $this->tabela = (defined('PRE') && !preg_match('/^' . PRE . '/', $tabela) && parent::getDatabase() === DATABASE ? PRE . $tabela : $tabela);
    }

    //Obtém o PDO e Prepara a query
    private function Connect()
    {
        $this->conn = parent::getConn();
        $this->delete = $this->conn->prepare($this->delete);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax()
    {
        $this->delete = "DELETE FROM {$this->tabela} {$this->termos}";
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function execute()
    {
        $this->getSyntax();
        $this->Connect();
        try {
            $this->delete->execute($this->places);
            $this->result = true;
            $this->react = new React("delete", str_replace(PRE, '', $this->tabela), $this->resultsUpdates[0] ?? [], $this->resultsUpdates[0] ?? []);
        } catch (\PDOException $e) {
            $this->result = null;
            $this->erro = "<b>Erro ao Deletar:</b> {$e->getMessage()}";
        }

        parent::setDefault();
    }
}
