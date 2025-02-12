<?php

/**
 * <b>Update:</b>
 * Classe responsável por atualizações genéricas no banco de dados!
 *
 * @copyright (c) 2017, Edinei J. Bauer
 */

namespace Conn;

use Entity\Dicionario;
use Entity\React;

class Update extends Conn
{
    private $result;
    private $react;
    private $rowCount;
    private $error;

    /**
     * @return mixed
     */
    public function getErro()
    {
        return $this->error;
    }

    /**
     * <b>Obter resultado:</b> Retorna TRUE se não ocorrer erros, ou FALSE. Mesmo não alterando os dados se uma query
     * for executada com sucesso o retorno será TRUE. Para verificar alterações execute o getRowCount();
     * @return BOOL $Var = True ou False
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * <b>Contar Registros: </b> Retorna o número de linhas alteradas no banco!
     * @return INT $Var = Quantidade de linhas alteradas
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getReact()
    {
        return ["data" => $this->react, "response" => (!empty($this->error) ? 2 : 1) , "error" => $this->error];
    }

    /**
     * @param string $table
     * @param array $dados
     * @param string $termos
     * @param $places
     * @return void
     */
    public function exeUpdate(string $table, array $dados, string $termos, $places = [])
    {
        if (!empty($places) && is_string($places))
            parse_str($places, $places);

        $sqlSet = [];
        foreach ($dados as $Key => $Value) {
            $namePlace = str_replace('-', '_', \Helpers\Check::name($Key));

            if(is_string($Value)) {
                $ValueSignal = substr(trim($Value), 0, 1);

                if(in_array($ValueSignal, ["+", "*", "/", "-"])) {
                    $ValueSignalSpace = substr(trim($Value), 1, 1) === " ";
                    $ValueNumber = substr(str_replace(" ", "", trim($Value)), 1);

                    if (is_numeric($ValueNumber) && ($ValueSignal !== "-" || $ValueSignalSpace)) {
                        $sqlSet[] = "`{$Key}` = " . $Key . " " . $ValueSignal . ":" . $namePlace;
                    } else {
                        $sqlSet[] = "`{$Key}` = :" . $namePlace;
                    }
                } else {
                    $sqlSet[] = "`{$Key}` = :" . $namePlace;
                }
            } else {
                $sqlSet[] = "`{$Key}` = :" . $namePlace;
            }
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sqlSet) . " {$termos}";
        list($this->result, $this->react, $this->rowCount, $this->error) = self::exeSql("update", $table, $sql, $places, $dados);
    }
}
