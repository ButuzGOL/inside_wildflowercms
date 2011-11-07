<?php
	echo 
	$form->create('User', array('url' => $html->url(array('action' => 'wf_mass_update', 'base' => false))));
?>

<h2 class="section"><?php __('User Accounts'); ?></h2>

<?php echo $this->element('wf_select_actions'); ?>

<ul class="list">
    <?php foreach ($users as $user): ?>
        <li class="post-row actions-handle">
            <span class="row-check"><?php echo $form->checkbox('id.' . $user['WildUser']['id']) ?></span>
            <span class="title-row"><?php echo $html->link($user['WildUser']['name'], array('action' => 'wf_edit', $user['WildUser']['id']), array('title' => __('Edit this user account.', true))) ?></span>
            <span class="cleaner"></span>
        </li>
    <?php endforeach; ?>
</ul>

<?php
    echo
    $this->element('wf_select_actions'), 
	//$this->element('wf_pagination'),
    $form->end();
?>



<?php $partialLayout->blockStart('sidebar'); ?>
    <li class="sidebar-box">
        <h4 class="add"><?php __('Add a new user account'); ?></h4>
        <?php echo 
            $form->create('WildUser', array('action' => 'create')),
            $form->input('name', array('between' => '<br />')),
            $form->input('email', array('between' => '<br />')),
            $form->input('login', array('between' => '<br />')),
            $form->input('password', array('between' => '<br />')),
            $form->input('confirm_password', array('between' => '<br />', 'type' => 'password')),
            $wild->submit('Create this user'),
            $form->end();
        ?>
    </li>
<?php $partialLayout->blockEnd(); ?>
    
