<?php

require 'config.php';

$dns = $config['dbms'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=utf8mb4';

try{
    # 长连接
    $db = new PDO($dns, $config['user'], $config['pass'], array(PDO::ATTR_PERSISTENT => true));
} catch(PDOException $e) {
    die('ERROR: ' . $e->getMessage() . '\n');
}


return $db;
