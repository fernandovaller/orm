<?php

namespace FVCode\Orm;

class ModelDao
{
    /** @var \PDO */
    protected $con;

    /** @var array|string */
    protected $where;

    /** @var array */

    protected $parms = [];

    /** @var string */
    protected $order;

    /** @var string */
    protected $offset;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->con = Conn::getConn();
    }

    /**
     * Monta uma query segura
     *
     * @param array $data Dados em key=>value
     * @param string $connector Tipo de conectivo da consulta [AND, OR]
     *
     * @return string
     */
    public function build($data, $connector = 'AND')
    {
        if (!is_array($data)) {
            return $data;
        }

        $query = [];

        foreach ($data as $key => $value) {
            // Hack para consultas com JOIN
            $key_sanitized = str_replace('.', '', $key);

            $query[] = sprintf('%s = :%s', $key, $key_sanitized);

            $this->parms = array_merge($this->parms, [
                $key_sanitized => $value,
            ]);
        }

        $separator = sprintf(' %s ', $connector);

        return implode($separator, array_filter($query, 'strlen'));
    }

    /**
     * Seta os parâmetros [where, order, offset]
     * Se where for array seta [parms]
     *
     * @param string|array $where
     * @param string $order
     * @param string $offset
     */
    public function setWhere($where = '', $order = '', $offset = '')
    {
        $where = $this->build($where);

        $this->where = $where ? sprintf('AND %s', $where) : '';

        $this->order = empty($order) ? sprintf('%s DESC', $this->id) : $order;

        $this->offset = !empty($offset) ? sprintf('OFFSET %s', $offset) : '';
    }

    /**
     * Retorna as colunas de uma tabela
     */
    public function getColumnNames()
    {
        try {
            $sql = sprintf('SHOW COLUMNS FROM %s', $this->table);
            $stmt = $this->con->prepare($sql);
            $stmt->execute();

            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $column = [];
            foreach ($data as $row) {
                $column[] = $row['Field'];
            }

            return $column;
        } catch (\PDOException $e) {
            trigger_error('Could not connect to MySQL database. ' . $e->getMessage(), E_USER_ERROR);
        }
    }

    /**
     * Executa um comando SQL sem retorno de dados
     * Usado para INSERT, UPDATE e DELETE
     *
     * @return boolean
     */
    public function run($sql, $parms = [], $debug = false)
    {
        $stmt = $this->con->prepare($sql);
        $data = $stmt->execute($parms);

        if ($debug) {
            var_dump($stmt->debugDumpParams());
        }

        return $data ? $stmt->rowCount() : false;
    }

    /**
     * Executa um comando SQL com retorno de dados
     *
     * @return array|false
     */
    public function query($sql, $parms = [], $debug = false)
    {
        $stmt = $this->con->prepare($sql);
        $stmt->execute($parms);

        if ($debug) {
            var_dump($stmt->debugDumpParams());
        }

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retornas os dados de um registro pelo ID
     *
     * @param integer $id ID da chave primaria da tabela
     *
     * @return array|false dados de um registro
     */
    public function find($id)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s = :id', $this->table, $this->id);

        $stmt = $this->con->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function _find($id)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s = :id', $this->table, $this->id);

        $stmt = $this->con->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os registros da tabela com a ordenação passada.
     * Ordenação padrão pela chave primaria
     *
     * @param string $order Coluna de ordenação dos resultados [Ex: nome ASC]
     *
     * @return array|false todos os registros da tabela
     */
    public function findAll($order = '')
    {
        $order = empty($order) ? sprintf('%s DESC', $this->id) : $order;

        $sql = sprintf('SELECT * FROM %s ORDER BY %s', $this->table, $order);

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os registros da tabela conforme condições informadas em $where
     *
     * @param string $where Condições da consulta SQL da clausula WHERE
     * @param string $order Coluna de ordenação dos resultados [Ex: nome ASC]
     * @param string $limit Limite dos resultados [Ex: LIMIT 100]
     * @param string $offset Clausula OFFSET da consulta SQL
     *
     * @return array|false Os registros da tabela
     */
    public function findWhere($where = '', $order = '', $limit = '', $offset = '')
    {
        if (!empty($where)) {
            $w = sprintf('AND %s', $where);
        }

        if (empty($order)) {
            $order = sprintf('%s DESC', $this->id);
        }

        if (!empty($offset)) {
            $offset = sprintf('OFFSET %s', $offset);
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE 1=1 %s ORDER BY %s %s %s',
            $this->table,
            $w,
            $order,
            $limit,
            $offset
        );

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o último registro de uma tabela
     *
     * @return array|false Dados do registro
     */
    public function last()
    {
        $sql = sprintf('SELECT * FROM %s ORDER BY %s DESC LIMIT 1', $this->table, $this->id);

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o total de registros de uma tabela contados pela chave primaria
     *
     * @return integer Numero de registro
     */
    public function total()
    {
        $sql = sprintf('SELECT COUNT(%s) as total FROM %s USE INDEX(PRIMARY)', $this->id, $this->table);

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data['total'];
    }

    /**
     * Inserir dados na tabela
     *
     * @param array $dados Dados a serem inseridos na tabelas conforme padrão
     * [chave=valor] com as chaves correspondendo as colunas da tabela
     *
     * @return integer Chave primaria do registro inserido na tabela
     */
    public function insert($dados)
    {
        $keys = array_keys($dados);

        $column = implode(',', $keys);

        $values = ':' . implode(',:', $keys);

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $column, $values);

        $stmt = $this->con->prepare($sql);

        foreach ($dados as $key => $value) {
            $stmt->bindParam(":{$key}", $dados[$key]);
        }

        if (!$stmt->execute()) {
            return false;
        }

        return $this->con->lastInsertId();
    }

    /**
     * Atualizar dados de um registro em uma tabela
     *
     * @param array $dados Array com dados a serem atualizados na tabelas
     * conforme padrão [chave=valor] com as chaves correspondendo as colunas da tabela
     * @param integer $id Chave primaria do registro na tabela
     */
    public function update($dados, $id)
    {
        $values = array_map(function ($key) {
            return sprintf('%s = :%s', $key, $key);
        }, array_keys($dados));

        $column = implode(', ', $values);

        $sql = sprintf('UPDATE %s SET %s WHERE %s = :id', $this->table, $column, $this->id);

        $stmt = $this->con->prepare($sql);

        foreach ($dados as $key => $value) {
            $stmt->bindParam(":{$key}", $dados[$key]);
        }

        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Atualizar dados de um registro numa tabela conforme condição passada
     *
     * @param array $dados Array com dados a serem atualizados na tabelas
     * conforme padrão [chave=valor] com as chaves correspondendo as colunas da tabela
     * @param string $where Condições da cláusula WHERE do comando SQL
     * @param string $limit Clausula LIMIT do comando SQL
     *
     * @return integer Número de linhas afetadas
     */
    public function updateWhere($dados, $where, $limit = '')
    {
        $values = [];

        foreach ($dados as $key => $value) {
            $values[] = "$key = :$key";
        }

        $column = implode(',', $values);

        $sql = sprintf('UPDATE %s SET %s WHERE %s %s', $this->table, $column, $where, $limit);

        $stmt = $this->con->prepare($sql);

        foreach ($dados as $key => $value) {
            $stmt->bindParam(":$key", $dados[$key]);
        }

        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Deleta um registro de uma tabela
     *
     * @param integer $id ID Chave primaria da tabela
     */
    public function delete($id)
    {
        $sql = sprintf('DELETE FROM %s WHERE %s = :id', $this->table, $this->id);

        $stmt = $this->con->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Deleta um ou mais registros de uma tabela conforme condição passada
     *
     * @param string $where Condições da cláusula WHERE do comando SQL
     * @param string $limit Clausula LIMIT do comando SQL. [Padrão: LIMIT 1]
     *
     * @return integer|false Número de linhas afetadas
     */
    public function deleteWhere($where, $limit = 'LIMIT 1')
    {
        if (empty($where)) {
            return false;
        }

        $sql = sprintf('DELETE FROM %s WHERE %s %s', $this->table, $where, $limit);

        $stmt = $this->con->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function debug($stmt)
    {
        var_dump($stmt->debugDumpParams());
    }
}
