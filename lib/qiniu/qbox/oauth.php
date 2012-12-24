<?php

require_once('oauth2/Client.php');
require_once('oauth2/GrantType/IGrantType.php');
require_once('oauth2/GrantType/Password.php');
require_once('oauth2/GrantType/RefreshToken.php');

require_once('config.php');

/**
 * New OAuth2 Client
*/
function QBox_OAuth2_NewClient() {
    global $QBOX_ACCESS_KEY, $QBOX_SECRET_KEY;
	$client = new QBox_OAuth_Client('a75604760c4da4caaa456c0c5895c061c3065c5a', '75df554a39f58accb7eb293b550fa59618674b7d');
	$client->setAccessTokenType($client->access_token_qbox, $QBOX_SECRET_KEY);
	$client->setAccessToken($QBOX_ACCESS_KEY);
	return $client;
}

/**
 * Internal func
 */
function QBox_OAuth2_exchangeRet($client, $response) {
	$code = $response['code'];
	$result = $response['result'];
	if ($code === 200) {
		$token = @$result['access_token'];
		if (empty($token)) {
			return array(401, "");
		}
		$client->setAccessTokenType($client->$access_token_bearer);
		$client->setAccessToken($token);
	}
	return array($code, $result);
}

/**
 * Login by username & password, and more permanently.
 */
function QBox_OAuth2_ExchangeByPasswordPermanently($client, $user, $passwd, $permanentFile = QBOX_TOKEN_TMP_FILE) {
	$force = true;
	if (file_exists($permanentFile)) {
		if($tokenData = QBox_OAuth2_ReadTokenData($permanentFile)) {
			if ($tokenData["expired_at"] > time()) {
				$force = false;
				$code = 200;
				$result = $tokenData;
				$client->setAccessToken($tokenData["access_token"]);
			} else {
				list($code, $result) = QBox_OAuth2_ExchangeByRefreshToken($client, $tokenData["refresh_token"]);
				if ($code == 200) {
					$force = false;
					QBox_OAuth2_WriteTokenData($permanentFile, $result);
				}
			}
		}
	}
	if ($force === true) {
		list($code, $result) = QBox_OAuth2_ExchangeByPassword($client, $user, $passwd);
		if ($code === 200) {
			QBox_OAuth2_WriteTokenData($permanentFile, $result);
		}
	}
	return array($code, $result);
}

function QBox_OAuth2_WriteTokenData($filepath, array $token){
	$expiredAt = $token["expires_in"] + time();
	$tokenData = array_merge($token, array("expired_at" => $expiredAt));
	return file_put_contents($filepath, serialize($tokenData), LOCK_EX);
}

function QBox_OAuth2_ReadTokenData($filepath = QBOX_TOKEN_TMP_FILE){
	if ($tokenInfo = file_get_contents($filepath)) {
		$tokenData = unserialize($tokenInfo);
		if(is_array($tokenData)) {
			return $tokenData;
		}
	}
	return false;
}

/**
 * Login by username & password
 */
function QBox_OAuth2_ExchangeByPassword($client, $user, $passwd, $devid = '') {
	$params = array('username' => $user, 'password' => $passwd, 'device_id' => $devid);
	$response = $client->getAccessToken(QBOX_TOKEN_ENDPOINT, 'password', $params);
	return QBox_OAuth2_exchangeRet($client, $response);
}

/**
 * Login by refreshToken
 */
function QBox_OAuth2_ExchangeByRefreshToken($client, $token) {
	$params = array('refresh_token' => $token);
	$response = $client->getAccessToken(QBOX_TOKEN_ENDPOINT, 'refresh_token', $params);
	return QBox_OAuth2_exchangeRet($client, $response);
}

/**
 * func Call(client *Client, url string) => (result array, code int, err Error)
 */
function QBox_OAuth2_Call($client, $url) {
	$response = $client->fetch($url, array(), $client->http_method_post, array(), $client->http_form_content_type_application);

	$code = $response['code'];
	if ($code === 200) {
		return array($response['result'], 200, null);
	}
	return array(null, $code, $response['result']);
}

/**
 * func CallNoRet(client *Client, url string) => (code int, err Error)
 */
function QBox_OAuth2_CallNoRet($client, $url) {
	$response = $client->fetch($url, array(), $client->http_method_post, array(), $client->http_form_content_type_application);
	$code = $response['code'];
	if ($code === 200) {
		return array(200, null);
	}
	return array($code, $response['result']);
}


/**
 * func CallWithParams(client *Client, url string, params stringOrArray) => (result array, code int, err Error)
 */
function QBox_OAuth2_CallWithParams($client, $url, $params) {

	$response = $client->fetch($url, $params, $client->http_method_post, array(), $client->http_form_content_type_application);
	$code = $response['code'];
	if ($code === 200 || $code === 298) {
		return array($response['result'], $code, null);
	}
	return array(null, $code, $response['result']);
}


/**
 * func CallWithParamsNoRet(client *Client, url string, params stringOrArray) => (code int, err Error)
 */
function QBox_OAuth2_CallWithParamsNoRet($client, $url, $params) {

	$response = $client->fetch($url, $params, $client->http_method_post, array(), $client->http_form_content_type_application);
	$code = $response['code'];
	if ($code === 200) {
		return array(200, null);
	}
	return array($code, $response['result']);
}



/**
 * func CallWithBinary(client *Client, url string, fp File, bytes int64, timeout int) => (result array, code int, err Error)
 */
function QBox_OAuth2_CallWithBinary($client, $url, $fp, $bytes, $timeout) {
	$http_headers = array('Content-Type: application/octet-stream');
	$curl_options = array(
			CURLOPT_UPLOAD => true,
			CURLOPT_INFILE => $fp,
			CURLOPT_INFILESIZE => $bytes,
			CURLOPT_TIMEOUT_MS => $timeout
	);
	$response = $client->fetch(
			$url, array(), $client->http_method_post, $http_headers, $client->http_form_content_type_application, $curl_options);
	//var_dump($response);

	$code = $response['code'];
	if ($code === 200) {
		return array($response['result'], 200, null);
	}
	return array(null, $code, $response['result']);
}
