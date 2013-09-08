<fieldset id="fieldset-embed"><legend><?php echo __('General parameters'); ?></legend>
    <div class="field">
        <label for="bookreader_custom_css"><?php echo __('Url to a custom css for BookReader'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_custom_css" size="7" value="<?php echo get_option('bookreader_custom_css'); ?>" id="bookreader_custom_css" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_favicon_url"><?php echo __('Favicon URL for viewer pages'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_favicon_url" size="65" value="<?php echo get_option('bookreader_favicon_url'); ?>" id="bookreader_favicon_url" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_custom_library"><?php echo __('Path of the custom library'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_custom_library" size="65" value="<?php echo get_option('bookreader_custom_library'); ?>" id="bookreader_custom_library" />
        </div>
        <p class="explanation">
            <?php echo __('Custom functions are used to get infos from your files.');
            echo ' ' . __('Default file is "%s".', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'BookReaderCustom.php'); ?>
        </p>
    </div>

    <div class="field">
        <label for="bookreader_sorting_mode">
            <?php echo __('Sorting by original filename ?'); ?>
        </label>
        <div class="inputs">
            <?php echo get_view()->formCheckbox('bookreader_sorting_mode', TRUE, array('checked' => (boolean) get_option('bookreader_sorting_mode'))); ?>
            <p class="explanation">
                <?php echo __('If checked, the viewer will sort images in viewer alphabetically, by original filename.'); ?>
            </p>
        </div>
    </div>
</fieldset>

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
        <label for="bookreader_class"><?php echo __('Class to add to the inline frame in embed mode'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_class" size="7" value="<?php echo get_option('bookreader_class'); ?>" id="bookreader_class" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_width"><?php echo __('Width of the inline frame in embed mode'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_width" size="5" value="<?php echo get_option('bookreader_width'); ?>" id="bookreader_width" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_height"><?php echo __('Height of the inline frame in embed mode'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_height" size="5" value="<?php echo get_option('bookreader_height'); ?>" id="bookreader_height" />
        </div>
    </div>
</fieldset>
