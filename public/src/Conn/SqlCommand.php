<?php
/**
 * <b>SqlCommand:</b>
 * Classe responsável por executar comandos sql, retorna por padrão valores como uma leitura
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

class SqlCommand extends Conn
{
    private $select;
    private $places;
    private $result;
    private $erro;

    /** @var PDOStatement */
    private $command;

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
        return $this->command->rowCount();
    }

    /**
     * @param $Query
     * @param bool|null $ignoreSystem
     */
    public function exeCommand($Query, $ignoreSystem = null)
    {
        $this->select = parent::addLogicMajor((string)$Query, "", [], $ignoreSystem !== null);
        $this->execute();
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

    //Obtém o PDO e Prepara a query
    private function connect()
    {
        $this->conn = parent::getConn();
        $this->command = $this->conn->prepare($this->select);
        $this->command->setFetchMode(\PDO::FETCH_ASSOC);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax()
    {
        if ($this->places):
            foreach ($this->places as $Vinculo => $Valor):
                if ($Vinculo == 'limit' || $Vinculo == 'offset'):
                    $Valor = (int)$Valor;
                endif;
                $this->command->bindValue(":{$Vinculo}", $Valor, (is_int($Valor) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
            endforeach;
        endif;
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function execute()
    {
        $this->connect();
        try {
            $this->getSyntax();
            $this->command->execute();

            if (preg_match('/^(SELECT|SHOW) /i', $this->select))
                $this->result = $this->command->fetchAll();

        } catch (\PDOException $e) {
            $this->result = "<b>Erro ao Executar Comando: </b> {$e->getMessage()}";
            $this->erro = "<b>Erro ao Executar Comando</b> {$e->getMessage()}";
        }

        parent::setDefault();
    }
}
