<?php

require 'config.php';

// $mysql = mysql_connect($config['user'], $config['pass'], $config['dbname']);

$dns = $config['dbms'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'];

try{
    # 长连接
    $db = new Pdo($dns, $config['user'], $config['pass'], array(PDO::ATTR_PERSISTENT => true));
    echo 'connect successful' . PHP_EOL;
} catch(PDOException $e) {
    die('ERROR: ' . $e->getMessage() . '\n');
}


return $db;
