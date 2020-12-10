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
    private $result;
    private $ignoreSystem;

    /** @var PDOStatement */
    private $command;

    /** @var PDO */
    private $conn;

    public function __construct(bool $ignoreSystem = false)
    {
        $this->ignoreSystem = $ignoreSystem;
    }

    /**
     * @return mixed
     */
    public function getErro()
    {
        return self::getError();
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
        parent::addEntitysToSession($Query);

        $queryLogic = explode(" WHERE ", $Query);
        if($ignoreSystem || (count($queryLogic) > 1 && preg_match("/system_id/i", explode(" GROUP BY ", $queryLogic[1])[0])))
            $this->ignoreSystem = !0;

        $this->select = parent::addLogicMajor((string)$Query, "", [], $this->ignoreSystem, (count($queryLogic) > 1 && preg_match("/ownerpub/i", $queryLogic[1])));
        $this->execute();
    }

    /**
     * ****************************************
     * *********** PRIVATE METHODS ************
     * ****************************************
     */

    /**
     * Check if have read commando on query, if have
     * add all table read name on SESSION
     */
    private function addEntitysToSession(string $queryCommand)
    {
        $from = explode(" FROM ", $queryCommand);
        if (!empty($from[1])) {
            foreach ($from as $i => $tableName) {
                if($i === 0)
                    continue;

                $_SESSION['db'][] = (!empty(PRE) ? preg_replace('/'.preg_quote(PRE, '/').'/', '', explode(" ", $tableName)[0], 1) : explode(" ", $tableName)[0]);
            }
        }

        $from = explode(" JOIN ", $queryCommand);
        if (!empty($from[1])) {
            foreach ($from as $i => $tableName) {
                if($i === 0)
                    continue;

                $_SESSION['db'][] = (!empty(PRE) ? preg_replace('/'.preg_quote(PRE, '/').'/', '', explode(" ", $tableName)[0], 1) : explode(" ", $tableName)[0]);
            }
        }
    }

    //Obtém o PDO e Prepara a query
    private function connect()
    {
        $this->conn = parent::getConn();
        $this->command = $this->conn->prepare($this->select);
        $this->command->setFetchMode(\PDO::FETCH_ASSOC);
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function execute()
    {
        $this->connect();
        try {
            $this->command->execute();

            if (preg_match('/^(SELECT|SHOW) /i', $this->select))
                $this->result = $this->command->fetchAll();

        } catch (\PDOException $e) {
            $this->result = "<b>Erro ao Executar Comando: </b> {$e->getMessage()}";
            self::setError("<b>Erro ao Executar Comando</b> {$e->getMessage()}");
        }

        parent::setDefault();
    }
}
