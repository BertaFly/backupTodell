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
		$result->whoLikesUser = $this->whoLikesMe($userId);
		return json_encode($result);
	}

	public function postGetAllPhoto($request, $response)
	{
		$userId = $request->getParam('userId');
		$db = new Model;
		$db = $db->connect();
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

	public function postWhoViewMe($request, $response)
	{
		$userId = $request->getParam('uId');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('views')->join('users', 'users.userId', '=', 'views.whoView')->join('profiles', 'views.whoView', '=', 'profiles.user')->where('target', '=', $userId);
		$exec = $sql->execute();
		$forSearch = $exec->fetchAll();

		$sql2 = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec2 = $sql2->execute();
		$myData = $exec2->fetch();

		$sql3 = $db->select()->from('blocks')->where('whoBlock', '=', $userId);
		$exec3 = $sql3->execute();
		$myBlocks = $exec3->fetchAll();

		$viewMe;
		$i = 0;
		foreach ($forSearch as $key => $value) {
			if ($myData['sexPref'] == 'homo')
			{
				if (($myData['sex'] == $value['sex'] && $value['sexPref'] == 'homo') || $value['sexPref'] == 'bi')
				{
					foreach ($myBlocks as $block) {
						if ($block['target'] !== $value['userId'])
						{
							$viewMe[$i] = array('uId' => $value['userId'], 'login' => $value['login'], 'fname' => $value['fname'], 'lname' => $value['lname'], 'age' => $value['age'], 'sex' => $value['sex'], 'sexPref' => $value['sexPref'], 'fameRate' => $value['fameRate'], 'stars' => $value['stars'], 'profilePic' => $value['profilePic'], 'isOnline' => $value['isOnline'], 'lastSeen' => $value['last_seen']);
							$i++;
						}
					}
				}
			}
			else if ($myData['sexPref'] == 'hetero')
			{
				if (($myData['sex'] != $value['sex'] && $value['sexPref'] == 'hetero') || $value['sexPref'] == 'bi')
				{
					foreach ($myBlocks as $block){
						if ($block['target'] !== $value['userId'])
						{
							$viewMe[$i] = array('uId' => $value['userId'], 'login' => $value['login'], 'fname' => $value['fname'], 'lname' => $value['lname'], 'age' => $value['age'], 'sex' => $value['sex'], 'sexPref' => $value['sexPref'], 'fameRate' => $value['fameRate'], 'stars' => $value['stars'], 'profilePic' => $value['profilePic'], 'isOnline' => $value['isOnline'], 'lastSeen' => $value['last_seen']);
							$i++;
						}
					}
				}
			}
			else
			{
				foreach ($myBlocks as $block){
					if ($block['target'] !== $value['userId'])
					{
						$viewMe[$i] = array('uId' => $value['userId'], 'login' => $value['login'], 'fname' => $value['fname'], 'lname' => $value['lname'], 'age' => $value['age'], 'sex' => $value['sex'], 'sexPref' => $value['sexPref'], 'fameRate' => $value['fameRate'], 'stars' => $value['stars'], 'profilePic' => $value['profilePic'], 'isOnline' => $value['isOnline'], 'lastSeen' => $value['last_seen']);
							$i++;
					}
				}
			}
		}
		$res->views = $viewMe;
		return json_encode($res);
	}

	public function whoLikesMe($userId)
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
		$userId = $request->getParam('uId');
		$login = $request->getParam('login');
		$pass = $request->getParam('pass');
		$fname = ucfirst(strtolower(($request->getParam('fname'))));
		$lname = ucfirst(strtolower(($request->getParam('lname'))));
		$email = $request->getParam('email');
		$age = $request->getParam('age');
		$sex = $request->getParam('sex');
		$sexPref = $request->getParam('sexPref');
		
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('users')->join('profiles', 'users.userId', '=', 'profiles.user')->where('userId', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetch();

		$res->allResFromDb = $fromDb['login'];

		if ($login != '')
		{
			$login = htmlspecialchars($login);
			$wrongLogin = (strlen($login) <= 4 || strlen($login) >= 120);
			if ($wrongLogin)
			{
				$res->eLogin = 'New login should be longer than 4 chars and shorter than 120';
				$login = $fromDb['login'];
			}
		} else {
			$login = $fromDb['login'];
		}
		// $res->login = $login;

		if ($pass != '')
		{
			$pass = htmlspecialchars($pass);
			$wrongPass = (strlen($pass) <= 6 || strlen($pass) >= 120 || preg_match("(.*[A-Z].*)", $request->getParam('pass')) == false || password_verify($request->getParam('pass'), $fromDb['password']));
			if ($pass)
			{
				$res->ePass = 'New password should be longer than 6 chars and shorter than 120, have at least 1 uppercase letter and differ from old one';
				$pass = $fromDb['password'];
			}
			else
				$pass = password_hash($pass, PASSWORD_DEFAULT);
		} else {
			$pass = $fromDb['password'];
		}

		// $res->pass = $pass;

		if ($email != '')
		{
			if (strlen($email) >= 120)
			{
				$res->eEmail = 'Email should not be empty or longer than 120 chars';
				$email = $fromDb['email'];
			}
		} else {
			$email = $fromDb['email'];
		}

		if ($fname != '') {
			$wrongFname = (strlen($fname) <= 1 || strlen($fname) >= 120 || !ctype_alpha($fname));
			if ($wrongFname)
			{
				$res->eFname = 'First name should consists at least 2 chars, be less than 120 and can contain only english letters';
				$fname = $fromDb['fname'];
			}
		} else {
			$fname = $fromDb['fname'];
		}
		// $res->fname = $fname;
		
		if ($lname != '') {
			$wrongLname = (strlen($lname) <= 1 || strlen($lname) >= 120 || !ctype_alpha($lname));
			if ($wrongLname)
			{
				$res->eLname = 'Last name should consists at least 2 chars, be less than 120 and can contain only english letters';
				$fname = $fromDb['lname'];
			}
		} else {
			$lname = $fromDb['lname'];
		}
		// $res->lname = $lname;

		if ($age != '')
		{
			$birthDate = explode("-", $age);
			$age = (date("md", date("U", mktime(0, 0, 0, $birthDate[2], $birthDate[1], $birthDate[0]))) > date("md")
				? ((date("Y") - $birthDate[0]) - 1)
				: (date("Y") - $birthDate[0]));
			if ($age < 18 || $age > 120)
				$res->eAge = "Oups, you can not be younger than 18 or so old";
		} else {
			$age = $fromDb['age'];
		}
		// $res->age = $age;

		if ($sex == '')
			$sex = $fromDb['sex'];
		// $res->sex = $sex;

		if ($sexPref == '')
			$sexPref = $fromDb['sexPref'];
		// $res->sexPref = $sexPref;

		$updateStatement = $db->update(array('login' => $login, 'password' => $pass, 'email' => $email, 'fname' => $fname, 'lname' => $lname))
						   ->table('users')
						   ->where('userId', '=', $userId);
		$updateStatement->execute();
		$updateStatement = $db->update(array('age' => $age, 'sex' => $sex, 'sexPref' => $sexPref))->table('profiles')->where('user', '=', $userId);
		$updateStatement->execute();
		$sql = $db->select()->from('users')->join('profiles', 'users.userId', '=', 'profiles.user')->where('userId', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetch();
		foreach ($fromDb as $key => $value) {
			if ($value == '' && $key != 'showMe' && $key != 'isOnline')
				$isFull = 0;
			else
				$isFull = 1;
		}
		$updateStatement = $db->update(array('isFull' => $isFull))
						   ->table('profiles')
						   ->where('user', '=', $userId);
		$updateStatement->execute();
		$res->profileIsFull = $isFull;
		$res->newData = $fromDb;
		return json_encode($res);
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
		$colTagDb = explode(' ', $fromDb['tags']);
		$sendTags = explode(' ', $userTags);
		$colForRecord = 50 - count($colTagDb);
		if(count($colTagDb) < 50)
		{
			foreach ($sendTags as $key => $toCheck) {
				if ($key > $colForRecord)
					break ;
				$toCheck = preg_replace('/[^\w#& ]/', '', $toCheck);
				if (strstr($userTagsInit, $toCheck) === false && strstr($tagMayAdd, $toCheck) === false){
					$tagMayAdd == "" ? $tagMayAdd = $toCheck : $tagMayAdd = $tagMayAdd . ' ' . $toCheck;
				}
			}
			$tagMayAdd == '' ? $userTags = $userTagsInit : $userTags = $userTagsInit . ' ' . $tagMayAdd;
		}
		else
		{
			$userTags = $userTagsInit;
			$res->err = "Would you be so kind be less specific. You may add up to 50 tags";
		}
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
		$res->src = $ava;
		return json_encode($res);
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

	public function postReturnCoord($request, $response)
	{
		$userId = $request->getParam('uId');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('profiles')->where('user', '=', $userId);
		$exec = $sql->execute();
		$fromDb = $exec->fetch();
		$res->latStart = $fromDb['latitude'];
		$res->lngStart = $fromDb['longetude'];
		$res->show = boolval($fromDb['showMe']);
		return json_encode($res);
	}

	public function postLocation($request, $response)
	{
		$userId = $request->getParam('uId');
		$long1 = $request->getParam('longAllow');
		$long2 = $request->getParam('longDen');
		$lat1 = $request->getParam('latAllow');
		$lat2 = $request->getParam('latDen');
		$show = $request->getParam('showMe');
		$db = new Model;
		$db = $db->connect();
		if ($long1 && $lat1)
		{
			$updateStatement = $db->update(array('longetude' => floatval($long1), 'latitude' => floatval($lat1)))->table('profiles')->where('user', '=', $userId);
			$exec = $updateStatement->execute();
			$res->latAllow = $lat1;
			$res->lngAllow = $long1;
		}
		if ($long2 && $lat2)
		{
			$selectStatement = $db->select()->from('profiles')->where('user', '=', $userId);
			$exec = $selectStatement->execute();
			$fromDb = $exec->fetch();
			if ($fromDb['longetude'] == null || $fromDb['longetude'] == 0)
			{
				$updateStatement = $db->update(array('longetude' => floatval($long2), 'latitude' => floatval($lat2)))->table('profiles')->where('user', '=', $userId);
				$exec = $updateStatement->execute();
			}
			$res->latDen = $lat2;
			$res->lngDen = $long2;
		}
		if ($show !== '' && $show !== undefined && $show !== null)
		{
			$updateStatement = $db->update(array('showMe' => $show))->table('profiles')->where('user', '=', $userId);
			$exec = $updateStatement->execute();
		}
		return json_encode($res);
	}

	public function updateRate($arr)
	{
		$db = new Model;
		$db = $db->connect();
		if ($arr['why'] == 'view')
		{
			$selectView = $db->select(array('COUNT(*)'))->from('view')->where('target', '=', $arr['target']);
			$selectView->execute();
			$colViewDb = $selectView->fetchColumn();
			$colViewDb > 10 ? $colView = 10 : $colView = $colViewDb;
		}
		if ($arr['why'] == 'like')
		{
			$selectLike = $db->select(array('COUNT(*)'))->from('likes')->where('target', '=', $arr['target']);
			$selectLike->execute();
			$colLikeDb = $selectLike->fetchColumn();
			$colLikeDb > 10 ? $colLike = 10 : $colLike = $colLikeDb;			
		}
		if ($arr['why'] == 'match')
		{
			$selectMatch = $db->select(array('COUNT(*)'))->from('matches')->where('partner1', '=', $arr['target'])->orWhere('partner2', '=', $arr['target']);
			$selectMatch->execute();
			$colMatchDb = $selectMatch->fetchColumn();
			$colMatchDb > 20 ? $colMatch = 20 : $colMatch = $colMatchDb;
		}
		//general formula
		//Famerate is plane sum views, likes, matches. 1 star values 10 points. by default you have 1 star. max you may have 5 stars(points can be more, it is sum stored in famerate). view max number 10, it is equval 100%, like same, match max number 20. you may have more then this quantaty but it still will give you up to 100%. count percentage for each category which influense your fame reting, find medium number, multiply by 40 + default 10 points devide on 5 and get stars quantety.
		$stars = ceil(round((((($colView * 10 + $colLike * 10 + ($colMatch * 10 / 2)) / 300) * 40 + 10) / 10), 1, PHP_ROUND_HALF_UP) / 0.5) * 0.5;
		$fameRate = $colViewDb + $colLikeDb + $colMatchDb;
		$updateStatement = $db->update(array('fameRate' => $fameRate, 'stars' => $stars))->table('profiles')->where('user', '=', $arr['target']);
		$updateStatement->execute();
		return true;
	}

	public function postReturnGuestInfo($request, $response)
	{
		$userId = $request->getParam('uId');
		$who = $request->getParam('who');
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('photos')->where('userNbr', '=', $userId);
		$sql = $sql->orderBy('whenAdd', 'DESC');
		$exec = $sql->execute();
		$fromDb = $exec->fetchAll();
		$photos = array();
		foreach ($fromDb as $key => $photo) {
			array_push($photos, $photo['src']);
		}
		$result->userPhoto = $photos;

		date_default_timezone_set ('Europe/Kiev');
		$date = date('Y-m-d G:i:s');
		$insertStatement = $db->insert(array('whoView', 'target', 'whenView'))
						   ->into('views')
						   ->values(array($who, $userId, $date));
		$insertView = $insertStatement->execute(false);

		$arrForUpdate = array('target' => $userId, 'why' => 'view');
		$updatedRate = $this->updateRate($arrForUpdate);

		$sql2= $db->select()->from('users')->join('profiles', 'users.userId', '=', 'profiles.user')->where('userId', '=', $userId);
		$exec = $sql->execute();
		$forSearch = $exec->fetch();
		$userData = array('login' => $forSearch['login'], 'fname' => $forSearch['fname'], 'lname' => $forSearch['lname'], 'age' => $forSearch['age'], 'sex' => $forSearch['sex'], 'sexPref' => $forSearch['sexPref'], 'fameRate' => $forSearch['fameRate'], 'stars' => $forSearch['stars'], 'profilePic' => $forSearch['profilePic'], 'isOnline' => boolval($forSearch['isOnline']), 'lastSeen' => $forSearch['last_seen']);
		$result->userData = $userData;
		//update table views, change targets fame rate
		return json_encode($result);
	}
}