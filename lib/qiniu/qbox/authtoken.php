<?php
require_once('config.php');
require_once('utils.php');

function QBox_MakeAuthToken(array $params)
{
    global $QBOX_ACCESS_KEY, $QBOX_SECRET_KEY;

	if (isset($params['expiresIn'])) {
		$expiresIn = $params['expiresIn'];
		unset($params['expiresIn']);
	} else {
		$expiresIn = 3600;
	}

	$params['deadline'] = time() + $expiresIn;
	$signature = QBox_Encode(json_encode($params));
	unset($params['deadline']);
	$params['expiresIn'] = $expiresIn;

	$digest = hash_hmac('sha1', $signature, $QBOX_SECRET_KEY, true);
	$encoded_digest = QBox_Encode($digest);

	return $QBOX_ACCESS_KEY . ":$encoded_digest:$signature";
}

