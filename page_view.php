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

        $id = $_POST['id'];
        $type = $_POST['type'];
        try {
            if ($type=='projects') {
                $sql = 'update projects set page_views = page_views + 1 where id = ?';
            } elseif ($type=='news') {
                $sql = 'update news set page_views = page_views + 1 where id = ?';
            }

            $db->beginTransaction();
            $stmt = $db->prepare($sql);
            $stmt->execute(array($id));
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
