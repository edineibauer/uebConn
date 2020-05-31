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

abstract class Conn
{
    private static $host = HOST ?? null;
    private static $user = USER ?? null;
    private static $pass = PASS ?? null;
    private static $database = DATABASE ?? null;

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
     * @return string
     */
    protected static function getDatabase(): string
    {
        return self::$database;
    }

    /**
     * Conecta com o banco de dados com o pattern singleton.
     * Retorna um objeto PDO!
     */
    private static function conectar()
    {
        try {
            if (self::$connect == null):
                $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$database;
                $options = [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'];
                self::$connect = new \PDO($dsn, self::$user, self::$pass, $options);
            endif;
        } catch (\PDOException $e) {
            self::error("<b>Erro ao se conectar ao Banco</b><br><br> #Linha: {$e->getLine()}<br> {$e->getMessage()}", $e->getCode());
            die;
        }

        self::$connect->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return self::$connect;
    }

    /** Retorna um objeto PDO Singleton Pattern. */
    protected static function getConn()
    {
        return self::conectar();
    }

    protected static function setDefault()
    {
        self::setDatabase(DATABASE ?? null);
        self::setHost(HOST ?? null);
        self::setUser(USER ?? null);
        self::setPass(PASS ?? null);
    }

    protected static function error($ErrMsg, $ErrNo = null)
    {
        $color = ["blue" => "lightskyblue", "yellow" => "gold", "green" => "steal", "red" => "lightcoral", "orange" => "orange"];
        $background = ($ErrNo == E_USER_NOTICE ? $color["blue"] : ($ErrNo == E_USER_WARNING ? $color['yellow'] : ($ErrNo == E_USER_ERROR ? $color['red'] : $color['orange'])));
        echo "<p style='width: 100%;float:left;clear:both; padding:10px 30px; background: {$background}; border-radius: 4px; font-weight: bold; box-shadow: 1px 4px 9px -2px rgba(0, 0, 0, 0.15); text-transform: uppercase; width: auto' >{$ErrMsg}</p>";
    }

    /**
     * Aplica clausula WHERE padrão para consultas no banco
     *
     * @param string $queryCommand
     * @param string $tabela
     * @param array $info
     * @param bool $ignoreSystem
     * @return string
     */
    protected static function addLogicMajor(string $queryCommand, string $tabela = "", array $info = [], bool $ignoreSystem = !1): string
    {
        if ($ignoreSystem || (!empty($_SESSION['userlogin']['setor']) && $_SESSION['userlogin']['setor'] === "admin"))
            return $queryCommand;

        $system = "system_id";
        if (empty($tabela)) {
            $from = explode("FROM ", $queryCommand);
            if (!empty($from[1])) {
                $tabela = explode(" ", $from[1])[0];

                if (!empty($tabela) && preg_match("/FROM {$tabela} as /i", $queryCommand))
                    $system = explode(" ", explode("FROM {$tabela} as ", $queryCommand)[1])[0] . ".system_id";
            }
        }

        /**
         * Se tiver tabela reconhecida
         */
        if (!empty($tabela)) {
            if(empty($info))
                $info = Metadados::getInfo(str_replace(PRE, "", $tabela));

            $whereSetor = "";
            if (!empty($info['setor']) && !empty($_SESSION['userlogin']['setor']) && $_SESSION['userlogin']['setor'] !== "admin")
                $whereSetor = " {$info['setor']} = '{$_SESSION['userlogin']['setor']}'";

            if (!empty($info['system']) || !empty($whereSetor)) {
                if (preg_match("/WHERE /i", $queryCommand)) {
                    $command = "WHERE ";
                    $query = explode($command, $queryCommand);

                } elseif (preg_match("/ GROUP BY /i", $queryCommand)) {
                    $command = " GROUP BY ";
                    $query = explode($command, $queryCommand);

                } elseif (preg_match("/ HAVING /i", $queryCommand)) {
                    $command = " HAVING ";
                    $query = explode($command, $queryCommand);

                } elseif (preg_match("/ ORDER BY /i", $queryCommand)) {
                    $command = " ORDER BY ";
                    $query = explode($command, $queryCommand);

                } elseif (preg_match("/ LIMIT /i", $queryCommand)) {
                    $command = " LIMIT ";
                    $query = explode($command, $queryCommand);

                } elseif (preg_match("/ OFFSET /i", $queryCommand)) {
                    $command = " OFFSET ";
                    $query = explode($command, $queryCommand);
                }

                if(!empty($info['system'])) {
                    $whereSetor .= (!empty($whereSetor) ? " && " : "");
                    if (isset($command) && !empty($query[1])) {
                        $queryCommand = $query[0] . " WHERE{$whereSetor} ({$system} IS NULL || {$system} = ''" . (empty($_SESSION['userlogin']['system_id']) ? "" : " || {$system} = '{$_SESSION['userlogin']['system_id']}'") . ")" . ($command === "WHERE " ? " && " : $command) . $query[1];
                    } else {
                        $queryCommand .= " WHERE{$whereSetor} ({$system} IS NULL || {$system} = ''" . (empty($_SESSION['userlogin']['system_id']) ? "" : " || {$system} = '{$_SESSION['userlogin']['system_id']}'") . ")";
                    }
                } else {
                    if (isset($command) && !empty($query[1])) {
                        $queryCommand = $query[0] . " WHERE{$whereSetor}" . ($command === "WHERE " ? " && " : $command) . $query[1];
                    } else {
                        $queryCommand .= " WHERE{$whereSetor}";
                    }
                }
            }
        }

        return $queryCommand;
    }
}
