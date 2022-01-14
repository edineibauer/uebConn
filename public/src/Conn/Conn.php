<?php

/**
 * Conn [ CONEXÃO ]
 * Classe abstrata de conexão. Padrão SingleTon.
 * Retorna um objeto PDO pelo método estático getConn();
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

use Entity\Metadados;
use Config\Config;
use Entity\React;

abstract class Conn
{
    private static $host = HOST ?? null;
    private static $user = USER ?? null;
    private static $pass = PASS ?? null;
    private static $database = DATABASE ?? null;
    private static $error = "";
    private static $result = "";
    private static $reactData = "";
    private static $rowCount = 0;

    /** @var PDO */
    private static $connect = null;

    /**
     * @param string $database
     */
    public static function setDatabase(string $database)
    {
        self::$connect = null;
        self::$database = $database;
    }

    /**
     * @param string $host
     */
    public static function setHost(string $host)
    {
        self::$host = $host;
    }

    /**
     * @param string $pass
     */
    public static function setPass(string $pass)
    {
        self::$pass = $pass;
    }

    /**
     * @param string $user
     */
    public static function setUser(string $user)
    {
        self::$user = $user;
    }

    /**
     * @param string $error
     */
    public static function setError(string $error)
    {
        self::$error = $error;
        self::$result = null;
    }

    /**
     * @param $result
     * @param int $rowCount
     * @param $reactData
     * @return void
     */
    public static function setResult($result = null, int $rowCount = 0, $reactData = null)
    {
        self::$result = $result;
        self::$reactData = $reactData;
        self::$rowCount = $rowCount;
        self::$error = "";
    }

    /**
     * @return string
     */
    protected static function getDatabase(): string
    {
        return self::$database;
    }

    /** Retorna um objeto PDO Singleton Pattern. */
    protected static function getConn()
    {
        self::$error = "";

        try {
            if (self::$connect == null) {
                $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$database;
                $options = [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8, @@sql_mode = STRICT_ALL_TABLES, @@foreign_key_checks = 1'
                ];
                self::$connect = new \PDO($dsn, self::$user, self::$pass, $options);
            }
        } catch (\PDOException $e) {
            self::error("<b>Erro ao se conectar ao Banco</b><br><br> #Linha: {$e->getLine()}<br> {$e->getMessage()}", $e->getCode());
        }

        return self::$connect;
    }

    protected static function setDefault()
    {
        self::setDatabase(DATABASE ?? null);
        self::setHost(HOST ?? null);
        self::setUser(USER ?? null);
        self::setPass(PASS ?? null);
        self::$result = "";
        self::$reactData = "";
        self::$rowCount = 0;
        self::$error = "";
    }

    /**
     * @param string $action
     * @param string $table
     * @param string $sql
     * @param array $places
     * @param array $dados
     * @return array
     */
    protected static function exeSql(string $action, string $table = null, string $sql = null, array $places = [], array $dados = []): array
    {
        switch ($action) {
            case "sql":
                self::exeSqlFree($sql);
                break;
            case "read":
                self::exeSqlRead($sql, $places);
                break;
            case "update":
                self::exeSqlUpdate($table, $sql, $places, $dados);
                break;
            case "delete":
                self::exeSqlDelete($table, $sql, $places);
                break;
            case "create":
                self::exeSqlCreate($table, $sql, $places, $dados);
                break;
        }

        $dadosReturn = [self::$result, self::$reactData, self::$rowCount, self::$error];

        self::setDefault();

        return $dadosReturn;
    }

    private static function operation($val1, $operation, $val2)
    {
        switch ($operation) {
            case "/":
                return $val1 / $val2;
                break;
            case "*":
                return $val1 * $val2;
                break;
            case "+":
                return $val1 + $val2;
                break;
            case "-":
                return $val1 - $val2;
                break;
        }
    }

    /**
     * @param string $sql
     * @param array $places
     * @return void
     */
    private static function exeSqlFree(string $sql)
    {
        try {
            /**
             * Executa a operação no banco
             */
            $conn = self::getConn();
            $op = $conn->prepare($sql);
            $op->execute();

            self::setResult(1, 1);

        } catch (\PDOException $e) {
            self::error("<b>Erro SQL:</b> {$e->getMessage()}", $e->getCode());
        }
    }

    /**
     * @param string $sql
     * @param array $places
     * @return void
     */
    private static function exeSqlRead(string $sql, array $places = [])
    {
        try {
            /**
             * Executa a operação no banco
             */
            $conn = self::getConn();
            $op = $conn->prepare($sql);

            if (!empty($places)) {
                foreach ($places as $Vinculo => $Valor) {
                    if ($Vinculo == 'limit' || $Vinculo == 'offset')
                        $Valor = (int)$Valor;

                    $op->bindValue(":{$Vinculo}", $Valor, (is_int($Valor) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
                }
            }

            $op->setFetchMode(\PDO::FETCH_ASSOC);
            $op->execute($places);

            self::setResult($op->fetchAll(), $op->rowCount());

        } catch (\PDOException $e) {
            self::error("<b>Erro ao Ler:</b> {$e->getMessage()}", $e->getCode());
        }
    }

    /**
     * @param string $table
     * @param string $sql
     * @param array $places
     * @param array $dados
     * @return void
     */
    private static function exeSqlUpdate(string $table, string $sql = null, array $places = [], array $dados = [])
    {
        $dadosBefore = self::readExeSql($table, (!empty($sql) ? "WHERE " . explode("WHERE ", $sql)[1] : ""), $places);
        if (count($dadosBefore) > 0) {

            try {

                /**
                 * Executa a operação no banco
                 */
                $conn = self::getConn();

                $op = $conn->prepare($sql);
                $dadosAfter = $dadosBefore;

                $placesData = [];
                foreach ($dados as $Key => $Value) {
                    $ValueSignal = substr(trim($Value), 0, 1);
                    $ValueNumber = substr(str_replace(" ", "", trim($Value)), 1);

                    $namePlace = str_replace('-', '_', \Helpers\Check::name($Key));
                    if (is_numeric($ValueNumber) && in_array($ValueSignal, ["+", "-", "*", "/"])) {
                        $ValueNumber = ($ValueSignal === "/" && $ValueNumber == 0 ? 1 : $ValueNumber);
                        $sqlSet[] = "`{$Key}` = " . $Key . " " . $ValueSignal . ":" . $namePlace;
                        $placesData[$namePlace] = $ValueNumber;

                        foreach ($dadosAfter as $i => $dadosTable)
                            $dadosAfter[$i][$Key] = self::operation($dadosBefore[$i][$Key], $ValueSignal, $ValueNumber);

                    } else {
                        $placesData[$namePlace] = $Value;

                        foreach ($dadosAfter as $i => $dadosTable)
                            $dadosAfter[$i][$Key] = $Value;
                    }
                }

                $op->execute(array_merge($places, $placesData));

                /**
                 * Executa a reação para cada resultado da atualização
                 */
                $reactError = false;
                foreach ($dadosBefore as $indice => $item) {
                    $react = new React("update", $table, $dadosAfter[$indice], $item);
                    $react = $react->getResponse();

                    if (!empty($react["error"])) {
                        $reactError = true;
                        self::setError($react["error"]);
                        break;
                    }
                }

                if (!$reactError)
                    self::setResult($dadosAfter, self::$rowCount, (!empty($react["data"]) ? $react["data"] : null));

            } catch (\PDOException $e) {
                self::error("<b>Erro ao Atualizar:</b> {$e->getMessage()}", $e->getCode());
            }
        }
    }

    /**
     * @param string $table
     * @param string $sql
     * @param array $places
     * @return void
     */
    private static function exeSqlDelete(string $table, string $sql = null, array $places = [])
    {
        $dadosBefore = self::readExeSql($table, (!empty($sql) ? "WHERE " . explode("WHERE ", $sql)[1] : ""), $places);
        if (count($dadosBefore) > 0) {
            try {

                /**
                 * Executa a operação no banco
                 */
                $conn = self::getConn();
                $op = $conn->prepare($sql);
                $op->execute($places);

                /**
                 * Executa a reação
                 */
                $reactError = false;
                foreach ($dadosBefore as $item) {
                    $react = new React("update", $table, $item, $item);
                    $react = $react->getResponse();

                    if (!empty($react["error"])) {
                        $reactError = true;
                        self::setError($react["error"]);
                        break;
                    }
                }

                if (!$reactError)
                    self::setResult($dadosAfter, self::$rowCount, (!empty($react["data"]) ? $react["data"] : null));

            } catch (\PDOException $e) {
                self::error("<b>Erro ao Excluir:</b> {$e->getMessage()}", $e->getCode());
            }
        }
    }

    /**
     * @param string $table
     * @param string $sql
     * @param array $places
     * @param array $dados
     * @return void
     */
    private static function exeSqlCreate(string $table, string $sql = null, array $places = [], array $dados = [])
    {
        try {
            /**
             * Executa a operação no banco
             */
            $conn = self::getConn();
            $op = $conn->prepare($sql);
            $op->execute($places);

            /**
             * Executa a reação
             */
            $react = new React("create", $table, $dados, []);
            $react = $react->getResponse();

            if (!empty($react["error"]))
                self::setError($react["error"]);
            else
                self::setResult($conn->lastInsertId(), 1, $react["data"]);

        } catch (\PDOException $e) {
            self::error("<b>Erro ao Criar:</b> {$e->getMessage()}", $e->getCode());
        }
    }

    /**
     * @param string $table
     * @param string|null $sql
     * @param array $places
     * @return array
     */
    private static function readExeSql(string $table, string $sql = null, array $places = []): array
    {
        $read = new Read();
        $read->exeRead($table, $sql, $places);
        return $read->getResult();
    }

    protected static function error($ErrMsg, $ErrNo = null)
    {
        $color = ["blue" => "lightskyblue", "yellow" => "gold", "green" => "steal", "red" => "lightcoral", "orange" => "orange"];
        $background = ($ErrNo == E_USER_NOTICE ? $color["blue"] : ($ErrNo == E_USER_WARNING ? $color['yellow'] : ($ErrNo == E_USER_ERROR ? $color['red'] : $color['orange'])));
        echo "<p style='width: 100%;float:left;clear:both; padding:10px 30px; background: {$background}; border-radius: 4px; font-weight: bold; box-shadow: 1px 4px 9px -2px rgba(0, 0, 0, 0.15); text-transform: uppercase; width: auto' >{$ErrMsg}</p>";
        die;
    }
}