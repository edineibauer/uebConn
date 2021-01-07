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

abstract class Conn
{
    private static $host = HOST ?? null;
    private static $user = USER ?? null;
    private static $pass = PASS ?? null;
    private static $database = DATABASE ?? null;
    private static $error = "";

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
    public static function setError(string $error) {
        self::$error = $error;
    }

    /**
     * @return string
     */
    public static function getError(): string {
        return self::$error;
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
        self::setError("");
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
     * Check if have read commando on query, if have
     * add all table read name on SESSION
     *
     * @param string|null $queryCommand
     */
    protected static function addEntitysToSession(string $queryCommand = null)
    {
        if(empty($queryCommand))
            return;

        $from = explode(" FROM ", $queryCommand);
        if (!empty($from[1])) {
            foreach ($from as $i => $tableName) {
                if($i === 0)
                    continue;

                $entityToAdd = (!empty(PRE) ? preg_replace('/'.preg_quote(PRE, '/').'/', '', explode(" ", $tableName)[0], 1) : explode(" ", $tableName)[0]);
                if(empty($_SESSION['db']) || !in_array($entityToAdd, $_SESSION['db']))
                    $_SESSION['db'][] = $entityToAdd;
            }
        }

        $from = explode(" JOIN ", $queryCommand);
        if (!empty($from[1])) {
            foreach ($from as $i => $tableName) {
                if($i === 0)
                    continue;

                $entityToAdd = (!empty(PRE) ? preg_replace('/'.preg_quote(PRE, '/').'/', '', explode(" ", $tableName)[0], 1) : explode(" ", $tableName)[0]);
                if(empty($_SESSION['db']) || !in_array($entityToAdd, $_SESSION['db']))
                    $_SESSION['db'][] = $entityToAdd;
            }
        }
    }

    /**
     * Aplica clausula WHERE padrão para consultas no banco
     *
     * @param string $queryCommand
     * @param string $tabela
     * @param array $info
     * @param bool $ignoreSystem
     * @param bool $ignoreOwnerpub
     * @return string
     */
    protected static function getQueryWithSystemAndOwnerProtection(string $queryCommand, string $tabela = "", array $info = [], bool $ignoreSystem = false, bool $ignoreOwnerpub = false): string
    {
        $setor = Config::getSetor();
        $ignoreSystem = ($ignoreSystem || $setor === "admin" || empty($_SESSION['userlogin']['system_id']));
        $ignoreOwnerpub = ($ignoreOwnerpub || $setor === "admin");

        if ($ignoreSystem && $ignoreOwnerpub)
            return $queryCommand;

        $queryLogic = explode("WHERE ", $queryCommand);
        $queryBody = " " . explode(" GROUP BY ", $queryLogic[1])[0];
        if(!$ignoreSystem && ((count($queryLogic) > 1 && preg_match("/ system_id\s*=/i", $queryBody)) || empty($_SESSION['userlogin']['system_id'])))
            $ignoreSystem = true;

        if(!$ignoreOwnerpub && (count($queryLogic) > 1 && preg_match("/ ownerpub\s*=/i", $queryBody)))
            $ignoreOwnerpub = true;

        if ($ignoreSystem && $ignoreOwnerpub)
            return $queryCommand;

        /**
         * SqlCommand not send the table, so search for it
         */
        $prefix = "";
        $system = "system_id";
        if (empty($tabela)) {
            $from = explode("FROM ", $queryCommand);
            if (!empty($from[1])) {
                $tabela = explode(" ", $from[1])[0];

                if (!empty($tabela) && preg_match("/FROM {$tabela} as /i", $queryCommand)) {
                    $prefix = explode(" ", explode("FROM {$tabela} as ", $queryCommand)[1])[0];
                    $prefix = !empty($prefix) ? $prefix . "." : "";
                    $system = $prefix . "system_id";
                }
            }
        }

        /**
         * Check for read permission to this user
         */
        $permissoes = Config::getPermission();
        if(isset($permissoes[$setor][$tabela]['read']) && !$permissoes[$setor][$tabela]['read']) {
            self::$error = $setor . " não tem permissão de leitura em " . $tabela;
            return (empty($tabela) ? "SELECT * FROM " . PRE . $tabela . " " : "") . "WHERE id < 0";
        }

        /**
         * Se tiver tabela reconhecida
         */
        if (!empty($tabela)) {
            if(empty($info))
                $info = Metadados::getInfo(str_replace(PRE, "", $tabela));

            $whereSetor = "";

            /**
             * where register setor like my setor
             */
            if (!empty($info['setor']))
                $whereSetor = " {$prefix}{$info['setor']} = '{$setor}'";

            /**
             * where register owner like me
             */
            if(!$ignoreOwnerpub && !empty($info['autor']) && $info['autor'] === 2)
                $whereSetor .= (empty($whereSetor) ? "" : " && ") . " {$prefix}ownerpub = '" . ($setor != "0" ? $_SESSION['userlogin']['id'] : "0") . "'";

            if (!empty($info['system']) || !empty($whereSetor)) {
                if (preg_match("/WHERE /i", $queryCommand)) {
                    $command = "WHERE ";
                    $query = explode($command, $queryCommand, 2);

                } elseif (preg_match("/ GROUP BY /i", $queryCommand)) {
                    $command = " GROUP BY ";
                    $query = explode($command, $queryCommand, 2);

                } elseif (preg_match("/ HAVING /i", $queryCommand)) {
                    $command = " HAVING ";
                    $query = explode($command, $queryCommand, 2);

                } elseif (preg_match("/ ORDER BY /i", $queryCommand)) {
                    $command = " ORDER BY ";
                    $query = explode($command, $queryCommand, 2);

                } elseif (preg_match("/ LIMIT /i", $queryCommand)) {
                    $command = " LIMIT ";
                    $query = explode($command, $queryCommand, 2);

                } elseif (preg_match("/ OFFSET /i", $queryCommand)) {
                    $command = " OFFSET ";
                    $query = explode($command, $queryCommand, 2);
                }

                if(!empty($info['system']) && !$ignoreSystem)
                    $whereSetor .= (!empty($whereSetor) ? " && " : "") . " ({$system} IS NULL || {$system} = ''" . ($setor != "0" ? " || {$system} = '{$_SESSION['userlogin']['system_id']}'" : "") . ")";

                if (isset($command) && !empty($query[1])) {
                    $haveWhere = $command === "WHERE ";
                    $queryCommand = $query[0] . (!empty($whereSetor) ? " WHERE{$whereSetor}" . ($haveWhere ? " && " : " ") : ($haveWhere ? "WHERE " : "")) . (!$haveWhere ? $command : "") . $query[1];
                } elseif(!empty($whereSetor)) {
                    $queryCommand .= " WHERE{$whereSetor}";
                }
            }
        }

        return $queryCommand;
    }
}