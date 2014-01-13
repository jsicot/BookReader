<fieldset id="bookreader-item-metadata">
    <h2><?php echo __('Files order'); ?></h2>
    <div class="field">
        <p class="explanation">
            <?php echo __('Order files of each item by original filename.');
            echo ' ' . __('By default, leaves (images) are ordered before non-leaves files. You can order by filename only.'); ?>
        </p>
        <label class="two columns alpha">
            <?php echo __('Order files'); ?>
        </label>
        <div class="inputs five columns omega">
            <label class="order-by-filename">
                <?php
                    echo $this->formCheckbox('custom[bookreader][orderByFilename]', null, array(
                        'checked' => false, 'class' => 'order-by-filename-checkbox'));
                ?>
            </label>
        </div>
        <label class="two columns alpha">
            <?php echo __('Mix files types'); ?>
        </label>
        <div class="inputs five columns omega">
            <label class="mixFilesTypes">
                <?php
                    echo $this->formCheckbox('custom[bookreader][mixFilesTypes]', null, array(
                        'checked' => false, 'class' => 'mix-files-types-checkbox'));
                ?>
            </label>
        </div>
    </div>
</fieldset>
