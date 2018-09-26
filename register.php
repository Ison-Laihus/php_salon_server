<?php
error_reporting(-1);
ini_set('display_errors', 1);

$ret_json = array('success' => false);

if ( isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD'])=='POST' ) {
    $post = $_POST;
    if ( isset($post['user_name']) && isset($post['password']) && isset($post['real_name']) && isset($post['school_id']) && isset($post['profession_id']) && isset($post['phone']) && isset($post['email']) && isset($post['characteristic']) ) {
        # connect sql
        require 'connection.php';

        $characteristic_arr = explode(',', $post['characteristic']);
        $password = md5($post['password'] . $config['salt']);

        try {
            $sql = 'insert into user_account values (0, ?, ?, ?, ?, 0, ?, ?, ?)';
            $db->beginTransaction();
            $stmt = $db->prepare($sql);
            $ret = $stmt->execute(array($post['user_name'], $post['real_name'], $password, $post['school_id'], $post['profession_id'], $post['phone'], $post['email']));
            $db->commit();

            if ($ret) {
                $id = $db->lastInsertId();
                $sql2 = 'insert into user_characteristic values(0, ' . $id . ', ?)';
                for ($i=1; $i<count($characteristic_arr); $i++) {
                    $sql2 .= ',(0, ' . $id . ', ?)';
                }

                $db->beginTransaction();
                $stmt2 = $db->prepare($sql2);
                $stmt2->execute($characteristic_arr);
                $db->commit();

                $ret_json['msg'] = 'register successful';
                $ret_json['success'] = true;
            } else {
                $ret_json['msg'] = 'insert into user_account error';
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
