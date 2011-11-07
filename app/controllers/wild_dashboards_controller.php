<?php
App::import('Sanitize');
class WildDashboardsController extends AppController {
	
	public $helpers = array('List', 'Time', 'Text');
	public $uses = array('WildComment', 'WildPage', 'WildPost');
	public $pageTitle = 'Dashboard';
	
	function wf_index() {
        // $comments = $this->WildComment->find('all', array('limit' => 5, 'conditions' => 'spam = 0'));
        // $messages = $this->WildMessage->find('all', array('limit' => 5));
        $pages = $this->WildPage->find('all', array('limit' => 10, 'order' => 'WildPage.updated DESC'));
		$this->set(compact('pages'));
	}
	
    /**
     * Admin page and post search
     *
     * @param string $query Search term, encoded by Javascript's encodeURI()
     */
    function wf_search($query = '') {
        fb($query);
        $query = urldecode($query);
        $postResults = $this->WildPost->search($query);
        $pageResults = $this->WildPage->search($query);
        $results = am($postResults, $pageResults);
        $this->set('results', $results);
    }
}
