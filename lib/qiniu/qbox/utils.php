<?php

function QBox_ErrorMessage($code, $error) {

	$msg = @$error['error'];
	if (empty($msg)) {
		return "errno($code)";
	} else {
		return $msg;
	}
}

function QBox_Encode($str) // URLSafeBase64Encode
{
	$find = array("+","/");
	$replace = array("-", "_");
	return str_replace($find, $replace, base64_encode($str));
}

