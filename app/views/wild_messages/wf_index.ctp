<?php
    echo 
    $list->create($messages, array('model' => 'WildMessage', 'class' => 'list selectable-list', 'element' => 'admin_messages_list_item')),
    $this->element('wf_pagination');
?>  
