<?php

namespace FVCode\Orm;

class Conn
{
    private static $instance;

    private static $instance_transaction;

    /**
     * @throws \Exception
     */
    public static function getConn()
    {
        try {
            if (
                !defined('ORM_DATABASE_HOST')
                || !defined('ORM_DATABASE_NAME')
                || !defined('ORM_DATABASE_USER')
                || !defined('ORM_DATABASE_PASSWORD')
            ) {
                throw new \Exception('Connection data not defined! set values for [ORM_DATABASE_] const');
            }

            if (!defined('ORM_DATABASE_CHARSET')) {
                define('ORM_DATABASE_CHARSET', 'utf8');
            }

            $dns = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                ORM_DATABASE_HOST,
                ORM_DATABASE_NAME,
                ORM_DATABASE_CHARSET
            );

            self::$instance = new \PDO($dns, ORM_DATABASE_USER, ORM_DATABASE_PASSWORD);
            self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            self::$instance->setAttribute(\PDO::ATTR_TIMEOUT, 5);

            return self::$instance;
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public static function prepare($sql)
    {
        return self::getConn()->prepare($sql);
    }

    /**
     * @throws \Exception
     */
    public static function lastInsertId()
    {
        return self::getConn()->lastInsertId();
    }

    /**
     * @throws \Exception
     */
    public static function query($sql)
    {
        $stmt = self::getConn()->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * @throws \Exception
     */
    public static function beginTransaction()
    {
        self::$instance_transaction = self::getConn();
        self::$instance_transaction->beginTransaction();
    }

    public static function queryTransaction($sql)
    {
        $stmt = self::$instance_transaction->prepare($sql);
        $stmt->execute();
    }

    public static function commit()
    {
        self::$instance_transaction->commit();
    }

    public static function rollBack()
    {
        self::$instance_transaction->rollBack();
    }
}
