<?php

define('DB_HOST','127.0.0.1');
define('DB_PORT','8889');
define('DB_NAME','tuelo');
define('DB_USER','root');
define('DB_PASS','root');
define('DB_CHARSET','utf8mb4');

function getPDO(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[Tuelo DB Error] ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }
    return $pdo;
}

function queryDB(string $sql, array $params = []): array {
    $statement = getPDO()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function singleQueryDB(string $sql,array $params = []): array {
    $statement = getPDO()->prepare($sql);
    $statement->execute($params);
    return $statement->fetch();
}

function insertIntoDB(string $sql,array $params = []): int {
    $pdo = getPDO();
    $pdo->prepare($sql)->execute($params);
    return (int) $pdo->lastInsertId();
}

function updateDB(string $sql, array $params = []): int {
    $statement = getPDO()->prepare($sql);
    $statement->execute($params);
    return $statement->rowCount();
}