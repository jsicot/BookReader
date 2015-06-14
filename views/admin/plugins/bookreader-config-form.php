<fieldset id="fieldset-bookreader-general"><legend><?php echo __('General parameters'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_favicon_url',
                __('Favicon')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('Favicon URL for viewer pages.'); ?>
            </p>
            <?php echo $this->formText('bookreader_favicon_url', get_option('bookreader_favicon_url'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_creator',
                __('Creator')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('A custom creator can be used to get infos from your files.'); ?>
                <?php echo  __('Default creator is "BookReader_Creator_Default". Other defaults are "BookReader_Creator_Simple", "BookReader_Creator_ExtractOCR" and "BookReader_Creator_RefnumOCR".'); ?>
            </p>
            <?php echo $this->formText('bookreader_creator', get_option('bookreader_creator'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_sorting_mode',
                __('Sorting by original filename')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('If checked, the viewer will sort images in viewer alphabetically, by original filename.');
                echo ' ' . __('Note that you can batch reorder files in admin/items/show page. This will avoid to reorder them each time the item is displayed.');
                echo ' ' . __("In that case, don't forget to uncheck this box."); ?>
            </p>
            <?php echo $this->formCheckbox('bookreader_sorting_mode', true,
                array('checked' => (boolean) get_option('bookreader_sorting_mode'))); ?>
        </div>
    </div>
</fieldset>
<fieldset id="fieldset-bookreader-embed"><legend><?php echo __('Embed mode'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_append_items_show',
                __('Append to "item show" page')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('If checked, the viewer will be automatically appended to the items/show page.'); ?>
            </p>
            <?php echo $this->formCheckbox('bookreader_append_items_show', true,
                array('checked' => (boolean) get_option('bookreader_append_items_show'))); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_mode_page',
                __('Number of pages')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('Default number of pages in embed mode (1 or 2).'); ?>
            </p>
            <?php echo $this->formText('bookreader_mode_page', get_option('bookreader_mode_page'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_embed_functions',
                __('Functions in embed mode')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('Enable all functions in embed mode (0 to disable or 1 to enable).'); ?>
            </p>
            <?php echo $this->formText('bookreader_embed_functions', get_option('bookreader_embed_functions'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_class',
                __('Class of inline frame')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('Class to add to the inline frame in embed mode.'); ?>
            </p>
            <?php echo $this->formText('bookreader_class', get_option('bookreader_class'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_width',
                __('Width of the inline frame')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('Width of the inline frame in embed mode.'); ?>
            </p>
            <?php echo $this->formText('bookreader_width', get_option('bookreader_width'), null); ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('bookreader_height',
                __('Height of the inline frame')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class="explanation">
                <?php echo __('Height of the inline frame in embed mode.'); ?>
            </p>
            <?php echo $this->formText('bookreader_height', get_option('bookreader_height'), null); ?>
        </div>
    </div>
</fieldset>
