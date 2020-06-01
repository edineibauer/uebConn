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
    private $sql;
    private $select = "*";
    private $places;
    private $result;
    private $tabela;
    private $erro;

    /** @var PDOStatement */
    private $read;

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
     * @param mixed $select
     */
    public function setSelect($select)
    {
        if(is_array($select)) {
            $this->select = "";
            foreach ($select as $item)
                $this->select .= (!empty($this->select) ? ", " : "") . $item;

        } else {
            $this->select = $select;
        }
    }

    /**
     * <b>Exe Read:</b> Executa uma leitura simplificada com Prepared Statments. Basta informar o nome da tabela,
     * os termos da seleção e uma analize em cadeia (ParseString) para executar.
     * @param STRING $tabela = Nome da tabela
     * @param STRING $termos = WHERE | ORDER | LIMIT :limit | OFFSET :offset
     * @param STRING $parseString = link={$link}&link2={$link2}
     * @param bool|null $ignoreSystem
     */
    public function exeRead($tabela, $termos = null, $parseString = null, $ignoreSystem = null)
    {
        $this->setTabela($tabela);
        if (!empty($parseString))
            parse_str($parseString, $this->places);

        $info = Metadados::getInfo(str_replace(PRE, "", $this->tabela));
        $termos = parent::addLogicMajor($termos ?? "", $this->tabela, $info, $ignoreSystem !== null);

        if(!empty($info['password']) && $this->select === "*" && !empty($info['columns_readable']))
            $this->select = implode(", ", $info['columns_readable']) . ($info['user'] === 1 ? ", usuarios_id" : ""). ($info['autor'] === 1 ? ", autorpub" : ""). ($info['autor'] === 2 ? ", ownerpub" : "");

        $this->sql = "SELECT {$this->select} FROM {$this->tabela} {$termos}";
        $this->execute();

        $this->select = "*";
    }

    /**
     * <b>Obter resultado:</b> Retorna um array com todos os resultados obtidos. Envelope primário númérico. Para obter
     * um resultado chame o índice getResult()[0]!
     * @return ARRAY $this = Array ResultSet
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
        return $this->read->rowCount();
    }

    /**
     * <b>Full Read:</b> Executa leitura de dados via query que deve ser montada manualmente para possibilitar
     * seleção de multiplas tabelas em uma única query!
     * @param STRING $Query = Query Select Syntax
     * @param STRING $ParseString = link={$link}&link2={$link2}
     */
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
    private function connect()
    {
        $this->conn = parent::getConn();
        $this->read = $this->conn->prepare($this->sql);
        $this->read->setFetchMode(\PDO::FETCH_ASSOC);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax()
    {
        if ($this->places):
            foreach ($this->places as $Vinculo => $Valor):
                if ($Vinculo == 'limit' || $Vinculo == 'offset')
                    $Valor = (int)$Valor;

                $this->read->bindValue(":{$Vinculo}", $Valor, (is_int($Valor) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
            endforeach;
        endif;
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function execute()
    {
        $this->connect();
        try {
            $this->getSyntax();
            $this->read->execute();
            $this->result = $this->read->fetchAll();

        } catch (\PDOException $e) {
            $this->result = null;
            $this->erro = "<b>Erro ao Ler: ({$this->tabela})</b> {$e->getMessage()}";
        }

        parent::setDefault();
    }
}
