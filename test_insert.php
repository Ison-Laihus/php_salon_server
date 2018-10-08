<?php

require 'connection.php';

$ret_json = array('success' => false);

$sql = 'insert into news values(0, ?, ?, "aaa", ?, 0, 0, 0)';
$date = date('Y-m-d H:i:s');
$title = '111';
$content = '2222';
try {
    $db->beginTransaction();
    $stmt = $db->prepare($sql);
    $ret = $stmt->execute(array($title, $content, $date));
    $db->commit();
    if ( $ret ) {
        $ret_json['success'] = true;
        $ret_json['msg'] = 'add news successful';
        $ret_json['item_id'] = $db->lastInsertId();
    } else {
        $ret_json['err'] = 'add news failed';
    }
} catch(PDOException $e) {
    $db->rollback();
    $ret_json['err'] = $e->getMessage();
}
var_dump($ret_json);

$user_id = 1;
$project_name = '11';
$project_describe = '11';
// $date = $post['date'];
$date = date('Y-m-d H:i:s');
$group_number = 0;
$group_number_now = 0;
$group_describe = '11';
$progress_id = 0;
$sql = 'insert into projects values(0, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)';
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $db->beginTransaction();
    $stmt = $db->prepare($sql);
    $ret = $stmt->execute(array($user_id, $project_name, $project_describe, $date, $group_number, $group_number_now, $group_describe, $progress_id));
    $db->commit();
    if ($ret) {
        $ret_json['success'] = true;
        $ret_json['msg'] = 'add project successful';
        $stmt = $db->query("SELECT LAST_INSERT_ID()");
        $lastId = $stmt->fetch(PDO::FETCH_NUM);
        $lastId = $lastId[0];
        $ret_json['item_id'] = $lastId;
    } else {
        $ret_json['err'] = 'add project failed';
    }
} catch(PDOException $e) {
    $db->rollback();
    $ret_json['err'] = $e->getMessage();
}
var_dump($ret_json);
 ?>
