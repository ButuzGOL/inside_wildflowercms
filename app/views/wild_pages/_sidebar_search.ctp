<?php
    echo
    $form->create('WildPage', array('url' => $html->url(array('action' => 'wf_search', 'base' => false)), 'class' => 'search')),
    $form->input('query', array('label' => __('Find a page by typing', true), 'id' => 'SearchQuery')),
    $form->end();
?>