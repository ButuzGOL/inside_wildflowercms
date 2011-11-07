<?php
App::import('Sanitize');
/**
 * Pages Controller
 *
 * Pages are the heart of every CMS.
 */
class WildPagesController extends AppController {
	
	public $components = array('RequestHandler', 'Seo');
	public $helpers = array('Cache', 'Text', 'Time', 'List', 'Tree');
    public $pageTitle = 'Pages';
    
    /**
     * A static about Wildflower page
     *
     */
    function wf_about() {
    }
        
    /**
     * Pages administration overview
     * 
     */
    function wf_index() {
        $this->pageTitle = 'Pages';
        $this->WildPage->recursive = -1;
    	$pages = $this->WildPage->find('all', array('order' => 'lft ASC'));
    	$newParentPageOptions = $this->WildPage->getListThreaded();
    	$this->set(compact('pages', 'newParentPageOptions'));
    }
        
    function beforeRender() {
        parent::beforeRender();
        $this->set('isPage', true);
        $this->params['Wildflower']['view']['isPage'] = true;
    }   

    /**
     * Create a new page, with title set, as a draft.
     *
     */
    function wf_create() {
        $this->data[$this->modelClass]['draft'] = 1;
        $this->data[$this->modelClass]['content'] = '';
        $this->WildPage->create($this->data);
        $this->WildPage->save();
        $this->redirect(array('action' => 'edit', $this->WildPage->id));
    }

    /**
     * Edit a page
     * 
     * @param int $id Page ID
     */
    function wf_edit($id = null) {
        if (isset($this->params['named']['rev'])) {
            $page = $this->WildPage->getRevision($id, $this->params['named']['rev']);
        } else {
            $page = $this->WildPage->findById($id);
        }
        
        $this->data = $page;
        $this->pageTitle = $page[$this->modelClass]['title'];

        $newParentPageOptions = $this->WildPage->getListThreaded();
        $revisions = $this->WildPage->getRevisions($id, 10);
        $isDraft = ($page['WildPage']['draft']);
        $this->set(compact('newParentPageOptions', 'revisions', 'isDraft'));
    }  

    function wf_update() {
        Configure::write('debug', 0);
        $this->data[$this->modelClass]['wild_user_id'] = $this->getLoggedInUserId();
        
        $this->WildPage->create($this->data);
        if (!$this->WildPage->exists()) return $this->cakeError('object_not_found');
        
        // Publish?
        if (isset($this->data['__save']['publish'])) {
            $this->data[$this->modelClass]['draft'] = 0;
        }
        unset($this->data['__save']);
        
        $oldUrl = $this->WildPage->field('url');
        
        $page = $this->WildPage->save();
        if (empty($page)) return $this->cakeError('save_error');
        
        $this->WildPage->contain('WildUser');
        $page = $this->WildPage->findById($this->WildPage->id);
        
        if (Configure::read('AppSettings.home_page_id') != $this->WildPage->id) {
            $this->WildPage->updateChildPageUrls($this->WildPage->id, $oldUrl, $page['WildPage']['url']);
        }
		$hasUser = $page['WildUser']['id'] ? true : false;
        // JSON response
        if ($this->RequestHandler->isAjax()) {
            $this->set(compact('page', 'hasUser'));
            return $this->render('wf_update');
        }
        
        $this->redirect(array('action' => 'edit', $this->data[$this->modelClass]['id']));
    }

    function wf_options($id = null) {
        $this->WildPage->contain('WildUser');
        $this->data = $this->WildPage->findById($id);
        
        if (empty($this->data)) return $this->cakeError('object_not_found');
        
        $this->pageTitle = $this->data[$this->modelClass]['title'];
        $parentPageOptions = $this->WildPage->getListThreaded($this->data['WildPage']['id']);
        $this->set(compact('parentPageOptions'));
    }   

     function wf_sidebar($id = null) {
        $this->WildPage->contain('WildUser');
        $this->data = $this->WildPage->findById($id);
        
        if (empty($this->data)) return $this->cakeError('object_not_found');
        
        $this->pageTitle = $this->data[$this->modelClass]['title'];
    }

    function update_root_cache() {
        if (!isset($this->params['requested'])) {
            return $this->do404();
        }
        
        // Get all pages without a parent except the home page and also all the home page children
        $homePageId = Configure::read('Wildflower.settings.home_page_id');
        $rootPages = $this->{$this->modelClass}->find('all', array(
            'conditions' => "parent_id IS NULL AND url <> '/' OR parent_id = $homePageId",
            'recursive' => -1,
            'fields' => array('id', 'url', 'slug'),
        ));
        
        if (!Configure::read('Wildflower.disableRootPageCache')) {
            WildflowerRootPagesCache::write($rootPages);
        }
        
        return $rootPages;
    }
    
    function wf_view($id = null) {
        if (isset($this->params['named']['rev'])) {
            $page = $this->WildPage->getRevision($id, $this->params['named']['rev']);
        } else {
            $page = $this->WildPage->findById($id);
        }
        
        // @TODO Process Widgets
        
        $revisions = $this->WildPage->getRevisions($id, 10);
        $this->set(compact('page', 'revisions'));
    }

    /**
     * View a page
     * 
     * Handles redirect if the correct url for page is not entered.
     */
    function view() {
        if (Configure::read('AppSettings.cache') == 'on') {
            $this->cacheAction = 60 * 60 * 24 * 3; // Cache for 3 days
        }
        
        // Parse attributes
        $args = func_get_args();
        $corrected = false;
        $argsCountBeforeFilter = count($args);
        $args = array_filter($args);
        $url = '/' . $this->params['url']['url'];
        
        //Redirect if the entered URL is not correct
        if (count($args) !== $argsCountBeforeFilter) {
            return $this->redirect($url);
        }
        
        // Determine if this is the site root (home page)
        $homeArgs = array('app', 'webroot');
        if ($url === '//' or $args === $homeArgs or $url === '/app/webroot/') {
            $this->isHome = true;
        }
        
        $this->params['Wildflower']['view']['isHome'] = $this->isHome;
        
        // Find the requested page
		$this->WildPage->recursive = -1;
        $page = array();
        
        if (isset($this->params['id'])) {
            $page = $this->WildPage->findByIdAndDraft($this->params['id'], 0);
        } else if ($this->isHome) {
            $page = $this->WildPage->findByIdAndDraft($this->homePageId, 0);
        } else {
            $slug = end(explode('/', $url));
	        $slug = self::slug($slug);
            $page = $this->WildPage->findBySlugAndDraft($slug, 0);
        }

		// Give 404 if no page found or requesting a parents page without a parent in the url
		$isChildWithoutParent = (!$this->isHome and ($page[$this->modelClass]['url'] !== $url));
		if (empty($page) or $isChildWithoutParent) {
			return $this->do404();
        }

        $this->pageTitle = $page[$this->modelClass]['title'];
        
        // View variables
        $this->set(array(
            'page' => $page,
            'currentPageId' => $page[$this->modelClass]['id'],
            'isPage' => true
        ));
        
        $this->params['pageMeta'] = array(
            'descriptionMetaTag' => $page[$this->modelClass]['description_meta_tag'],
            'keywordsMetaTag' => $page[$this->modelClass]['keywords_meta_tag']
        );
        $this->set($this->params['pageMeta']);
        // Parameters @TODO unify parameters
        $this->params['current'] = array(
            'type' => 'page', 
            'slug' => $page[$this->modelClass]['slug'], 
            'id' => $page[$this->modelClass]['id']);
        $this->params['Wildflower']['page']['slug'] = $page[$this->modelClass]['slug'];
        
        $this->_chooseTemplate($page[$this->modelClass]['slug']);
    }

/**
     * Renders a normal page view or home view
     *
     * @param string $slug
     */
    private function _chooseTemplate($slug) {
        // For home page home.ctp is the default
        $template = 'view';
        if ($this->isHome) {
            $template = 'home';
        }
        $render = $template;
        
        $possibleThemeFile = APP . 'views' . DS . 'themed' . DS . 'wild_pages' . DS . $slug . '.ctp';
        if (file_exists($possibleThemeFile)) {
            $render = $possibleThemeFile;
        }
        
        return $this->render($render);
    }
}
