<?php

$sessionid = $_COOKIE['PHPSESSID'];
session_id($sessionid);
session_start();

$ret_json = array('success' => false);
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : null;
if ( $method && $method=='POST' ) {
    if (isset($_SESSION['user']) ) {
        # connect sql
        require 'connection.php';

        $project_id = $_POST['project_id'];
        try {
            $sql = 'update projects set thumb_number = thumb_number + 1 where id = ?';
            $db->beginTransaction();
            $stmt = $db->prepare($sql);
            $stmt->execute(array($project_id));
            $db->commit();
            $ret_json['success'] = true;
            $ret_json['data'] = 'update success';
        } catch(PDOException $e) {
            $db->rollback();
            $ret_json['err'] = $e->getMessage();
        }
    } else {
        $ret_json['err'] = 'please login first';
    }
} else {
    $ret_json['err'] = 'request method error';
}

echo json_encode($ret_json);
