<?php
class WildCommentsController extends AppController {
    public $helpers = array('Time', 'List');
    public $paginate = array(
        'limit' => 8,
        'order' => array(
            'WildComment.created' => 'desc'
        )
    );

    function wf_index() {
        $this->WildComment->contain('WildPost.title', 'WildPost.id');
        $comments = $this->paginate('WildComment', 'WildComment.spam = 0');
        $this->set('comments', $comments);
    }
}
