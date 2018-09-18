<?php

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    $post = $_POST;
    if ( isset($post['user_name']) && isset($post['password']) && isset($post['real_name']) && isset($post['school_id']) && isset($post['profession_id']) && isset($post['phone']) && isset($post['email']) && isset($post['characteristic']) ) {
        # connect sql
        require 'connection.php';

        $salt = 'salon';
        // $sql = 'insert into user_account values(0, "' . $post['user_name'] . '", "' . $post['real_name'] . '", "'
        //         . md5($post['password'] . $salt) . '", ' . $post['school_id'] . ', 0, ' . $post['profession_id'] . ', "'
        //         . $post['phone'] . '", "' . $post['email'] . '")';
        $sql = 'insert into user_account values(0, "?", "?", "?", ?, 0, ?, "?", "?")';
        $stmt = $db->prepare($sql);
        try {
            $db->beginTransaction();
            $ret = $stmt->execute(array($post['user_name'], $post['real_name'], md5($post['password'] . $salt), $post['school_id'], $post['profession_id'], $post['phone'], $post['email']));
            if ($ret) {
                $id = $db->lastInsertId();
                $sql2 = 'insert into user_characteristic values(0, ' . $id . ', ?)';
                for ($i=1; $i<count($post['characteristic']); $i++) {
                    $sql2 .= ',(0, ' . $id . ', ?)';
                }
                $sql2 .= ';'
                $stmt2 = $db->prepare($sql2);
                $ret2 = $stmt2->excute($post['characteristic']);
                if ($ret2) {
                    $ret_json['msg'] = 'register successful';
                    $ret_json['success'] = true;
                }
            }
        } catch(PDOException $e) {
            $db->rollback();
            $ret_json['err'] = $e->getMessage();
        }
    } else {
        $ret_json['err'] = 'post params error';
    }

} else {
    $ret_json['err'] = 'request method error';
}
echo json_encode($ret_json);
