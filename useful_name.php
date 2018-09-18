<?php

$ret_json = array('success' => false);
if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    var_dump($_POST);
    if ( isset($_POST['user_name']) ) {
        # connect sql
        require 'connection.php';

        $sql = 'select * from user_account where user_name = " . $_POST['user_name'] . '"';
        $rows = $db->query($sql);
        if ($rows == 1) {
            $ret_json['msg'] = 'the username is useful';
            $ret_json['success'] = true;
        } else {
            $ret_json['err'] = 'this username has been registered';
        }
    } else {
        $ret_json['err'] = 'post params error';
    }

} else {
    $ret_json['err'] = 'request method error';
}
echo json_encode($ret_json);
