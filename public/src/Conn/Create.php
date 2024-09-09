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

        /**
         * Caso não seja informado um system_id
         */
        if(empty($dados['system_id'])) {

            /**
             * Verifica se o usuário que esta criando o registro tem um setor especificado
             */
            if(!empty($_SESSION['userlogin']['setor']) && !empty($_SESSION['userlogin']['system_id'])) {

                $infoUser = Metadados::getInfo($_SESSION['userlogin']['setor']);
                $dados['system_id'] = $_SESSION['userlogin']['system_id'];
                $dados['system_entity'] = $infoUser['system'];

            } else if($info['systemRequired']) {

                $this->error = "Obrigatório que o registro seja criado por um usuários identificado ou que seja informado no formulário.";

            } else {
                /**
                 * Como o usuário identificado que criou o registro é diferente do esperado e não é obrigatório essa informação, deixa null
                 */
                $dados['system_id'] = null;
                $dados['system_entity'] = null;
            }

        } elseif(empty($dados['system_entity'])) {

            /**
             * Caso seja informado um system_id, mas não seja informado um system_entity
             */
            $dados['system_entity'] = (!empty($info['system']) ? $info['system'] : $_SESSION['userlogin']['setor'] ?? null);
        }

        if(!empty($this->error))
            return;

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