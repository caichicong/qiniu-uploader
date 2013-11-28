<?php

define('DOMAIN', $_SERVER["HTTP_HOST"]);
define('HOST', 'http://'.DOMAIN.'/');

$config = array(

    # DEBUG
    'error' => array(
        'reporting'       => 4095,
        'throw_exception' => true,
    ),

    # qiniu account
    'qbox' => array(
        'access_key' => 'Please input your access_key here',
        'secret_key' => 'Please input your secret_key here',
        'bucket'     => 'your bucket name',
    ),
    'domain' => 'your custom domain'
);
