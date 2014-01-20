<fieldset id="bookreader-sort-item-files">
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
            <label class="mix-files-types">
                <?php
                    echo $this->formCheckbox('custom[bookreader][mixFilesTypes]', null, array(
                        'checked' => false, 'class' => 'mix-files-types-checkbox'));
                ?>
            </label>
        </div>
    </div>
</fieldset>
<fieldset id="bookreader-item-metadata">
    <h2><?php echo __('Prepare and save data for BookReader'); ?></h2>
    <div class="field">
        <p class="explanation">
            <?php echo __('Save all data needed to display a BookReader in order to avoid to calculate them each time an item is displayed.');
            echo ' ' . __('This process can be done only if the custom library has got a function to do it.'); ?>
        </p>
        <label class="two columns alpha">
            <?php echo __('Prepare and save data'); ?>
        </label>
        <div class="inputs five columns omega">
            <label class="save-data">
                <?php
                    echo $this->formCheckbox('custom[bookreader][saveData]', null, array(
                        'checked' => false, 'class' => 'save-data-checkbox'));
                ?>
            </label>
        </div>
    </div>
</fieldset>
