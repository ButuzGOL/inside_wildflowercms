<?php
echo $navigation->create(array(
        'All users' => array('action' => 'index'),
        'Edit user' => array('action' => 'wf_edit', $this->data['WildUser']['id']),
    ), array('id' => 'sub-nav', 'class' => 'always-current'));
?>

<h3>Change password for user <?php echo hsc($this->data['WildUser']['name']) ?></h3>
<?php echo 
    $form->create('WildUser', array('url' => $html->url(array('action' => 'wf_update_password', 'base' => false)))),
    $form->input('password', array('between' => '<br />', 'label' => 'New password', 'tabindex' => '1')),
    $form->input('confirm_password', array('between' => '<br />', 'label' => 'New password again', 'type' => 'password', 'tabindex' => '2')),
    '<div class="hidden">',
    $form->hidden('name'),
    $form->hidden('id'),
    '</div>',
    $wild->submit('Save changes'),
    $form->end();
?>
