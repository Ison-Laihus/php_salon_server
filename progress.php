<?php
$sessionid = $_COOKIE['PHPSESSID'];
session_id($sessionid);
session_start();

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='GET' ) {
    # connect sql
    require 'connection.php';

    if (isset($_SESSION['user']) ) {
        try {
            $sql = 'select * from progress';
            $db->beginTransaction();
            $stmt = $db->query($sql);
            $arr = [];
            foreach($stmt->fetchAll() as $row) {
                array_push($arr, $row);
            }
            $ret_json['success'] = true;
            $ret_json['data'] = $arr;
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
