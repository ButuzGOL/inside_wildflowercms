<?php 
    echo 
    $form->create('WildPost', array('url' => $html->url(array('action' => 'update', 'base' => false)), 'class' => 'horizontal-form')),
    '<div>',
    $form->hidden('id'),
    '</div>';
?>

<h2 class="section">Post Options</h2>
<?php
    echo 
    $form->input('draft', array('type' => 'select', 'label' => 'Status', 'options' => WildPost::getStatusOptions())),
    $form->input('description_meta_tag', array('type' => 'textarea', 'rows' => 4, 'cols' => 60, 'tabindex' => '4')),
    $form->input('slug', array('label' => 'URL slug', 'size' => 61)),
    $form->input('created', array());
?>

<div class="horizontal-form-buttons">
    <div class="submit save-section">
        <input type="submit" value="<?php __('Save options'); ?>" />
    </div>
    <div class="cancel-edit"> <?php __('or'); ?> <?php echo $html->link(__('Cancel and go back to post edit', true), array('action' => 'edit', $this->data['WildPost']['id'])); ?></div>
</div>

<?php echo $form->end(); ?>

<p class="post-info">
    This post is <?php if ($isDraft): ?>not published, therefore not visible to the public<?php else: ?>published and visible to the public at <?php echo $html->link(FULL_BASE_URL . $this->base . WildPost::getUrl($this->data['WildPost']['uuid']), WildPost::getUrl($this->data['WildPost']['uuid'])); ?><?php endif; ?>. Latest changes were made <?php echo $time->nice($this->data['WildPost']['updated']); ?> by <?php echo hsc($this->data['WildUser']['name']); ?>.
</p>


<?php $partialLayout->blockStart('sidebar'); ?>
    <li class="sidebar-box">
        <h4>Editing options for post...</h4>
        <?php echo $html->link($this->data['WildPost']['title'], array('action' => 'edit', $this->data['WildPost']['id']), array('class' => 'edited-item-link')); ?>
    </li>
<?php $partialLayout->blockEnd(); ?>