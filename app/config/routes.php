<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.ctp)...
 */

/**
 * Wildflower admin routes
 *
 * Changing Wildflower.prefix in app/plugins/wildflower/config/core.php allows you
 * to change the WF admin url. After this access the admin under /your-prefix.
 */

Router::connect('/', array('controller' => 'wild_pages', 'action' => 'view'));

$prefix = Configure::read('Wildflower.prefix');

$wfControllers = array('pages', 'posts', 'dashboards', 'users', 'categories', 'comments', 'assets', 'messages', 'uploads', 'settings', 'utilities', 'widgets');
foreach ($wfControllers as $shortcut) {
	Router::connect(
		"/$prefix/$shortcut", 
		array('controller' => "wild_$shortcut", 'action' => 'index', 'prefix' => 'wf')
	);
	
	Router::connect(
		"/$prefix/$shortcut/:action/*", 
		array('controller' => "wild_$shortcut", 'prefix' => 'wf')
	);
}

// Login screen
Router::connect("/$prefix/login", array('controller' => 'wild_users', 'action' => 'login'));

Router::connect("/$prefix", array('controller' => 'wild_dashboards', 'action' => 'index', 'prefix' => 'wf'));

// Image thumbnails
Router::connect('/wildflower/thumbnail/*', array('controller' => 'wild_assets', 'action' => 'thumbnail'));

// Contact form
Router::connect('/contact', array('controller' => 'wild_messages', 'action' => 'index'));

// Ultra sexy short SEO friendly post URLs in form of http://my-domain/p/40-char-uuid
Router::connect('/' . Configure::read('Wildflower.postsParent') . '/:uuid', array('controller' => 'wild_posts', 'action' => 'view'));
Router::connect('/' . Configure::read('Wildflower.blogIndex') . '/page::page', array('controller' => 'wild_posts', 'action' => 'index'));
Router::connect('/' . Configure::read('Wildflower.blogIndex'), array('controller' => 'wild_posts', 'action' => 'index'));
Router::connect('/' . Configure::read('Wildflower.blogIndex') . '/rss', array('controller' => 'wild_posts', 'action' => 'rss'));

WildflowerRootPagesCache::connect();
/**
 * Wildflower root pages routes cache API
 * 
 * Pages without a parent are each passed to Route::connect().
 *
 * @package wildflower
 */
class WildflowerRootPagesCache {
    
    static function connect() {
        $file = Configure::read('Wildflower.rootPageCache');
        $rootPages = array();
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $rootPages = json_decode($content, true);
        } else {
            $rootPages = self::update();
        };
        
        if (!is_array($rootPages)) {
            $rootPages = self::update();
        }

        foreach ($rootPages as $page) {
            // Root page
            Router::connect(
        		$page['WildPage']['url'], 
        		array('controller' => "wild_pages", 'action' => 'view', 'id' => $page['WildPage']['id'])
        	);
        	// It's children
        	$children = $page['WildPage']['url'] . '/*';
            Router::connect(
                $children, 
                array('controller' => 'wild_pages', 'action' => 'view')
            );
        }
    }
    
    static function update() {
        return Router::requestAction(array('controller' => 'wild_pages', 'action' => 'update_root_cache'), array('return' => 1));
    }
    
    static function write($rootPages = array()) {
        $content = json_encode($rootPages);
        $file = Configure::read('Wildflower.rootPageCache');
        return file_put_contents($file, $content);
    }
    
}

?>
