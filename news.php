<?php
$sessionid = $_COOKIE['PHPSESSID'];
session_id($sessionid);
session_start();

$ret_json = array('success' => false);
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : null;
if ( $method && ( $method=='GET' || $method=='POST') ) {

    if (isset($_SESSION['user']) ) {
        # connect sql
        require 'connection.php';

        if ($method=='GET') {
            try {
                $sql = 'select news.id as id, title, images.url as image_url, date, page_views, thumb_number from (news left join images on news.id = images.item_id)';
                $db->beginTransaction();
                $stmt = $db->query($sql);
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
        } elseif ($method=='POST') {
            $post = isset($_POST) ? $_POST : null;
            if ( $post ) {
                $title = $post['title'];
                $content = $post['content'];
                $sql = 'insert into news values(0, ?, ?, ?, 0, 0, 0)';
                $date = date('Y-m-d H:i:s');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                try {
                    $db->beginTransaction();
                    $stmt = $db->prepare($sql);
                    $ret = $stmt->execute(array($title, $content, $date));
                    $db->commit();
                    if ( $ret ) {
                        $ret_json['success'] = true;
                        $ret_json['msg'] = 'add news successful';
                        # get last insert id
                        $stmt = $db->query("SELECT LAST_INSERT_ID()");
                        $lastId = $stmt->fetch(PDO::FETCH_NUM);
                        $lastId = $lastId[0];
                        $ret_json['item_id'] = $lastId;
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
