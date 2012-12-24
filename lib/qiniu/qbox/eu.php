<?php
require_once('oauth.php');

/**
 * End-user Settings Service
 * 终端用户配置项服务
 */
class Service {

	public $Conn;

	public function __construct($conn) {
		$this->Conn = $conn;
	}

	public function GetWatermark($customer) {
		$url = QBOX_EU_HOST . '/wmget';
		$params = array('customer' => $customer);
		return QBox_OAuth2_CallWithParams($this->Conn, $url, $params);
	}

	public function SetWatermark($customer, array $tpl) {
		$url = QBOX_EU_HOST . '/wmset';
		$tpl['customer'] = $customer;
		$ret = QBox_OAuth2_CallWithParamsNoRet($this->Conn, $url, $tpl);
		unset($tpl['customer']);
		return $ret;
	}
}

function QBox_EU_NewService($conn) {
	return new Service($conn);
}

