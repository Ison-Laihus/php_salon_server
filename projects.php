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
                $sql = 'select project_name, date, group_number, group_number_now, progress.progress as progress_name, user_account.real_name as user from (projects left join progress on projects.progress_id = progress.id) left join user_account on user_account.id = projects.user_id order by date desc, group_number_now asc';
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
                $user_id = $_SESSION['id'];
                $project_name = $post['project_name'];
                $project_describe = $post['project_describe'];
                // $date = $post['date'];
                $date = time();
                $group_number = $post['group_number'];
                $group_number_now = $post['group_number_now'];
                $group_describe = $post['group_describe'];
                $process_id = $post['process_id'];
                $sql = 'insert into projects values(0, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)';
                try {
                    $db->beginTransaction();
                    $stmt = $db->prepare($sql);
                    $ret = $stmt->excute(array($user_id, $project_name, $project_describe, $date, $group_number, $group_number_now, $group_describe, $process_id));
                    $db->commit();
                    if ($ret) {
                        $ret_json['success'] = true;
                        $ret_json['msg'] = 'add project successful';
                        $ret_json['project_id'] = $db->lastInsertId();
                    } else {
                        $ret_json['err'] = 'add project failed';
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
