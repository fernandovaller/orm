<?php

namespace FVCode\Orm;

abstract class Model extends ModelDao
{
    /**
     * Alias para find()
     * Retornas os dados de um registro pelo ID
     *
     * @param integer $id ID da chave primaria da tabela
     *
     * @return array dados de um registro
     */
    public static function get($id)
    {
        $obj = new static();

        return $obj->find($id);
    }

    public static function _get($id)
    {
        $obj = new static();

        return $obj->_find($id);
    }

    /**
     * Alias para findAll()
     * Retorna todos os registros da tabela com a ordenação passada
     * Ordenação padrão pela chave primaria
     *
     * @param string $order Coluna de ordenação dos resultados [Ex: nome ASC]
     *
     * @return array todos os registros da tabela
     */
    public static function getAll($order = '')
    {
        $obj = new static();

        return $obj->findAll($order);
    }

    /**
     * Cadastra um novo registro
     *
     * @return integer ID do novo registro
     * @throws \Exception
     */
    public static function cadastrar($dados)
    {
        if (empty($dados)) {
            throw new \Exception('Uninformed data');
        }

        $obj = new static();

        return $obj->insert($dados);
    }

    /**
     * Atualiza os dados de um registro
     *
     * @param array $data Dados a serem atualizados
     * @param integer $id Id do registro a ser atualizado
     *
     * @throws \Exception
     */
    public static function atualizar($data, $id)
    {
        if (empty($data)) {
            throw new \Exception('Uninformed data');
        }

        if (empty($id)) {
            throw new \Exception('id not given');
        }

        $obj = new static();

        return $obj->update($data, $id);
    }

    /**
     * Atualiza os dados de um registro
     *
     * @param array $data Dados a serem atualizados
     * @param integer $id Id do registro a ser atualizado
     *
     * @throws \Exception
     */
    public static function atualizarOnde($data, $where, $limit = '')
    {
        if (empty($data)) {
            throw new \Exception('Uninformed data');
        }

        $obj = new static();

        return $obj->updateWhere($data, $where, $limit);
    }

    /**
     * Deletar um registro
     *
     * @param integer $id ID da chave primaria da tabela
     *
     * @return boolean true|false
     * @throws \Exception
     */
    public static function deletar($id)
    {
        if (empty($id)) {
            throw new \Exception('id not given');
        }

        $obj = new static();

        return $obj->delete($id);
    }
}
