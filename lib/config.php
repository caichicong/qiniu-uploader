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
        'access_key' => '9V8EdVCNkAJ4GHLesS7Rh1JsMAOX-ihQd--s4UNx',
        'secret_key' => 'flz46ZBTpVoNEW6BcfPtRKiWozU5OcBvHpoM-I7T',
        'bucket'     => 'notes',
    ),

);
