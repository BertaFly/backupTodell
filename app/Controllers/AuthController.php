<?php

namespace App\Controllers;
use App\Models\Model;

use Slim\Views\Twig as View;

class AuthController extends Controller
{
	public function generateToken($login, $id)
	{
		$expiration = time() + (15 * 60 * 1000);

		// Create token header as a JSON string
		$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

		// Create token payload as a JSON string
		$payload = json_encode(['user_login' => $login, 'user_id' => $id,'exp' => $expiration]);

		// Encode Header to Base64Url String
		$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

		// Encode Payload to Base64Url String
		$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

		// Create Signature Hash
		$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);

		// Encode Signature to Base64Url String
		$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

		// Create JWT
		$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
		return $jwt;
	}

	public function postSignIn($request, $response)
	{
		$db = new Model;
		$db = $db->connect();
		$sql = $db->select()->from('users')->where('login', '=', $request->getParam('login'));
		$exec = $sql->execute();
		$fromDb = $exec->fetch();

		if (count($fromDb) !== 0 && password_verify($request->getParam('pass'), $fromDb['password']) && $fromDb['isEmailConfirmed'] === 1)
		{
			$jwt = $this->generateToken($request->getParam('login'), $fromDb['id']);
			$result->user = $jwt;
			//record after login that user's last visit of our site has just happened
			date_default_timezone_set ('Europe/Kiev');
			$date = date('Y-m-d G:i:s');
			$updateStatement = $db->update(array('last_seen' => $date))
                       ->table('users')
                       ->where('id', '=', 1);
            $affectedRows = $updateStatement->execute();
			return json_encode($result);
		}
		return json_encode($false);
	}
}
