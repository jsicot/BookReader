<div class="field">
    <label for="bookreader_logo_url"><?php echo __('Logo URL for toolbar viewer'); ?></label>
    <div class="inputs">
        <input type="text" class="textinput" name="bookreader_logo_url" size="65" value="<?php echo get_option('bookreader_logo_url'); ?>" id="bookreader_logo_url" />
    </div>
</div>

<div class="field">
    <label for="bookreader_favicon_url"><?php echo __('Favicon URL for viewer pages'); ?></label>
    <div class="inputs">
        <input type="text" class="textinput" name="bookreader_favicon_url" size="65" value="<?php echo get_option('bookreader_favicon_url'); ?>" id="bookreader_favicon_url" />
    </div>
</div>

<fieldset id="fieldset-embed"><legend><?php echo __('Embed mode'); ?></legend>
    <div class="field">
        <label for="bookreader_mode_page"><?php echo __('Number of pages in embed mode (1 or 2)'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_mode_page" size="1" value="<?php echo get_option('bookreader_mode_page'); ?>" id="bookreader_mode_page" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_embed_functions"><?php echo __('Enable all functions in embed mode (0 or 1)'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_embed_functions" size="1" value="<?php echo get_option('bookreader_embed_functions'); ?>" id="bookreader_embed_functions" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_width"><?php echo __('Width of the inline frame in embed mode'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_width" size="3" value="<?php echo get_option('bookreader_width'); ?>" id="bookreader_width" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_height"><?php echo __('Height of the inline frame in embed mode'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_height" size="3" value="<?php echo get_option('bookreader_height'); ?>" id="bookreader_height" />
        </div>
    </div>
</fieldset>

<div class="field">
    <label for="bookreader_toolbar_color"><?php echo __('Color of the toolbar (#e2dcc5)'); ?></label>
    <div class="inputs">
        <input type="text" class="textinput" name="bookreader_toolbar_color" size="3" value="<?php echo get_option('bookreader_toolbar_color'); ?>" id="bookreader_height" />
    </div>
</div>

<div class="field">
    <label for="bookreader_custom_library"><?php echo __('Path of the custom library'); ?></label>
    <div class="inputs">
        <input type="text" class="textinput" name="bookreader_custom_library" size="65" value="<?php echo get_option('bookreader_custom_library'); ?>" id="bookreader_custom_library" />
    </div>
    <p class="explanation">
        <?php echo __('Path of the custom library you use to get infos from your files.');
        echo ' ' . __('Default directory is "%s".', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'BookReaderCustom.php'); ?>
    </p>
</div>
