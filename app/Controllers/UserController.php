<?php

namespace App\Controllers;
use App\Models\Model;
use App\Mail\SendMail;

class UserController extends Controller
{
	public function postCheckProfileIsFull($request, $response)
	{
		$userId = $request->getParam('userId');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetch();
		if (isset($fromDb['isFull']))
		{
			if ($fromDb['isFull'] === 0)
				return json_encode(false);
			else
				return json_encode(true);				
		}
		else
		{
			$result->error = 'You did not confirm your email';
			return json_encode($result);	
		}
	}

	public function postGetAllInfo($request, $response)
	{
		$userId = $request->getParam('userId');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('users')->join('profiles', 'users.userId', '=', 'profiles.user')->where('userId', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetch();
		$result->userData = $fromDb;

		$sql = $db->select()->from('photos')->where('userNbr', '=', $userId);
		$sql = $sql->orderBy('whenAdd', 'DESC');
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		$photos = array();
		foreach ($fromDb as $key => $photo) {
			array_push($photos, $photo['src']);
		}
		$result->userPhoto = $photos;
		$result->whoLikesUser = $this->postWhoLikesMe($userId);

		return json_encode($result);
	}

	public function postWhoLikesMe($userId)
	{
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('likes')->join('users', 'users.userId', '=', 'likes.who')->join('profiles', 'users.userId', '=', 'profiles.user')->where('target', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		$likesMeProfiles;
		foreach ($fromDb as $key => $profile) {
			$txt = $profile['bio'];
			if (strlen($txt) > 200)
			{
				$parts = preg_split('/([\s\n\r]+)/', $txt, null, PREG_SPLIT_DELIM_CAPTURE);
				$parts_count = count($parts);
				$length = 0;
				$last_part = 0;
				for (; $last_part < $parts_count; ++$last_part)
				{
					$length += strlen($parts[$last_part]);
					if ($length > 200)
						break;
				}
				$txt = implode(array_slice($parts, 0, $last_part));
			}
			$likesMeProfiles[$key] = array('profilePic' => $profile['profilePic'], 'firstName' => $profile['fname'], 'lastName' => $profile['lname'], 'about' => $txt);			
		}
		return $likesMeProfiles;
	}

	public function postRecordInfo($request, $response)
	{
		return json_encode(true);

	}

	public function postForAbout($request, $response)
	{
		$userId = $request->getParam('uId');
		$db = new Model;
		$db = $db->connect();
		$selectStatement = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec = $selectStatement->execute();
		$fromDb = $exec->fetch();
		$res->tags = $fromDb['tags'];
		$res->bio = $fromDb['bio'];
		return json_encode($res);
	}

	public function postRecordAbout($request, $response)
	{
		$userId = $request->getParam('uId');
		$userTags = $request->getParam('tags');
		$userBio = $request->getParam('bio');
		$db = new Model;
		$db = $db->connect();
		$selectStatement = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec = $selectStatement->execute();
		$fromDb = $exec->fetch();
		$userTagsInit = $fromDb['tags'];

		$sendTags = explode(' ', $userTags);
		foreach ($sendTags as $key => $toCheck) {
			if (strstr($userTagsInit, $toCheck) === false){
				$tagMayAdd == "" ? $tagMayAdd = $toCheck : $tagMayAdd = $tagMayAdd . ' ' . $toCheck;
			}
		}
		$userTags = $userTagsInit . ' ' . $tagMayAdd;
		$userBio === "" ? $userBio = $fromDb['bio'] : $userBio = $userBio;

		$updateStatement = $db->update(array('bio' => $userBio, 'tags' => $userTags))
								   ->table('profiles')
								   ->where('user', '=', $userId);
		$exec = $updateStatement->execute();

		$selectStatement = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec = $selectStatement->execute();
		$fromDb = $exec->fetch();
		$res->end = true;
		if ($fromDb['bio'] === "")
			$res->bio = "";
		else
			$res->bio = $fromDb['bio'];
		if ($fromDb['tags'] === "")
			$res->tags = "";
		else
			$res->tags = $fromDb['tags'];
		return json_encode($res);
	}

	public function postNewPhoto($request, $response)
	{
		$userId = $request->getParam('userId');
		//check if no more 5 photo
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('photos')->where('userNbr', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		if (count($fromDb) < 5)
		{
			$photo = $request->getParam('file');
			if (strstr($photo, 'data:image/jpeg;base64,'))
				$img = str_replace('data:image/jpeg;base64,', '', $photo);
			else
				$img = str_replace('data:image/png;base64,', '', $photo);
			$img = str_replace(' ', '+', $img);
			$img = base64_decode($img);
			//for name file
			$str = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789";
			$str = str_shuffle($str);
			$picName = substr($str, 0, 10);
			$fileName = ROOT.'/userPhoto/'.$picName.'.png';
			$fileNameForDb = 'http://localhost:8080/userPhoto/'.$picName.'.png';
			//record in file
			file_put_contents($fileName, $img);
			//insert in db new user photo
			$sql = $db->insert(array('userNbr', 'src'))
						   ->into('photos')
						   ->values(array($userId, $fileNameForDb));
			$sql->execute(false);			
			//for returning new arr pics on front
			$sql = $db->select()->from('photos')->where('userNbr', '=', $userId);
			$sql = $sql->orderBy('whenAdd', 'DESC');
			$exec = $sql->execute();
			$fromDb = $exec->fetchAll();
			$photos = array();
			foreach ($fromDb as $key => $photo) {
				array_push($photos, $photo['src']);
			}
			$result->userPhoto = $photos;
			return json_encode($result);
		}
			// return json_encode($result);

		return json_encode(false);
	}

	public function postDelPhoto($request, $response)
	{
		$target = $request->getParam('pic');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->delete()->from('photos')->where('src', '=', $target);
		$exec = $sql->execute();
		//check if avatar and erise this cell in table
		$sql = $db->select()->from('profiles')->where('profilePic', '=', $target);
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		if (count($fromDb))
		{
			$sql = $db->delete()->from('profiles')->where('profilePic', '=', $target);
			$exec = $sql->execute();
		}
		//return new array pics to re render on front
		$sql = $db->select()->from('photos')->where('userNbr', '=', $request->getParam('userId'));
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		$photos = array();
		foreach ($fromDb as $key => $photo) {
			array_push($photos, $photo['src']);
		}
		$result->userPhoto = $photos;
		return json_encode($result);
	}

	public function postSetAvatar($request, $response)
	{
		$ava = $request->getParam('ava');
		$userId = $request->getParam('userId');
		$db = new Model;
		$db = $db->connect();
		$updateStatement = $db->update(array('profilePic' => $ava))
								   ->table('profiles')
								   ->where('user', '=', $userId);
		$exec = $updateStatement->execute();
		return json_encode(true);
	}
	public function postDellTag($request, $response)
	{
		$userId = $request->getParam('uId');
		$tagToDel = $request->getParam('what');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetch();
		$allTags = explode(' ', $fromDb['tags']);
		if (($key = array_search($tagToDel, $allTags)) !== false) {
			unset($allTags[$key]);
		}
		$allTagsFinal = implode(' ', $allTags);
		$updateStatement = $db->update(array('tags' => $allTagsFinal))
								   ->table('profiles')
								   ->where('user', '=', $userId);
		$exec = $updateStatement->execute();
		$res->tags = $allTagsFinal;
		return json_encode($res);
	}
}