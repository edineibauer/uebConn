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
    public $select;
    private $result;
    private $ignoreSystem;
    private $ignoreOwnerpub;

    /** @var PDOStatement */
    private $command;

    /** @var PDO */
    private $conn;

    /**
     * SqlCommand constructor.
     * @param bool $ignoreSystem
     * @param bool $ignoreOwnerpub
     */
    public function __construct(bool $ignoreSystem = false, bool $ignoreOwnerpub = false)
    {
        $this->ignoreSystem = $ignoreSystem;
        $this->ignoreOwnerpub = $ignoreOwnerpub;
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
    public function exeCommand($Query, $ignoreSystem = null, $ignoreOwnerpub = null)
    {
        parent::addEntitysToSession($Query);
        $this->select = parent::getQueryWithSystemAndOwnerProtection((string)$Query, "", [], $this->ignoreSystem || $ignoreSystem !== null, $this->ignoreOwnerpub ||$ignoreOwnerpub !== null);
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
