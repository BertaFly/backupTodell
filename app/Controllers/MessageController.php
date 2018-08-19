<?php

namespace App\Controllers;
use App\Models\Model;
use App\Mail\SendMail;

class MessageController extends Controller
{
	public function returnInitialHistory($request, $response)
	{
		$uId = $request->getParam('userId');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('chat')->where('sender', '=', $uId)->orWhere('receiver', '=', $uId)->orderBy('whenSend', 'ASC');
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		$uniqUser = array();
		$conversation = array();

		foreach ($fromDb as $key => $value) {
			if ($value['sender'] != $uId && in_array($value['sender'], $uniqUser) === false)
				$uniqUser[$key] = $value['sender'];
			else if ($value['receiver'] != $uId && in_array($value['receiver'], $uniqUser) === false)
				$uniqUser[$key] = $value['receiver'];
		}
		$uniqUser = array_values($uniqUser);

		foreach ($uniqUser as $i => $v) {
			$conversation[$i]['withWho'] = $v;

			$sql = $db->select()->from('profiles')->join('users', 'users.userId', '=', 'profiles.user')->where('user', '=', $v);
			$exec = $sql->execute();
			$info = $exec->fetch();

			$conversation[$i]['ava'] = $info['profilePic'];
			$conversation[$i]['name'] = $info['fname'] . ' ' . $info['lname'];

			$countIn = 0;
			$countOut = 0;

			foreach ($fromDb as $msg) {
				if ($v == $msg['sender']){
					$conversation[$i]['in'][$countIn] = $msg['msg'] . ' ' . $msg['whenSend'];
					$countIn++;
				} else if ($v == $msg['receiver']){
					$conversation[$i]['out'][$countOut] = $msg['msg'] . ' ' . $msg['whenSend'];
					$countOut++;
				}		
			}
		}
		$result->data = $conversation;
		$result->check = "vatrushka";

		return json_encode($result);

	}
}