jQuery(function($) {
	$('.atg_datepicker').datepicker();
	$('.atg_colorpicker').wpColorPicker();
	$('.atg_field select').select2({
		width: 'resolve',
		allowClear: true
	});

	var _custom_media = true,
		_orig_send_attachment = wp.media.editor.send.attachment;

	$('.atg_upload').click(function(e) {
		e.preventDefault();
		var send_attachment_bkp = wp.media.editor.send.attachment;
		var button    = $(this);
		_custom_media = true;
		wp.media.editor.send.attachment = function(props, attachment) {
			if (_custom_media) {
				var image_url;
				if (attachment.mime.indexOf('image') == -1) {
					image_url = attachment.icon;
				} else {
					image_url = attachment.url;
				}
				$(e.target).siblings('.atg_reset').show();
				$(e.target).siblings('img').attr('src', image_url);
				$(e.target).siblings('span.atg_filename').text(attachment.filename);
				$(e.target).siblings('.atg_attachment_id').val(attachment.id);
				return false;
			} else {
				return _orig_send_attachment.apply( this, [props, attachment] );
			}
		};
		wp.media.editor.open(button);
	});

	$('.atg_reset').click(function(e) {
		e.preventDefault();
		$(this).hide();
		$(e.target).siblings('img').attr('src', '');
		$(e.target).siblings('span.atg_filename').text('');
		$(e.target).siblings('.atg_attachment_id').val('0');
	});

	$('.add_media').on('click', function(){
		_custom_media = false;
	});
});