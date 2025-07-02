<?php

namespace App\Utility;

use PDO;
use PDOException;
use Dotenv\Dotenv;


class MySQL
{
    private $conn;

    public function __construct()
    {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();

            $host = $_ENV['DB_HOST'];
            $dbname = $_ENV['DB_NAME'];
            $username = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASS'];
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Connection Failed: ' . $e->getMessage());
        }
    }

    public function query($sql)
    {
        try {
            return $this->conn->query($sql);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function create($table, $params = array())
    {
        try {
            $columns = implode('`, `', array_keys($params));
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $stmt = $this->conn->prepare("INSERT INTO `{$table}` (`$columns`) VALUES ($placeholders)");
            $stmt->execute(array_values($params));
            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function select($table, $conditions = array(), $orderBy = null, $limit = null)
    {
        try {
            $sql = "SELECT * FROM {$table}";

            if (!empty($conditions)) {
                $whereClauses = array();
                $params = array();

                foreach ($conditions as $key => $value) {
                    if (is_array($value)) {
                        $operator = strtoupper($value[0]);
                        if ($operator === 'IN') {
                            $placeholders = implode(',', array_fill(0, count($value[1]), '?'));
                            $whereClauses[] = "`$key` IN ({$placeholders})";
                            $params = array_merge($params, $value[1]);
                        } else {
                            // Handle other special operators like BETWEEN, >, <, etc
                            $whereClauses[] = "`$key` {$operator} ?";
                            $params[] = $value[1];
                        }
                    } else {
                        $whereClauses[] = "`$key` = ?";
                        $params[] = $value;
                    }
                }

                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }

            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }

            if ($limit) {
                $sql .= " LIMIT $limit";
            }

            $stmt = $this->conn->prepare($sql);

            if (!empty($conditions)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function __destruct()
    {
        $this->conn = null;
    }
}
