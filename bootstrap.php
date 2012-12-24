<?php
date_default_timezone_set('Asia/Shanghai');

define('LIB_DIR', qiniu_ABSPATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);
define('QBOX_SDK_DIR', LIB_DIR . 'qiniu' . DIRECTORY_SEPARATOR . 'qbox' . DIRECTORY_SEPARATOR);

require_once LIB_DIR . 'config.php';
require_once LIB_DIR . 'helper.php';

require_once QBOX_SDK_DIR . 'rs.php';
require_once QBOX_SDK_DIR . 'fileop.php';
require_once QBOX_SDK_DIR . 'client/rs.php';
require_once QBOX_SDK_DIR . 'authtoken.php';

/**
 * 设置错误报告级别
 */
error_reporting($config['error']['reporting']);

/**
 * 配置七牛云存储密钥信息
 */
$QBOX_ACCESS_KEY = $config["qbox"]["access_key"];
$QBOX_SECRET_KEY = $config["qbox"]["secret_key"];

/**
 * 初始化 OAuth Client Transport
 */
$client = QBox_OAuth2_NewClient();

/**
 * 初始化 Qbox Reource Service Transport
 */
$bucket = $config["qbox"]["bucket"];
$rs = QBox_RS_NewService($client, $bucket);
$upToken = QBox_MakeAuthToken(array('expiresIn' => 3600));
