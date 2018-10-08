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

        $news_id = $_POST['news_id'];
        try {
            $sql = 'select news.id as id, title, content, images.url as image_url, date, page_views, thumb_number from (news left join images on news.id = images.item_id) where news.id = ?';
            $db->beginTransaction();
            $stmt = $db->prepare($sql);
            $stmt->execute(array($news_id));
            $db->commit();
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
