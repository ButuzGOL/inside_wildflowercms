<?php
App::import('Sanitize');
class WildPostsController extends AppController {
	public $helpers = array('Time', 'List', 'Tree', 'Rss', 'Category', 'Textile');
	/** Pagination options for the wf_index action **/
    public $paginate = array(
        'limit' => 10,
        'order' => array('WildPost.created' => 'desc'),
    );
       
    /**
     * Posts overview
     * 
     */
    function wf_index() {
    	$posts = $this->paginate($this->modelClass);
        $this->set('posts', $posts);
    }
    
    function beforeFilter() {
    	parent::beforeFilter();
    	
    	$this->pageTitle = 'Blog';
    	
    	$this->params['current']['type'] = 'post';
    	$this->params['current']['slug'] = Configure::read('Wildflower.blogIndex');
    }
    
    function beforeRender() {
        parent::beforeRender();
        $this->set('isPosts', true);
        $this->params['Wildflower']['view']['isPosts'] = true;
    }    
    
     /**
     * Create a post and redirect to it's edit screen
     *
     */
    function wf_create() {
        // Generate UUID
        $uuid = sha1(String::uuid()); 
        // Check if unique
        while ($this->{$this->modelClass}->findByUuid($uuid)) {
            $uuid = sha1(String::uuid()); 
        }
        
        $defaultParams = array(
            'draft' => 1,
            'uuid' => $uuid,
            'slug' => $uuid,
        );
        $this->data[$this->modelClass] = am($this->data[$this->modelClass], $defaultParams);
        $this->{$this->modelClass}->create($this->data);
        $this->{$this->modelClass}->save();
        $this->redirect(array('action' => 'wf_edit', $this->{$this->modelClass}->id));
    }

    /**
     * Edit page
     * 
     * @param int $id post ID
     */
    function wf_edit($id = null, $revisionNumber = null) {
        if (empty($this->data)) {
            $this->WildPost->contain(array('WildUser', 'WildCategory'));
            $this->data = $this->WildPost->findById($id);
            if (empty($this->data)) return $this->cakeError('object_not_found');
        } else {
            if ($this->WildPost->save($this->data)) {
                return $this->redirect(array('action' => 'wf_edit', $this->WildPost->id));
            }
        }
        
        // If viewing a revision, merge with revision content
        if ($revisionNumber) {
            $this->data = $this->WildPost->getRevision($id, $revisionNumber);
            
            $this->set(array('revisionId' => $revisionNumber, 'revisionCreated' => $this->data['WildRevision']['created']));
        }
        
        // View
        $hasUser = $this->data['WildUser']['id'] ? true : false;
        $isDraft = ($this->data[$this->modelClass]['draft'] == 1) ? true : false;
        $isRevision = !is_null($revisionNumber);
        
        // Categories
        $categories = $this->WildPost->WildCategory->find('list', array('fields' => array('id', 'title')));
        $inCategories = Set::extract($this->data['WildCategory'], '{n}.id');
        
        // Revisions
        $revisions = $this->WildPost->getRevisions($id);
        
        $this->set(compact('isRevision', 'hasUser', 'isDraft', 'categories', 'inCategories', 'revisions'));
        $this->pageTitle = $this->data[$this->modelClass]['title'];
    }

    function wf_categorize($id = null) {
        $this->WildPost->contain(array('WildUser', 'WildCategory'));
        $this->data = $this->WildPost->findById($id);
        
        if (empty($this->data)) return $this->cakeError('object_not_found');
   
        $categories = $this->WildPost->WildCategory->find('list', array('fields' => array('id', 'title')));
        $inCategories = Set::extract($this->data['WildCategory'], '{n}.id');
        $isDraft = ($this->data[$this->modelClass]['draft'] == 1) ? true : false;
        $categoriesForTree = $this->WildPost->WildCategory->find('all', array('order' => 'lft ASC', 'recursive' => -1));
        $this->set(compact('categories', 'inCategories', 'isDraft', 'categoriesForTree'));
        
        $this->pageTitle = $this->data[$this->modelClass]['title'];
    }

    function wf_update() {
        Configure::write('debug', 0);
        $this->data[$this->modelClass]['wild_user_id'] = $this->getLoggedInUserId();

        // Publish?
        if (isset($this->data['__save']['publish'])) {
            $this->data[$this->modelClass]['draft'] = 0;
        }
        unset($this->data['__save']);
        
        $this->WildPost->create($this->data);
        
        if (!$this->WildPost->exists()) return $this->cakeError('object_not_found');
        
        if (isset($this->data[$this->modelClass]['categories_can_be_empty']) && !isset($this->data['WildCategory'])) {
             // Delete all post categories
             $this->WildPost->query("DELETE FROM categories_posts WHERE post_id = {$this->WildPost->id}");
        }

        if (!$this->WildPost->save()) return $this->cakeError('save_error'); // @TODO Rendering the exact save errors would be better

        // $cacheName = str_replace('-', '_', $this->data[$this->modelClass]['slug']); // @TODO check cache for proper naming method
        // clearCache($cacheName, 'views', '.php');
		
        if ($this->RequestHandler->isAjax()) {
            $this->WildPost->contain('WildUser');
            $post = $this->WildPost->findById($this->WildPost->id);
            $this->set(compact('post'));
            return $this->render('wf_update');
        }

        $this->redirect(array('action' => 'edit', $this->WildPost->id));
    }

    function wf_options($id = null) {
        $this->WildPost->contain(array('WildUser', 'WildCategory'));
        $this->data = $this->WildPost->findById($id);
        
        if (empty($this->data)) return $this->cakeError('object_not_found');
   
        $isDraft = ($this->data[$this->modelClass]['draft'] == 1) ? true : false;
        $this->set(compact('isDraft'));
        
        $this->pageTitle = $this->data[$this->modelClass]['title'];
    }

    /**
     * View particular post's comments
     * 
     */
    function wf_comments($id = null) {
        $this->data = $this->{$this->modelClass}->find('first', array(
            'conditions' => array('WildPost.id' => $id),
            'contain' => array(
                'WildComment' => array(
                    'order' => 'WildComment.created DESC',
                    'conditions' => array('WildComment.spam' => 0)
                ),
                'WildUser'
            )
        ));
        
        $goBackAction = $this->referer(array('action' => 'edit', $this->data['WildPost']['id']));
        $this->set('goBackAction', $goBackAction);
    }

    /**
     * RSS feed for posts
     *
     */
    function rss() {
        $this->layout = 'rss/default';
        $posts = $this->WildPost->find('all', array(
             'order' => 'WildPost.created DESC',
             'contain' => 'WildUser',
        ));
        $this->set(compact('posts'));
        $this->RequestHandler->respondAs('text/xml');
    }

    /**
     * Posts index
     * 
     */
    function index() {
    	$this->cacheAction = true;
    	
    	$this->pageTitle = 'Blog';
     
        $this->paginate = array(
	        'limit' => 15,
	        'order' => array('WildPost.created' => 'desc'),
			'conditions' => 'WildPost.draft = 0'
	    );
        
	    $posts = $this->paginate($this->modelClass);
	    
        if (isset($this->params['requested'])) {
            return $posts;
        }
        $this->set('posts', $posts);
    }

    /**
     * View a post
     * 
     * @param string $slug
     */
    function view() {
        $this->acceptComment();
        
		if (Configure::read('AppSettings.cache') == 'on') {
            $this->cacheAction = 60 * 60 * 24 * 3; // Cache for 3 days
        }

        $uuid = Sanitize::paranoid($this->params['uuid']);
        $post = $this->WildPost->findByUuidAndDraft($uuid, 0);

		if (empty($post)) {
			return $this->do404();
		}
        
        // Post title
        $this->pageTitle = $post[$this->modelClass]['title'];
        
        if (isset($this->params['requested'])) {
            return $post;
        }
        
        $this->set(array(
            'post' => $post,
            'descriptionMetaTag' => $post[$this->modelClass]['description_meta_tag']
        ));
    }

    /**
     * Allow an action to accept a comment submit
     *
     * @return void
     */
    function acceptComment() {
        if (empty($this->data)) return;

        $this->WildPost->WildComment->spamCheck = true;
        if ($this->WildPost->WildComment->save($this->data)) {
            $this->Session->setFlash('Comment succesfuly added.');
            $postId = intval($this->data['WildComment']['wild_post_id']);
            $postSlug = $this->WildPost->field('slug', "id = $postId");
            $postLink = '/' . Configure::read('Wildflower.blogIndex') . "/$postSlug";

            // Clear post cache
            // @TODO find out better method
            // $cacheName = str_replace('-', '_', $postSlug);
            // clearCache($cacheName, 'views', '.php');

            $this->redirect($this->data['WildPost']['permalink'] . '#comment-' . $this->WildPost->WildComment->id);
        }
    }
}
