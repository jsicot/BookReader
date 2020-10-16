<fieldset id="bookreader-item-metadata">
    <h2><?php echo __('BookReader'); ?></h2>
    <p><?php
        echo __('Save all data needed to display a BookReader in order to avoid to calculate them each time an item is displayed.');
        echo ' ' . __('This is useful for old items only, because the process is launched automatically after an item is saved.');
        echo ' ' . __('This process can be done only if the custom library has got a function to do it.');
    ?></p>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('orderByFilename',
                __('Order files')); ?>
        </div>
        <div class="inputs five columns omega">
            <?php echo $this->formCheckbox('custom[bookreader][orderByFilename]', null, array(
                'checked' => false, 'class' => 'order-by-filename-checkbox')); ?>
            <p class="explanation">
                <?php echo __('Order files of each item according to the order of BookReader leaves.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('mixFileTypes',
                __('Mix file types')); ?>
        </div>
        <div class="inputs five columns omega">
            <?php echo $this->formCheckbox('custom[bookreader][mixFileTypes]', null, array(
                'checked' => false, 'class' => 'mix-files-types-checkbox')); ?>
            <p class="explanation">
                <?php echo __('By default, leaves (images) are ordered before non-leaves files. You can order by filename only and mix all files.'); ?>
            </p>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('checkImageSize',
                __('Rebuild metadata when missing')); ?>
        </div>
        <div class="inputs five columns omega">
            <?php echo $this->formCheckbox('custom[bookreader][checkImageSize]', null, array(
                'checked' => false, 'class' => 'check-image-size-checkbox')); ?>
            <p class="explanation">
                <?php echo __('If checked, missing metadata of files will be rebuilt in order to get the size of images instantly.'); ?>
            </p>
        </div>
    </div>
</fieldset>
