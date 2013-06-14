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

<div class="field">
    <label for="bookreader_toolbar_color"><?php echo __('Hex color for viewer toolbar'); ?></label>
    <div class="inputs">
        <input type="text" class="textinput" name="bookreader_toolbar_color" size="7" value="<?php echo get_option('bookreader_toolbar_color'); ?>" id="bookreader_toolbar_color" />
    </div>
</div>

<fieldset id="fieldset-embed"><legend><?php echo __('Embed mode'); ?></legend>
    <div class="field">
        <label for="bookreader_embed_enable">
            <?php echo __('Enable embed mode in items/show page'); ?>
        </label>
        <div class="inputs">
        <?php echo get_view()->formCheckbox('bookreader_embed_enable', TRUE,
            array('checked' => (boolean) get_option('bookreader_embed_enable'))); ?>
            <p class="explanation">
                <?php echo __('If checked, the viewer will be available in embed mode in the "items/show/id" page and not only in full page in "viewer/show/id" page.'); ?>
            </p>
        </div>
    </div>  
    <div class="field">
        <label for="bookreader_sorting_mode">
            <?php echo __('Sorting by original filename ?'); ?>
        </label>
        <div class="inputs">
        <?php echo get_view()->formCheckbox('bookreader_sorting_mode', TRUE,
            array('checked' => (boolean) get_option('bookreader_sorting_mode'))); ?>
            <p class="explanation">
                <?php echo __('If checked, the viewer will sort images in viewer by original filename.'); ?>
            </p>
        </div>
    </div>  

    
    <div class="field">
        <label for="bookreader_mode_page"><?php echo __('Number of pages in Embed mode (1 or 2)'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_mode_page" size="1" value="<?php echo get_option('bookreader_mode_page'); ?>" id="bookreader_mode_page" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_embed_functions"><?php echo __('Enable all functions in Embed mode (0 or 1)'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_embed_functions" size="1" value="<?php echo get_option('bookreader_embed_functions'); ?>" id="bookreader_embed_functions" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_width"><?php echo __('Width of the inline frame (Embedded Simple Viewer)'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_width" size="3" value="<?php echo get_option('bookreader_width'); ?>" id="bookreader_width" />
        </div>
    </div>

    <div class="field">
        <label for="bookreader_height"><?php echo __('Height of the inline frame (Embedded Simple Viewer)'); ?></label>
        <div class="inputs">
            <input type="text" class="textinput" name="bookreader_height" size="3" value="<?php echo get_option('bookreader_height'); ?>" id="bookreader_height" />
        </div>
    </div>
</fieldset>
