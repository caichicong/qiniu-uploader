=== Qiniu Uploader ===
Contributors: chicong cai
Tags: post, attachment, upload, download, storage, qiniu
Requires at least: 2.6
Tested up to: 3.5.0
Stable tag: trunk

== Description ==

You can upload your image to qiniu cloud storage

some code refer to the following plugin

http://wordpress.org/extend/plugins/dbank-uploader/

Please report bug in github.com. Thank you.

安装这个插件之后，可以在文章编辑界面点击七牛的图标，上传文件到七牛云储存

部分代码参考了以下插件

http://wordpress.org/extend/plugins/dbank-uploader/

请到github.com反馈bug. 谢谢

== Prerequisite ==

Please install curl extension for php (http://php.net/manual/en/curl.installation.php)

== Installation ==
1. rename qiniu-upload-master.zip to **qiniu-upload.zip** and unzip the file
2. input your access key ,secret key and bucket in lib/config.php 
3. Upload the entire folder to the `/wp-content/plugins/` directory.
4. Activate the plugin through the 'Plugins' menu in WordPress.

1. 把解压出来的文件夹重命名为qiniu-upload
2. 在lib/config.php  输入access key ,secret key, bucket  
3. 上传qiniu-uploader文件夹到 `/wp-content/plugins/`.
4. 在管理后台启用插件.

== Plugin Usage ==

点击富文本编辑器中的七牛图标,在弹出的窗口中点击upload选择要上传的文件,上传完毕后，
窗口将自动关闭，图片会插入到编辑器相应的位置中。


你可以在 后台管理菜单栏Media (多媒体)下的"七牛云储存"查看已经上传了的文件

== todo == 

1. 图片删除功能
2. 水印添加功能
