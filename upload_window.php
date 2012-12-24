<?php 
require_once( dirname(__FILE__) .'/../../../wp-load.php');
require_once qiniu_ABSPATH. 'bootstrap.php'; 
$nonce = wp_create_nonce( 'save_qiniu_filekey' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>相册 - 上传图片</title>
<script type="text/javascript">
    var $bucket = '<?php echo $bucket; ?>';
    var $upToken = '<?php echo $upToken;?>';
	var $nonce = '<?php echo $nonce;?>';
</script>
<link href="assets/css/default.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="assets/js/jquery.js"></script>
<script type="text/javascript" src="assets/js/utf8_encode.js"></script>
<script type="text/javascript" src="assets/js/utf8_decode.js"></script>
<script type="text/javascript" src="assets/js/base64_encode.js"></script>
<script type="text/javascript" src="assets/js/base64_decode.js"></script>
<script type="text/javascript" src="assets/js/uniqid.js"></script>
<script type="text/javascript" src="assets/js/helper.js"></script>
<script type="text/javascript" src="assets/swfupload/swfupload.js"></script>
<script type="text/javascript" src="assets/js/swfupload.queue.js"></script>
<script type="text/javascript" src="assets/js/fileprogress.js"></script>
<script type="text/javascript" src="assets/js/handlers.js"></script>
<script type="text/javascript">
    var swfu;

    window.onload = function() {
        var settings = {
            flash_url : "assets/swfupload/swfupload.swf",
            upload_url:  "<?php echo QBOX_UP_HOST . "/upload"; ?>",
            post_params: {},
            use_query_string: false,
            file_post_name: "file",
            file_size_limit : "10 MB",
            file_types : "*.png;*.jpg;*.jpeg;*.gif",
            file_types_description: "Web Image Files",
            file_upload_limit : 100,
            file_queue_limit : 0,
            custom_settings : {
                fileUniqIdMapping : {},
                progressTarget : "fsUploadProgress",
                cancelButtonId : "btnCancel"
            },
            debug: false,

            // Button Settings
            button_image_url : "assets/images/XPButtonUploadText_61x22.png",
            button_placeholder_id : "spanButtonPlaceholder1",
            button_width: 61,
            button_height: 22,

            // The event handler functions are defined in handlers.js
            file_queued_handler : fileQueued,
            file_queue_error_handler : fileQueueError,
            file_dialog_complete_handler : fileDialogComplete,
            upload_start_handler : uploadStart,
            upload_progress_handler : uploadProgress,
            upload_error_handler : uploadError,
            upload_success_handler : uploadSuccess,
            upload_complete_handler : uploadComplete,
            queue_complete_handler : queueComplete	// Queue plugin event
	};

	swfu = new SWFUpload(settings);
    };
</script>
</head>
<body>


<div id="content">
    <form id="form1" action="#" method="post" enctype="multipart/form-data">
        <div class="fieldset flash" id="fsUploadProgress">
            <span class="legend">上传列表</span>
        </div>
        <div id="divStatus">0 Files Uploaded</div>

        <div style="padding-left: 5px;">
            <span id="spanButtonPlaceholder1"></span>
            <input id="btnCancel" type="button" value="Cancel All Uploads" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left: 2px; height: 22px; font-size: 8pt;" />
        </div>
    </form>
</div>
<div id="info"></div>
</body>
</html>
