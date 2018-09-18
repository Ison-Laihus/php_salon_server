<?php
session_start();

$ret_json = array('success' => false);
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : null;
if ( $method && ( $method=='GET' || $method=='POST') ) {

    if (isset($_SESSION['user']) ) {
        # connect sql
        require 'connection.php';

        if ($method=='GET') {
            try {
                $sql = 'select id, title, image_url, date, type, page_views, thumb_number from news';
                $db->beginTransaction();
                $stmt = $db->prepare($sql);
                $stmt->execute()
                $arr = [];
                foreach($rows->fetchAll() as $row) {
                    array_push($arr, $row);
                }
                $ret_json['success'] = true;
                $ret_json['data'] = $arr;
            } catch(PDOException $e) {
                $db->rollback();
                $ret_json['err'] = $e->getMessage();
            }
        } elseif ($method=='POST') {
            $post = isset($_POST) ? $_POST : null;
            if ( $post ) {
                $title = $post['title'];
                $content = $post['content'];
                $date = $post['date'];
                $type = $post['type'];
                $sql = 'insert into news values(0, ?, ?, ?, ?, 0, 0)';
                try {
                    $db->beginTransaction();
                    $stmt = $db->prepare($sql);
                    $ret = $stmt->excute(array($title, $content, $date, $type));
                    if ( $ret ) {
                        $ret_json['success'] = true;
                        $ret_json['msg'] = 'add news successful';
                        $ret_json['news_id'] = $db->lastInsertId();
                    } else {
                        $ret_json['err'] = 'add news failed';
                    }
                } catch(PDOException $e) {
                    $db->rollback();
                    $ret_json['err'] = $e->getMessage();
                }

            } else {
                $ret_json['err'] = 'you should set header application/x-www-form-urlencoded';
            }
        }
    } else {
        $ret_json['err'] = 'please login first';
    }
} else {
    $ret_json['err'] = 'request method error';
}
echo json_encode($ret_json);
