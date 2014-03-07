<fieldset id="bookreader-item-metadata">
    <h2><?php echo __('BookReader'); ?></h2>
    <p><?php
        echo __('Save all data needed to display a BookReader in order to avoid to calculate them each time an item is displayed.');
        echo ' ' . __('This is useful for old items only, because the process is launched automatically after an item is saved.');
        echo ' ' . __('This process can be done only if the custom library has got a function to do it.');
    ?></p>
    <div class="field">
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
            <p class="explanation"><?php
                echo __('Order files of each item according to the order of BookReader leaves.');
            ?></p>
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
            <p class="explanation"><?php
                echo ' ' . __('By default, leaves (images) are ordered before non-leaves files. You can order by filename only and mix all files.');
            ?></p>
        </div>
    </div>
</fieldset>
