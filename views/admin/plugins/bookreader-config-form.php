<fieldset id="fieldset-embed"><legend><?php echo __('General parameters'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_custom_css"><?php echo __('Custom css'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_custom_css" size="7" value="<?php echo get_option('bookreader_custom_css'); ?>" id="bookreader_custom_css" />
            <p class="explanation">
                <?php echo __('Url to a custom css for BookReader (let empty if none).'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_favicon_url"><?php echo __('Favicon'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_favicon_url" size="65" value="<?php echo get_option('bookreader_favicon_url'); ?>" id="bookreader_favicon_url" />
            <p class="explanation">
                <?php echo __('Favicon URL for viewer pages.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_custom_library"><?php echo __('Custom library'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_custom_library" size="65" value="<?php echo get_option('bookreader_custom_library'); ?>" id="bookreader_custom_library" />
            <p class="explanation">
                <?php echo __('Custom functions are used to get infos from your files.');
                echo ' ' . __('Default file is "%s".', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'BookReaderCustom.php'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_sorting_mode">
                <?php echo __('Sorting by original filename ?'); ?>
            </label>
        </div>
        <div class='inputs five columns omega'>
            <?php echo get_view()->formCheckbox('bookreader_sorting_mode', TRUE, array('checked' => (boolean) get_option('bookreader_sorting_mode'))); ?>
            <p class="explanation">
                <?php echo __('If checked, the viewer will sort images in viewer alphabetically, by original filename.');
                echo ' ' . __('Note that you can batch reorder files in admin/items/show page. This will avoid to reorder them each time the item is displayed.');
                echo ' ' . __("In that case, don't forget to uncheck this box."); ?>
            </p>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-embed"><legend><?php echo __('Embed mode'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_mode_page"><?php echo __('Number of pages'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_mode_page" size="1" value="<?php echo get_option('bookreader_mode_page'); ?>" id="bookreader_mode_page" />
            <p class="explanation">
                <?php echo __('Default number of pages in embed mode (1 or 2).'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_embed_functions"><?php echo __('Functions in embed mode'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_embed_functions" size="1" value="<?php echo get_option('bookreader_embed_functions'); ?>" id="bookreader_embed_functions" />
            <p class="explanation">
                <?php echo __('Enable all functions in embed mode (0 to disable or 1 to enable).'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_class"><?php echo __('Class of inline frame'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_class" size="7" value="<?php echo get_option('bookreader_class'); ?>" id="bookreader_class" />
            <p class="explanation">
                <?php echo __('Class to add to the inline frame in embed mode.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_width"><?php echo __('Width of the inline frame'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_width" size="5" value="<?php echo get_option('bookreader_width'); ?>" id="bookreader_width" />
            <p class="explanation">
                <?php echo __('Width of the inline frame in embed mode.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <label for="bookreader_height"><?php echo __('Height of the inline frame'); ?></label>
        </div>
        <div class='inputs five columns omega'>
            <input type="text" class="textinput" name="bookreader_height" size="5" value="<?php echo get_option('bookreader_height'); ?>" id="bookreader_height" />
            <p class="explanation">
                <?php echo __('Height of the inline frame in embed mode.'); ?>
            </p>
        </div>
    </div>
</fieldset>
