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
    private $result;
    private $rowCount;
    private $error;

    /**
     * SqlCommand constructor.
     * @param bool $ignoreSystem
     * @param bool $ignoreOwnerpub
     */
    public function __construct(bool $ignoreSystem = false, bool $ignoreOwnerpub = false)
    {
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return mixed
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

    /**
     * @param string $query
     * @param $ignoreSystem
     * @param $ignoreOwnerpub
     * @return void
     */
    public function exeCommand(string $query, $ignoreSystem = null, $ignoreOwnerpub = null)
    {
        $action = (preg_match('/^(SELECT|SHOW) /i', trim($query)) ? "read" : "sql");
        list($this->result, $react, $this->rowCount, $this->error) = self::exeSql($action, null, trim($query));
    }
}
