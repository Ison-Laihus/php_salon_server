<?php

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='GET' ) {
    # connect sql
    require 'connection.php';

    $sql = 'select * from school';
    $rows = $db->query($sql);
    if ($rows >= 1) {
        foreach()
    } else {
        $ret_json['err'] = 'no this user';
    }

} else {
    $ret_json['err'] = 'request method error';
}
echo json_encode($ret_json);
