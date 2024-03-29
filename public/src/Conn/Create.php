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

        //system_id
        $dados['system_id'] = (empty($dados['system_id']) ? (!empty($_SESSION['userlogin']['setorData']['system_id']) ? $_SESSION['userlogin']['setorData']['system_id'] : null) : $dados['system_id']);

        //system_entity
        if(!empty($_SESSION['userlogin']['setor']) && $_SESSION['userlogin']['setor'] !== "0") {
            $infoUser = Metadados::getInfo($_SESSION['userlogin']['setor']);
            $dados['system_entity'] = (empty($dados['system_entity']) ? (!empty($infoUser['system']) ? $infoUser['system'] : null) : $dados['system_entity']);
        } elseif(empty($dados['system_entity']) && !empty($info)) {
            $dados['system_entity'] = $info['system'];
        }

        if(!empty($info)) {
            if ($info['autor'] === 2)
                $dados['ownerpub'] = (empty($dados['ownerpub']) ? (!empty($_SESSION['userlogin']['id']) ? $_SESSION['userlogin']['id'] : null) : $dados['ownerpub']);
            elseif ($info['autor'] === 1)
                $dados['autorpub'] = (empty($dados['autorpub']) ? (!empty($_SESSION['userlogin']['id']) ? $_SESSION['userlogin']['id'] : null) : $dados['autorpub']);
        }

        $Fileds = "`" . implode("`, `", array_keys($dados)) . "`";
        $places = [];
        foreach ($dados as $key => $dado)
            $places[str_replace('-', '_', \Helpers\Check::name($key))] = $dado;


        $sql = "INSERT INTO {$table} ({$Fileds}) VALUES (:" . implode(', :', array_keys($places)) . ")";

        list($this->result, $this->react, $this->rowCount, $this->error) = parent::exeSql("create", $table, $sql, $places, $dados);
    }

}