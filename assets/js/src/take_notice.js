/**
 * Take Notice
 * Adds default colors to the color pickers
 */
 
jQuery(function($) {
	// light grey, red, orange, yellow, green, blue, purple, dark grey
	$('#take_notice_background_color').wpColorPicker({
		palettes: ['#e5e5e5', '#ffe5e0', '#ffe6bc', '#fff6bf', '#e6efc2', '#dbeef9', '#e4d7ec', '#555555']
	});
	$('#take_notice_border_color').wpColorPicker({
		palettes: ['#bbbbbb', '#fbc2c4', '#ffb535', '#ffd324', '#c6d880', '#93ccee', '#d6b2df', '#333333']
	});
	$('#take_notice_text_color').wpColorPicker({
		palettes: ['#555555', '#8a1f11', '#563504', '#514721', '#264409', '#004a78', '#4a3063', '#e5e5e5']
	});
});