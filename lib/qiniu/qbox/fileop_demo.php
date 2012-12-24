#!/usr/bin/env php
<?php

require_once('rs.php');
require_once('fileop.php');

$QBOX_ACCESS_KEY = '<Please apply your access key>';
$QBOX_SECRET_KEY = '<Dont send your secret key to anyone>';

$client = QBox_OAuth2_NewClient();

$bucketName = 'bucketName';
$rs = QBox_RS_NewService($client, $bucketName);

$key = '2.jpg';

list($result, $code, $error) = $rs->Get($key, $key);
echo "===> Get $key result:\n";
if ($code == 200) {
	var_dump($result);
} else {
	$msg = QBox_ErrorMessage($code, $error);
	echo "Get failed: $code - $msg\n";
	exit(-1);
}

$urlImageInfo = QBox_FileOp_ImageInfoURL($result['url']);

echo "===> ImageInfo of $key:\n";
echo file_get_contents($urlImageInfo) . "\n";


$targetKey = 'cropped-' . $key;
$source_img_url = $result['url'];
$opts = array("thumbnail" => "!120x120r",
              "gravity" => "center",
              "crop" => "!120x120a0a0",
              "quality" => 85,
              "rotate" => 45,
              "format" => "jpg",
              "auto_orient" => true);

$mogrifyPreviewURL = QBox_FileOp_ImageMogrifyPreviewURL($source_img_url, $opts);
echo "===> ImageMogrifyPreviewURL result:\n";
var_dump($mogrifyPreviewURL);

$imgrs = QBox_RS_NewService($client, "test_thumbnails_bucket");
list($result, $code, $error) = $imgrs->ImageMogrifyAs($targetKey, $source_img_url, $opts);
echo "===> ImageMogrifyAs $key result:\n";
if ($code == 200) {
	var_dump($result);
} else {
	$msg = QBox_ErrorMessage($code, $error);
	echo "ImageMogrifyAs failed: $code - $msg\n";
	exit(-1);
}

