<?php
class WildPage extends AppModel {

	public $actsAs = array(
	   'Containable',
	   'Slug' => array('separator' => '-', 'overwrite' => false, 'label' => 'title'),
	   'Tree',
	   'Versionable' => array('title', 'content', 'description_meta_tag', 'keywords_meta_tag')
    );
    public $belongsTo = array('WildUser');
	public $validate = array(
	   'title' => array(
	       'rule' => array('maxLength', 255),
	       'allowEmpty' => false,
	       'required' => true,
	       'on' => 'wf_create'
	    )
	);
	public static $statusOptions = array(
	   '0' => 'Published',
	   '1' => 'Draft'
	);

    /**
     * Find possible parents of a page for select box
     *
     * @deprecated: Use Cake's TreeBehavior::genera...
     * @param int $skipId id to skip
     */
    function getListThreaded($skipId = null, $alias = 'title') {
        $parentPages = $this->findAll(null, null, "{$this->name}.lft ASC", null, 1, 0);

        // Array for form::select
        $selectBoxData = array();
        $skipLeft = false;
        $skipRight = false;

        if (empty($parentPages)) return $selectBoxData;

        $rightNodes = array();
        foreach ($parentPages as $key => $page) {
            $level = 0;
            // Check if we should remove a node from the stack
            while (!empty($rightNodes) && ($rightNodes[count($rightNodes) - 1] < $page[$this->name]['rght'])) {
               array_pop($rightNodes);
            }
            $level = count($rightNodes);

            $dashes = '';
            if ($level > 0) {
                $dashes = str_repeat('&nbsp;', $level) . '-';
            }

            if ($skipId == $page[$this->name]['id']) {
                $skipLeft = $page[$this->name]['lft'];
                $skipRight = $page[$this->name]['rght'];
            } else {
                if (!($skipLeft
                   && $skipRight
                   && $page[$this->name]['lft'] > $skipLeft
                   && $page[$this->name]['rght'] < $skipRight)) {
                       $alias = hsc($page[$this->name]['title']);
                       if (!empty($dashes)) $alias = "$dashes $alias";
                       $selectBoxData[$page[$this->name]['id']] = $alias;

                }
            }

            $rightNodes[] = $page[$this->name]['rght'];
        }

        return $selectBoxData;
    }

    /**
     * Search title and content fields
     *
     * @TODO Create a Search behavior
     *
     * @param string $query
     * @return array
     */
    function search($query) {
        $query = Sanitize::escape($query);
    	$fields = null;
    	$titleResults = $this->findAll("{$this->name}.title LIKE '%$query%'", $fields, null, null, 1);
    	$contentResults = array();
    	if (empty($titleResults)) {
    		$titleResults = array();
			$contentResults = $this->findAll("MATCH ({$this->name}.content) AGAINST ('$query')", $fields, null, null, 1);
    	} else {
    		$alredyFoundIds = join(', ', Set::extract($titleResults, '{n}.' . $this->name . '.id'));
    		$notInQueryPart = '';
    		if (!empty($alredyFoundIds)) {
    			$notInQueryPart = " AND {$this->name}.id NOT IN ($alredyFoundIds)";
    		}
    		$contentResults = $this->findAll("MATCH ({$this->name}.content) AGAINST ('$query')$notInQueryPart", $fields, null, null, 1);
    	}

    	if (!is_array(($contentResults))) {
    		$contentResults = array();
    	}

    	$results = array_merge($titleResults, $contentResults);
    	return $results;
    }

    function getStatusOptions() {
        return self::$statusOptions;
    }

    /**
     * Publish a page (unmark draft status)
     *
     * @param int $id
     */
    function publish($id) {
        $id = intval($id);
        return $this->query("UPDATE {$this->useTable} SET draft = 0 WHERE id = $id");
    }

   /**
     * Mark a page as a draft
     *
     * @param int $id
     */
    function draft($id) {
        $id = intval($id);
        return $this->query("UPDATE {$this->useTable} SET draft = 1 WHERE id = $id");
    }

    function afterSave($arg) {
	    parent::afterSave($arg);

	    // Update root pages cache
        WildflowerRootPagesCache::update($this->findAllRoot());
	}

    /**
     * Before save callback
     *
     * @return bool Success
     */
    function beforeSave() {
        parent::beforeSave();

    	// Construct the absolute page URL
    	if (isset($this->data[$this->name]['slug'])) {
	    	$level = 0;
	    	if (intval($this->id) === intval(Configure::read('AppSettings.home_page_id'))) {
	    		// Home page has the URL of root
	    		$this->data[$this->name]['url'] = '/';
	    	} else if (!isset($this->data[$this->name]['parent_id']) or !is_numeric($this->data[$this->name]['parent_id'])) {
	    	    // Page has no parent
	    	    $this->data[$this->name]['url'] = "/{$this->data[$this->name]['slug']}";
	    	} else {
	    		$parentPage = $this->findById($this->data[$this->name]['parent_id'], array('url'));

	    		$url = "/{$this->data[$this->name]['slug']}";
	    		if ($parentPage[$this->name]['url'] !== '/') {
	    		    $url = $parentPage[$this->name]['url'] . $url;
	    		}

	    		$this->data[$this->name]['url'] = $url;
	    	}
    	}

    	// Publish?
        if (isset($this->data[$this->name]['publish'])) {
            $this->data[$this->name]['draft'] = 0;
            unset($this->data[$this->name]['publish']);
        }

    	return true;
    }

     function findAllRoot() {
        return $this->find('all', array(
            'conditions' => 'parent_id IS NULL',
            'recursive' => -1,
            'fields' => array('id', 'slug', 'url'),
            'order' => 'lft ASC',
        ));
    }

    function updateChildPageUrls($id, $oldUrl, $newUrl) {
        // Update child pages URLs
    	$children = $this->find('all', array(
    	    'conditions' => "{$this->name}.url LIKE '$oldUrl%' AND {$this->name}.id != $id",
    	    'recursive' => -1,
    	    'fields' => array('id', 'url', 'slug'),
    	));
        if (!empty($children)) {
            foreach ($children as $page) {
                        $childNewUrl = str_replace($oldUrl, $newUrl, $page[$this->name]['url']);
                        $this->query("UPDATE {$this->useTable} SET url = '$childNewUrl' WHERE id = {$page[$this->name]['id']}");
            }
        }
    }
}

