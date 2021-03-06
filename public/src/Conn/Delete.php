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
    private $react;
    private $resultsUpdates;
    private $isCache;

    /** @var PDOStatement */
    private $delete;

    /** @var PDO */
    private $conn;

    /**
     * @return mixed
     */
    public function getErro()
    {
        return self::getError();
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
        $this->setTabela($tabela);
        $this->isCache = substr( $this->tabela, strlen(PRE), 7) === "wcache_";

        if(!$this->isCache) {
            $read = new Read();
            $read->exeRead($tabela, $termos, $parseString, !0, !0, !0);
            if ($read->getResult()) {
                $this->resultsUpdates = $read->getResult();
            } else {
                $this->result = true;
                return;
            }
        }

        $this->termos = (string)$termos;
        if (!empty($parseString))
            parse_str($parseString, $this->places);
        else
            $this->places = [];

        $this->execute();
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

            if(!$this->isCache) {
                $result = (is_array($this->resultsUpdates) && !empty($this->resultsUpdates[0]) ? $this->resultsUpdates[0] : []);

                /**
                 * Delete caches IDs
                 */
                $idList = "";
                foreach ($this->resultsUpdates as $resultsUpdate)
                    $idList .= (!empty($idList) ? ", " : "") . $resultsUpdate['id'];

                if (!empty($idList)) {
                    $cacheTable =  PRE . "wcache_" . str_replace(PRE, "", $this->tabela);
                    $sql = new SqlCommand(!0);
                    $sql->exeCommand("SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema = '" . DATABASE . "' AND table_name = '{$cacheTable}'");
                    if($sql->getResult() && $sql->getResult()[0]['t'] == 1)
                        $sql->exeCommand("DELETE FROM {$cacheTable} WHERE id IN (" . $idList . ")");
                }

                $this->react = new React("delete", str_replace(PRE, '', $this->tabela), $result, $result);
            }
        } catch (\PDOException $e) {
            $this->result = null;
            self::setError("<b>Erro ao Deletar:</b> {$e->getMessage()}");
        }

        parent::setDefault();
    }
}
