<h4 class="add"><?php __('Upload a new file'); ?></h4>
<?php
	echo 
	$form->create('WildAsset', array('type' => 'file', 'action' => 'wf_create')),
    $form->input('file', array('type' => 'file', 'between' => '<br />', 'label' => false)),
    //$form->input('title', array('between' => '<br />', 'label' => 'Title <small>(optional)</small>')),
    "<p><small>$uploadLimits.</small></p>",
    $form->submit('Upload file'),
    $form->end();
?>
