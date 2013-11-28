<?php
/*
Plugin Name: Qiniu uploader
Plugin URI: http://github.com/caichicong
Version: 0.1
Author: chicong cai
Description: upload image to qiniu cloud store and copy the url to current post.
 */

/*
some code refer to the following plugin

http://wordpress.org/extend/plugins/dbank-uploader/
 */

global $QBOX_ACCESS_KEY;
global $QBOX_SECRET_KEY;
global $QBOX_BUCKET;
global $qiniu_domain;

define('qiniu_ABSPATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
define('qiniu_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
define('LIB_DIR', qiniu_ABSPATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);
define('QBOX_SDK_DIR', LIB_DIR . 'qiniu' . DIRECTORY_SEPARATOR . 'qbox' . DIRECTORY_SEPARATOR);

require_once LIB_DIR . 'config.php';
require_once LIB_DIR . 'helper.php';
require_once QBOX_SDK_DIR . 'rs.php';
require_once QBOX_SDK_DIR . 'fileop.php';
require_once QBOX_SDK_DIR . 'client/rs.php';
require_once QBOX_SDK_DIR . 'authtoken.php';

/**
 * 配置七牛云存储密钥信息
 */
$QBOX_ACCESS_KEY = $config["qbox"]["access_key"];
$QBOX_SECRET_KEY = $config["qbox"]["secret_key"];
$QBOX_BUCKET = $config['qbox']['bucket'];

$qiniu_domain = $config['domain'];

/* install qiniu db*/
global $qiniu_db_version;
$qiniu_db_version = "1.0";

function qiniu_install () {
    global $wpdb;
    global $qiniu_db_version;
    $table_name = $wpdb->prefix . "qiniu";
    $sql ="
        CREATE TABLE IF NOT EXISTS `$table_name` (
            `key` varchar(255) NOT NULL,
            `name` varchar(100) NOT NULL,
            `size` int(11) NOT NULL,
            `type` varchar(10) NOT NULL,
            `addtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`key`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option("qiniu_db_version", $qiniu_db_version);
}

register_activation_hook(__FILE__, 'qiniu_install');


/* plugin class */
class qiniu_uploader {
    function qiniu_uploader() {
        global $wp_version;
        global $QBOX_ACCESS_KEY, $QBOX_SECRET_KEY;

        // The current version
        define('qiniu_uploader_VERSION', '0.1');

        // Check for WP2.6 installation
        if (!defined ('IS_WP26'))
            define('IS_WP26', version_compare($wp_version, '2.6', '>=') );

        //This works only in WP2.6 or higher
        if ( IS_WP26 == FALSE) {
            add_action('admin_notices', create_function('', 
                'echo \'<div id="message" class="error fade"><p><strong>Sorry, this uploader works only under WordPress 2.6 or higher</strong></p></div>\';'));
            return;
        } else {
            // define URL
            include_once (dirname (__FILE__)."/tinymce/tinymce.php");

        }
    }
}

add_action( 'plugins_loaded', create_function( '', 'global $qiniu_uploader; $qiniu_uploader = new qiniu_uploader();' ) );

/* handle ajax request which sent from uploadSuccess(assets/js/handlers.js) */

function save_qiniu_filekey() {
    check_ajax_referer( "save_qiniu_filekey" );
    global $wpdb;
    $table_name = $wpdb->prefix . "qiniu";
    if($_POST['file_key'] && $_POST['file_name'] && $_POST['file_size'] && $_POST['file_type']) {
        $sql = $wpdb->prepare( 
            "
            INSERT `$table_name` 
            (`key`, `name`, `size`, `type`)
            values (%s, %s, %d, %s)
            ",
            $_POST['file_key'],
            $_POST['file_name'],
            $_POST['file_size'],
            $_POST['file_type']  
        );
        $wpdb->query($sql);
    }

    die();
}

if( is_admin() ) {
    add_action('wp_ajax_save_qiniu_filekey', 'save_qiniu_filekey' );
}

/* show images in qiniu*/
function qiniu_image_list() {
    if ( !current_user_can( 'edit_posts' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    global $wpdb;
    $table_name = $wpdb->prefix . "qiniu";	
    $pagesize = 16;
    $page = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
    $sql = $wpdb->prepare("select `key`, `name` from $table_name order by `addtime` DESC limit %d , %d",
        ($page - 1) * $pagesize, $pagesize
    );
    $image_list = $wpdb->get_results($sql, ARRAY_A);

    echo '<div class="wrap" style="width:1000px;">';

    require(qiniu_ABSPATH . '/lib/config.php');

    foreach($image_list as $i) {
        printf('<div style="display:inline-block;margin:20px;"><img  src="http://%s.qiniudn.com/%s?imageView/1/w/100/h/100" /><div>%s</div></div>', 
            str_replace('_', '-', $config['qbox']['bucket']), $i['key'], $i['key']);
    }
    echo '</div>';

    $page_count = ceil($wpdb->get_var("select count(*) from $table_name ", 0, 0) / $pagesize);

    echo '<div>';
    for($i = 1; $i <= $page_count; $i++) {
        printf('<a style="margin-right:10px;" href="?page=qiniu-image&pageno=%d">%d</a>', $i, $i);
    }
    echo '</div>';
}

/* install menu */

function add_qiniu_plugin_menu() {
    add_media_page('图片列表', '七牛云储存', 'edit_posts', 'qiniu-image', 'qiniu_image_list');

}

add_action( 'admin_menu', 'add_qiniu_plugin_menu' );

function custom_colors() {
    echo '<style type="text/css">
        .wp_themeSkin span.qiniu_custom{background-image:url("/wp-content/plugins/qiniu-uploader/tinymce/qiniu.png")!important;}
        .wp_themeSkin span.qiniu_custom{background-position:0px -20px}
        .wp_themeSkin .qiniu_custom:hover span.qiniu_custom {background-position:0px 0px}
        </style>';
}

add_action('admin_head', 'custom_colors');

/* popup upload window */
add_filter('query_vars','upload_window_trigger');

function upload_window_trigger($vars) {
    $vars[] = 'upload_window';
    return $vars;
}

add_action('template_redirect', 'upload_window_check');
function upload_window_check() {
    global $QBOX_BUCKET, $qiniu_domain;
    if(intval(get_query_var('upload_window')) == 1) {
        /**
         * 初始化 OAuth Client Transport
         */
        $client = QBox_OAuth2_NewClient();

        /**
         * 初始化 Qbox Reource Service Transport
         */
        $rs = QBox_RS_NewService($client, $QBOX_BUCKET);
        $upToken = QBox_MakeAuthToken(array('expiresIn' => 3600));

        $nonce = wp_create_nonce('save_qiniu_filekey');

        include qiniu_ABSPATH . 'upload_window.php';
        exit;
    }
}
