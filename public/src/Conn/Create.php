<?php

/**
 * <b>Create:</b>
 * Classe responsável por cadastros genéricos no banco de dados!
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

use Entity\React;

class Create extends Conn
{
    //teste
    private $tabela;
    private $dados;
    private $dadosName;
    private $result;
    private $erro;
    private $react;

    /** @var PDOStatement */
    private $create;

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
     * <b>ExeCreate:</b> Executa um cadastro simplificado no banco de dados utilizando prepared statements.
     * Basta informar o nome da tabela e um array atribuitivo com nome da coluna e valor!
     *
     * @param STRING $tabela = Informe o nome da tabela no banco!
     * @param ARRAY $dados = Informe um array atribuitivo. ( Nome Da Coluna => Valor ).
     */
    public function exeCreate($tabela, array $dados)
    {
        $this->setTabela($tabela);
        $this->dados = $dados;
        $this->dados['system_id'] = (!empty($_SESSION['userlogin']['system_id']) ? $_SESSION['userlogin']['system_id'] : null);

        $this->execute();
    }

    /**
     * <b>Obter resultado:</b> Retorna o ID do registro inserido ou FALSE caso nem um registro seja inserido!
     * @return INT $Variavel = lastInsertId OR FALSE
     */
    public function getResult()
    {
        return $this->result;
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
        $this->create = $this->conn->prepare($this->create);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax()
    {
        $Fileds = "`" . implode("`, `", array_keys($this->dados)) . "`";
        $this->dadosName = [];
        foreach ($this->dados as $key => $dado)
            $this->dadosName[str_replace('-', '_', \Helpers\Check::name($key))] = $dado;

        $this->create = "INSERT INTO {$this->tabela} ({$Fileds}) VALUES (:" . implode(', :', array_keys($this->dadosName)) . ")";
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function execute()
    {
        $this->getSyntax();
        $this->connect();
        try {
            $this->create->execute($this->dadosName);
            $this->result = $this->conn->lastInsertId();

            $read = new Read();
            $read->exeRead($this->tabela, "WHERE id = :id", "id={$this->result}");
            if($read->getResult())
                $this->react = new React("create", str_replace(PRE, '', $this->tabela), $read->getResult()[0]);
        } catch (\PDOException $e) {
            $this->result = null;
            $this->erro = "<b>Erro ao cadastrar: ({$this->tabela})</b> {$e->getMessage()}";
        }

        parent::setDefault();
    }
}
