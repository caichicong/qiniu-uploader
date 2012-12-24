(function() {
	tinymce.create('tinymce.plugins.QiniuUploader', {
		init: function(ed, url) {
			// Register an example button
			ed.addButton('qiniu', {
				title: 'qiniu uploader',
				// image: url + '/qiniu.png',
				onclick: function() {
					// Display an alert when the user clicks the button
					//ed.windowManager.alert('Hello world!');
					ed.windowManager.open({
						url: url + '/../upload_window.php',
						width: 500,
						height: 300
					});
				},
                     
				'class': 'qiniu_custom' // Use the bold icon from the theme
			});
		}
	});

	// Register plugin with a short name
	tinymce.PluginManager.add('qiniu', tinymce.plugins.QiniuUploader);
})();



