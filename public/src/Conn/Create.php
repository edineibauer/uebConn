<?php

/**
 * <b>Create:</b>
 * Classe responsável por cadastros genéricos no banco de dados!
 *
 * @copyright (c) 2021, Edinei J. Bauer
 */

namespace Conn;

use Entity\Meta;
use Entity\Metadados;

class Create extends Conn
{
    private $result;
    private $react;
    private $rowCount;
    private $error;

    public function getResult()
    {
        return $this->result;
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getErro()
    {
        return $this->error;
    }

    public function getReact()
    {
        return ["data" => $this->react, "response" => (!empty($this->error) ? 2 : 1) , "error" => $this->error];
    }

    /**
     * @param $table
     * @param array $dados
     * @return void
     */
    public function exeCreate($table, array $dados)
    {
        $info = Metadados::getInfo($table);
        $meta = Metadados::getDicionario($table);

        foreach ($meta as $item) {
            if(!isset($dados[$item["column"]])) {
                if($item["default"] !== false) {
                    $dados[$item["column"]] = !empty($item["default"]) ? $item["default"] : null;
                } else {
                    $this->error = "Coluna '{$item["column"]}' obrigatória";
                    return;
                }
            }
        }

        $dados['system_id'] = (empty($dados['system_id']) ? (!empty($_SESSION['userlogin']['setorData']['system_id']) ? $_SESSION['userlogin']['setorData']['system_id'] : null) : $dados['system_id']);

        if ($info['autor'] === 2)
            $dados['ownerpub'] = (empty($dados['ownerpub']) ? (!empty($_SESSION['userlogin']['id']) ? $_SESSION['userlogin']['id'] : null) : $dados['ownerpub']);
        elseif ($info['autor'] === 1)
            $dados['autorpub'] = (empty($dados['autorpub']) ? (!empty($_SESSION['userlogin']['id']) ? $_SESSION['userlogin']['id'] : null) : $dados['autorpub']);

        $Fileds = "`" . implode("`, `", array_keys($dados)) . "`";
        $places = [];
        foreach ($dados as $key => $dado)
            $places[str_replace('-', '_', \Helpers\Check::name($key))] = $dado;


        $sql = "INSERT INTO {$table} ({$Fileds}) VALUES (:" . implode(', :', array_keys($places)) . ")";

        list($this->result, $this->react, $this->rowCount, $this->error) = parent::exeSql("create", $table, $sql, $places, $dados);
    }

}