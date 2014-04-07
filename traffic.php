<?php
/**
 * @copyright	Copyright (c) 2014 traffic. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * System - traffic Plugin
 *
 * @package		Joomla.Plugin
 * @subpakage	traffic.traffic
 */
class plgSystemtraffic extends JPlugin {

	/**
	 * Constructor.
	 *
	 * @param 	$subject
	 * @param	array $config
	 */
	function __construct(&$subject, $config = array()) {
		// call parent constructor
		parent::__construct($subject, $config);
	}
        
        function onBeforeCompileHead() {
            $app =& JFactory::getApplication();
            $disable_cache = isset($_GET['nocache']);
            if ($app->isSite() && ! $disable_cache) {
                    $reset_cache = isset($_GET['resetcache']);
                    $no_js_cache = isset($_GET['nojscache']);
                    $no_js_min = isset($_GET['nojsmin']);
                    $no_css_cache = isset($_GET['nocsscache']);
                    $no_css_min = isset($_GET['nocssmin']);
                    $http_url_reference = md5($_SERVER['REQUEST_URI']);
                    if (is_file(JPATH_PLUGINS . '/system/traffic/cssmin.php') && ! $no_css_cache) $cssmin_include = TRUE; else $cssmin_include = FALSE;
                    if (is_file(JPATH_PLUGINS . '/system/traffic/jsmin.php') && ! $no_js_cache) $jsmin_include = TRUE; else $jsmin_include = FALSE;
                    if ($reset_cache) {
                            $handle = dir(JPATH_ROOT . DIRECTORY_SEPARATOR .'cache/'); 
                            while($entry = $handle->read()) { 
                                if ($entry != '.' && $entry != '..') unlink(JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache/' . $entry);
                            } 
                            $handle->close(); 

                    }  
                    if ($cssmin_include) $this->cssmin($http_url_reference, $no_css_min);
                    if ($jsmin_include) $this->jsmin($http_url_reference, $no_js_min);
            }
        }
   
        function cssmin($http_url_reference = NULL, $cssmin = TRUE) {
            $document =& JFactory::getDocument();
            // Generate stylesheet links
            //check if index.css does exist in cache folder
            include_once JPATH_PLUGINS . '/system/traffic/cssmin.php';
            $index_css_pointer = JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache/index.'.$http_url_reference.'.css';
            $index_css_exists = is_file($index_css_pointer);
            $external_css = array();
            $internal_css = array();
            // removes all the css pointers and splits them by his type
            foreach ($document->_styleSheets as $strSrc => $strAttr) {
                if (preg_match("/http/i", $strSrc)) {
                    // css coming from external source. Nothing to do with them
                    $external_css[] = $strSrc;
                    continue;
                }
                // css provided by the core of joomla and also from user-modified extra features installed. we should do something about it
                $internal_css[] = $strSrc;
            }
            if ( ! $index_css_exists ) {
                    $handle = fopen($index_css_pointer, "w+");
                    foreach ($internal_css as $strSrc) {
                        $content = file_get_contents(JPATH_ROOT . DIRECTORY_SEPARATOR . $strSrc);
                        if (!$cssmin) {
                            // lets compress the css
                            $strSrc = explode("/", $strSrc);
                            array_pop($strSrc);
                            $strSrc = \implode("/", $strSrc) . '/';
                            CssMin::setVerbose(true);
                            $content = CssMin::minify(preg_replace('/url\([\"\'](.*)[\"\']\)/i','url(\''.$strSrc.'$1\')',$content));
                        }
                        fwrite($handle, $content. "\n");
                    }
                    fclose($handle);
            }
            $document->_styleSheets = array(
                'cache/index.'.$http_url_reference.'.css' => array(
                    'mime' => 'text/css',
                    'media' => NULL,
                    'attribs' => array()
                )
            );
            foreach($external_css as $item) {
                $document->_styleSheets[$item] = array(
                    'mime' => 'text/css',
                    'media' => NULL,
                    'attribs' => array()
                );
            }
        }
        
        function jsmin($http_url_reference = NULL, $jsmin = TRUE) {
            $document =& JFactory::getDocument();
            // Generate script file links
            include_once JPATH_PLUGINS . '/system/traffic/jsmin.php';
            $index_js_pointer = JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache/index.'.$http_url_reference.'.js';
            $index_js_exists = is_file($index_js_pointer); 
            $external_js = array();
            $internal_js = array();
            foreach ($document->_scripts as $strSrc => $strAttr) {
                if (preg_match("/http/i", $strSrc)) {
                    $external_js[] = $strSrc;
                    continue;
                }
                $internal_js[] = $strSrc;
            };
            if ( ! $index_js_exists ) {
                $handle = fopen($index_js_pointer, "w+");
                foreach ($internal_js as $strSrc) {
                    $content = file_get_contents(JPATH_ROOT . DIRECTORY_SEPARATOR . $strSrc);
                    if (!$jsmin) $content = JShrink\Minifier::minify($content);
                    fwrite($handle, $content. "\n");
                }
                fclose($handle);
            }
            $document->_scripts = array(
                'cache/index.'.$http_url_reference.'.js' => array(
                    'mime' => 'text/javascript',
                    'defer' => NULL,
                    'async' => NULL
                )
            );
            foreach($external_js as $item) {
                $document->_scripts[$item] = array(
                    'mime' => 'text/javascript',
                    'defer' => NULL,
                    'async' => NULL
                );
            }
        }
	
}