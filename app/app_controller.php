<?php
App::import('Core', 'l10n');

class AppController extends Controller {

	public $components = array('Auth', 'Cookie', 'RequestHandler', 'Seo');
    public $helpers = array('Html', 'Form', 'Javascript', 'PartialLayout', 'Htmla', 'Navigation', 'Wild');
	public $isAuthorized = false; 
    public $isHome = false;
    public $homePageId;
	
	/**
     * Called before any controller action
     * 
     * Do 3 things:
     *   1. protect admin area
     *   2. check for user sessions
     *   3. set site parameters
     */
    function beforeFilter() {
      
        // AuthComponent settings
        $this->Auth->userModel = 'WildUser';
        $this->Auth->fields = array('username' => 'login', 'password' => 'password');
        $prefix = Configure::read('Wildflower.prefix');
        $this->Auth->loginAction = "/$prefix/login";
        $this->Auth->logoutAction = array('plugin' => 'wildflower', 'prefix' => $prefix, 'controller' => 'wild_users', 'action' => 'logout');
        $this->Auth->autoRedirect = false;
        $this->Auth->allow('update_root_cache'); // requestAction() actions need to be allowed
        $this->Auth->loginRedirect = "/$prefix";

        $this->_configureSite();
                
		// Admin area requires authentification
		if ($this->isAdminAction()) {
			// Set admin layout and admin specific view vars
			$this->layout = 'admin_default';
		} else {
			$this->layout = 'default';
			$this->Auth->allow('*');
		}
		$this->isAuthorized = $this->Auth->isAuthorized();
		
		// Internationalization
		$this->L10n = new L10n();
        $this->L10n->get('eng');
        Configure::write('Config.language', 'en');

        $this->homePageId = intval(Configure::read('AppSettings.home_page_id'));

		// Set cookie defaults
		$this->cookieName = Configure::read('Wildflower.cookie.name');
		$this->cookieTime = Configure::read('Wildflower.cookie.expire');
		$this->cookieDomain = '.' . getenv('SERVER_NAME');

		// Compress output to save bandwith / speed site up
		if (!isset($this->params['requested']) && Configure::read('Wildflower.gzipOutput')) {
		    $this->gzipOutput();
		}
    }

    /**
     * Before rendering
     * 
     * Set nice SEO titles.
     */
    function beforeRender() {
    	
        $this->Seo->title();

    	// Set view parameters (CmsHelper uses some of these for example)
        $params = array(
            'siteName' => Configure::read('AppSettings.site_name'),
            'siteDescription' => Configure::read('AppSettings.description'),
            'isLogged' => $this->isAuthorized,
            'isAuthorized' => $this->isAuthorized,
            'homePageId' => $this->homePageId,
            'here' => substr($this->here, strlen($this->base) - strlen($this->here)),
        );
        $this->set($params);
    }

    /**
     * Tell wheather the current action should be protected
     *
     * @return bool
     */
    function isAdminAction() {
        $adminRoute = Configure::read('Routing.admin');
        $wfPrefix = Configure::read('Wildflower.prefix');
        if (isset($this->params[$adminRoute]) && $this->params[$adminRoute] === $wfPrefix) return true;
        return (isset($this->params['prefix']) && $this->params['prefix'] === $wfPrefix);
    }

    /**
	 * Write all site settings to Configure class as key => value pairs.
	 * Access them anywhere in the application with Configure::read().
	 *
	 */
	private function _configureSite() {
		$settings = ClassRegistry::init('WildSetting')->getKeyValuePairs();
        Configure::write('AppSettings', $settings); // @TODO add under Wildlfower. configure namespace
        Configure::write('Wildflower.settings', $settings); // The new namespace for WF settings
	}

    /**
	 * Gzip output
	 * 
	 * Cuts the bandwith cost down to half.
	 * Helps the responce time.
	 */
	function gzipOutput() {
		if (@ob_start('ob_gzhandler')) {
            header('Content-type: text/html; charset: UTF-8');
			header('Cache-Control: must-revalidate');
			$offset = -1;
			$expireTime = gmdate('D, d M Y H:i:s', time() + $offset);
			$expireHeader = "Expires: $expireTime GMT";
			header($expireHeader);
		}
	}

     /**
     * Update more records at once
     *
     * @TODO Could be much faster using custom UPDATE or DELETE queries
     */
    function wf_mass_update() {
        if (isset($this->data['__action'])) {
            foreach ($this->data['id'] as $id => $checked) {
                if (intval($checked) === 1) {
                    switch ($this->data['__action']) {
                        case 'delete':
                            // Delete with comments
                            $this->{$this->modelClass}->delete($id);
                            break;
                        case 'publish':                   
                            $this->{$this->modelClass}->publish($id);
                            break;
                        case 'draft':
                            $this->{$this->modelClass}->draft($id);
                            break;
                    }
                }
            }
        }
        
    	$link = am($this->params['named'], array('action' => 'wf_index'));
        return $this->redirect($link);
    }
	
     /**
     * Admin search
     *
     * @param string $query Search term, encoded by Javascript's encodeURI()
     */
    function wf_search($query = '') {
        $query = urldecode($query);
        $results = $this->{$this->modelClass}->search($query);
        $this->set('results', $results);
        $this->render('/wild_dashboards/wf_search');
    }

    function getLoggedInUserId() {
        return $this->Auth->user('id');
    }

    function do404() {
		$this->pageTitle = 'Page not found';
        
        $this->cakeError('error404', array(array(
                'message' => 'Requested page was not found.',
                'base' => $this->base)));
	}

    /**
	 * @TODO duplicate in AppHelper
	 * Returns a string with all spaces converted to $replacement and non word characters removed.
	 *
	 * @param string $string
	 * @param string $replacement
	 * @return string
	 * @static
	 */
    static function slug($string, $replacement = '-') {
    	$string = trim($string);
        $map = array(
            '/à|á|å|â|ä/' => 'a',
            '/è|é|ê|ẽ|ë/' => 'e',
            '/ì|í|î/' => 'i',
            '/ò|ó|ô|ø/' => 'o',
            '/ù|ú|ů|û/' => 'u',
            '/ç|č/' => 'c',
            '/ñ|ň/' => 'n',
            '/ľ/' => 'l',
            '/ý/' => 'y',
            '/ť/' => 't',
            '/ž/' => 'z',
            '/š/' => 's',
            '/æ/' => 'ae',
            '/ö/' => 'oe',
            '/ü/' => 'ue',
            '/Ä/' => 'Ae',
            '/Ü/' => 'Ue',
            '/Ö/' => 'Oe',
            '/ß/' => 'ss',
            '/[^\w\s]/' => ' ',
            '/\\s+/' => $replacement,
            String::insert('/^[:replacement]+|[:replacement]+$/', 
            array('replacement' => preg_quote($replacement, '/'))) => '',
        );
        $string = preg_replace(array_keys($map), array_values($map), $string);
        return low($string);
    }
}
