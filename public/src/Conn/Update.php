<?php

/**
 * <b>Update:</b>
 * Classe responsável por atualizações genéricas no banco de dados!
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

use Entity\Dicionario;
use Entity\React;

class Update extends Conn
{
    private $tabela;
    private $dados;
    private $dadosName;
    private $termos;
    private $places;
    private $result;
    private $erro;
    private $react;
    private $resultsUpdates;

    /** @var PDOStatement */
    private $update;

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
     * <b>Exe Update:</b> Executa uma atualização simplificada com Prepared Statments. Basta informar o
     * nome da tabela, os dados a serem atualizados em um Attay Atribuitivo, as condições e uma
     * analize em cadeia (ParseString) para executar.
     * @param STRING $tabela = Nome da tabela
     * @param ARRAY $dados = [ NomeDaColuna ] => Valor ( Atribuição )
     * @param STRING $termos = WHERE coluna = :link AND.. OR..
     * @param STRING $parseString = link={$link}&link2={$link2}
     */
    public function exeUpdate(string $tabela, array $dados, string $termos, $parseString = null)
    {
        $read = new Read();
        $read->exeRead($tabela, $termos, $parseString);
        if($read->getResult()) {
            $this->resultsUpdates = $read->getResult();
            $this->setTabela($tabela);

            $this->dados = $dados;
            $this->dados['system_id'] = (!empty($_SESSION['userlogin']['system_id']) ? $_SESSION['userlogin']['system_id'] : null);

            $this->termos = (string)$termos;

            if (!empty($parseString))
                parse_str($parseString, $this->places);
            else
                $this->places = [];

            $this->execute();
        }

    }

    /**
     * <b>Obter resultado:</b> Retorna TRUE se não ocorrer erros, ou FALSE. Mesmo não alterando os dados se uma query
     * for executada com sucesso o retorno será TRUE. Para verificar alterações execute o getRowCount();
     * @return BOOL $Var = True ou False
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * <b>Contar Registros: </b> Retorna o número de linhas alteradas no banco!
     * @return INT $Var = Quantidade de linhas alteradas
     */
    public function getRowCount()
    {
        return $this->update->rowCount();
    }

    /**
     * <b>Modificar Links:</b> Método pode ser usado para atualizar com Stored Procedures. Modificando apenas os valores
     * da condição. Use este método para editar múltiplas linhas!
     * @param STRING $ParseString = id={$id}&..
     */
    public function setPlaces(string $ParseString)
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
        $this->update = $this->conn->prepare($this->update);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax()
    {
        $this->dadosName = [];
        foreach ($this->dados as $Key => $Value) {
            $Places[] = "`{$Key}` = :" . str_replace('-', '_', \Helpers\Check::name($Key));
            $this->dadosName[str_replace('-', '_', \Helpers\Check::name($Key))] = $Value;
        }

        $Places = implode(', ', $Places);
        $this->update = "UPDATE {$this->tabela} SET {$Places} {$this->termos}";
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function execute()
    {
        $this->getSyntax();
        $this->connect();
        try {
            $this->update->execute(array_merge($this->dadosName, $this->places));
            $this->result = true;

            /**
             * Garante que todos os campos estejam presentes nos dados
            */
            foreach ($this->resultsUpdates[0] as $col => $value) {
                if(!isset($this->dados[$col]))
                    $this->dados[$col] = $value;
            }

            $this->react = new React("update", str_replace(PRE, '', $this->tabela), $this->dados, $this->resultsUpdates[0] ?? []);
        } catch (\PDOException $e) {
            $this->result = null;
            $this->erro = "<b>(Update) Erro ao Ler: ({$this->tabela})</b> {$e->getMessage()}";
        }

        parent::setDefault();
    }
}
