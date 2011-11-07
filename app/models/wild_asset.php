<?php
class WildAsset extends AppModel {
    
    public $validate = array(
        // 'file' => array(
        //     'rule' => array('isFileArray'),
        //     'required' => true,
        //     'message' => 'Select a file to upload'
        // )
	);    

    static function getUploadUrl($name) {
	    return '/' . Configure::read('Wildflower.uploadsDirectoryName') . '/' . $name;
	}

    /**
     * Delete one or more files
     *
     * @param mixed $paths
     * @return void
     */
    private function _deleteFiles($paths = array()) {
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    function delete($id) {
	    $upload = $this->findById($id);
	    if (!$upload) return $this->cakeError('object_not_found');

	    // Delete DB record first
	    if (parent::delete($upload[$this->name]['id'])) {
	        $path = Configure::read('Wildflower.uploadDirectory') . DS . $upload[$this->name]['name'];
            $this->_deleteFiles($path);
            return true;
	    }
	    
	    return false;
	}
}

