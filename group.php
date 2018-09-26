<?php
$sessionid = $_COOKIE['PHPSESSID'];
session_id($sessionid);
session_start();

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    # connect sql
    require 'connection.php';

    if (isset($_SESSION['user']) ) {
        try {
            $post = isset($_POST) ? $_POST : null;
            if ($post) {
                $project_id = $post['project_id'];
                $sql = 'select group.id, user_account.user_name, user_account.real_name, role.role from (`group` left join user_account on group.user_id = user_account.id) left join role on group.role_id = role.id where group.project_id = ?';
                $db->beginTransaction();
                $stmt = $db->prepare($sql);
                $stmt->execute(array($project_id))
                $arr = [];
                foreach($rows->fetchAll() as $row) {
                    array_push($arr, $row);
                }
                $ret_json['success'] = true;
                $ret_json['data'] = $arr;
            } else {
                $ret_json['err'] = 'you should set header application/x-www-form-urlencoded';
            }

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
