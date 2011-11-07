<?php
    $partialLayout->switchToEditorMode();
    $partialLayout->setLayoutVar('publishedLink', $html->link(FULL_BASE_URL . $this->base . $this->data['WildPage']['url'], $this->data['WildPage']['url']));
 
    echo 
    $form->create('WildPage', array('url' => $html->url(array('action' => 'update', 'base' => false)), 'class' => 'horizontal-form')),
    '<div>',
    $form->hidden('id'),
    $form->hidden('draft'),
    '</div>';
?>

<h2 class="section">Page options for <?php echo $html->link($this->data['WildPage']['title'], array('action' => 'edit', $this->data['WildPage']['id'])); ?></h2>
<?php
    echo 
    $form->input('parent_id', array('type' => 'select', 'label' => 'Parent page', 'options' => $parentPageOptions, 'empty' => '(none)', 'escape' => false)),
    $form->input('draft', array('type' => 'select', 'label' => 'Status', 'options' => WildPage::getStatusOptions())),
    $form->input('description_meta_tag', array('type' => 'textarea', 'rows' => 4, 'cols' => 60, 'tabindex' => '4')),
    $form->input('slug', array('label' => 'URL slug', 'size' => 61)),
    $form->input('created', array());
?>

<div class="horizontal-form-buttons">
    <div class="submit save-section">
        <input type="submit" value="<?php __('Save options'); ?>" />
    </div>
    <div class="cancel-edit"> <?php __('or'); ?> <?php echo $html->link(__('Cancel', true), array('action' => 'edit', $this->data['WildPage']['id'])); ?></div>
</div>

<?php 
    echo 
    $form->end();
?>


<?php $partialLayout->blockStart('sidebar'); ?>
    <li class="sidebar-box">
        <h4>Editing options for page...</h4>
        <?php echo $html->link($this->data['WildPage']['title'], array('action' => 'edit', $this->data['WildPage']['id']), array('class' => 'edited-item-link')); ?>
    </li>
<?php $partialLayout->blockEnd(); ?>