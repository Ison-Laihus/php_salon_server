<?php
session_start();

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    if ( isset($_POST['username']) && isset($_POST['password']) ) {
        # connect sql
        require 'connection.php';

        $sql = 'select * from user_account where user_name = "' . $_POST['username'] . '"';
        $rows = $db->query($sql)->rowCount();
        if ($rows == 1) {
            $passwrod = md5($_POST['password'] . $config['salt']);
            $sql = 'select * from user_account where user_name = "' . $_POST['username'] . '" and password = "' . $passwrod . '"';
            $stmt = $db->query($sql);
            $rows = $stmt->rowCount();
            if ($rows == 1) {
                $ret_json['msg'] = 'login successful';
                $ret_json['success'] = true;
                $_SESSION['user'] = $_POST['username'];
                $_SESSION['id'] = $stmt->fetchAll()[0]['id'];
                setcookie('session_id', session_id(), time()+$config['expire']);
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
