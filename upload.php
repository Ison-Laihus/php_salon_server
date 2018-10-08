<?php

$sessionid = $_COOKIE['PHPSESSID'];
session_id($sessionid);
session_start();

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    if (isset($_SESSION['user']) ) {
        # connect sql
        require 'connection.php';

        $item_id = $_POST['item_id'];
        $type = $_POST['type'];

        if ( isset($_FILES) ) {
            $image = $_FILES['image'];
            $arr = explode('.', $image['name']);
            $ext = $arr[count($arr)-1];
            $img_name = time() . '.' . $ext;
            $dest = './images/' . $img_name;
            move_uploaded_file($image['tmp_name'], $dest);
            try {
                $sql = 'insert into images values(0, ?, ?, ?)';
                $db->beginTransaction();
                $stmt = $db->prepare($sql);
                $stmt->execute(array($item_id, $type, $img_name));
                $db->commit();
                $ret_json['success'] = true;
                $ret_json['data'] = 'add picture success';
            } catch(PDOException $e) {
                $db->rollback();
                $ret_json['err'] = $e->getMessage();
            }
        } else {
            $ret_json['err'] = 'no upload file';
        }
    }
} else {
    $ret_json['err'] = 'request method error';
}
echo json_encode($ret_json);
