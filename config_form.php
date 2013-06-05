<?php 
/**
 * Config form include
 *
 * Included in the configuration page for the plugin to change settings.
 *
 */
?>
<div class="field">
	<label for="bookreader_mode_page">Logo URL for toolbar viewer:</label>
	<div class="inputs">
	<input type="text" class="textinput"  name="bookreader_logo_url" size="65" value="<?php echo get_option('bookreader_logo_url'); ?>" id="bookreader_logo_url" />
	</div>
</div>

<div class="field">
	<label for="bookreader_mode_page">Favicon URL for viewer pages:</label>
	<div class="inputs">
	<input type="text" class="textinput"  name="bookreader_favicon_url" size="65" value="<?php echo get_option('bookreader_favicon_url'); ?>" id="bookreader_favicon_url" />
	</div>
</div>

<div class="field">
	<label for="bookreader_mode_page">Hex color for toolbar viewer:</label>
	<div class="inputs">
	<input type="text" class="textinput"  name="bookreader_toolbar_color" size="7" value="<?php echo get_option('bookreader_toolbar_color'); ?>" id="bookreader_toolbar_color" />
	</div>
</div>

<div class="field">
	<label for="bookreader_mode_page">Number of page in Embed mode (1 or 2):</label>
	<div class="inputs">
	<input type="text" class="textinput"  name="bookreader_mode_page" size="1" value="<?php echo get_option('bookreader_mode_page'); ?>" id="bookreader_mode_page" />
	</div>
</div>

<div class="field">
	<label for="bookreader_default_width">The WIDTH of the inline frame (Embedded Simple Viewer)</label>
	<div class="inputs">
		<input type="text" class="textinput" name="bookreader_default_width" size="3" value="<?php echo get_option('bookreader_default_width'); ?>" id="bookreader_default_width" />
	</div>
</div>

<div class="field">
	<label for="bookreader_default_height">The HEIGHT of the inline frame (Embedded Simple Viewer)</label>
	<div class="inputs">
		<input type="text" class="textinput"  name="bookreader_default_height" size="3" value="<?php echo get_option('bookreader_default_height'); ?>" id="bookreader_default_height" />
	</div>
</div>
