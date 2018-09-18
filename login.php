<?php
session_start();

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    if ( isset($_POST['user_name']) && isset($_POST['password']) ) {
        # connect sql
        require 'connection.php';

        $sql = 'select * from user_account where user_name = "' . $_POST['user_name'] . '"';
        $rows = $db->query($sql);
        if ($rows == 1) {
            $sql = 'select * from user_account where user_name = "' . $_POST['user_name'] . '" and password = "' . $_POST['password'] . '"';
            $rows = $db->query($sql);
            if ($rows == 1) {
                $ret_json['msg'] = 'login successful';
                $ret_json['success'] = true;
                $_SESSION['user'] = $_POST['user_name'];
            } else {
                $ret_json['err'] = 'password error';
            }
        } else {
            $ret_json['err'] = 'no this user';
        }
    } else {
        $ret_json['err'] = 'post params error';
    }

} else {
    $ret_json['err'] = 'request method error';
}
echo json_encode($ret_json);
