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
            $document =& JFactory::getDocument();
            $disable_cache = isset($_GET['nocache']);
            if ($app->isSite() && ! $disable_cache) {
                    $reset_cache = isset($_GET['resetcache']);
                    $no_js_cache = isset($_GET['nojscache']);
                    $no_js_min = isset($_GET['nojsmin']);
                    $no_css_cache = isset($_GET['nocsscache']);
                    $no_css_min = !isset($_GET['nocssmin']);
                    $http_url_reference = md5(JURI::current());
                    if ($reset_cache) {
                            $handle = dir(JPATH_ROOT . DIRECTORY_SEPARATOR .'cache/'); 
                            while($entry = $handle->read()) { 
                                if ($entry != '.' && $entry != '..') unlink(JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache/' . $entry);
                            } 
                            $handle->close();
                    }
                    if (!$no_css_cache) $this->cssmin($http_url_reference, $no_css_min, $document);
                    if (!$no_js_cache) $this->jsmin($http_url_reference, $no_js_min, $document);
            }
        }
   
        function cssmin($http_url_reference = NULL, $no_css_min = TRUE, $document = NULL) {
            // Generate stylesheet links
            //check if index.css does exist in cache folder
            include_once JPATH_PLUGINS . '/system/traffic/cssmin.php';
            $index_css_pointer = JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'index.'.$http_url_reference.'.css';
            $index_css_exists = is_file($index_css_pointer);
            $internal_sources = array();
            $external_sources = array();
            foreach ($document->_styleSheets as $strSrc => $strAttr) {
                if (preg_match("/http/i", $strSrc)) $external_sources[] = $strSrc;
                else $internal_sources[] = $strSrc;
            }
            
            if ( ! $index_css_exists ) {
                $handle = fopen($index_css_pointer, "w+");
                foreach ($internal_sources as $strSrc) {
                    $content = file_get_contents(JPATH_ROOT . DIRECTORY_SEPARATOR . $strSrc);
                    $strSrc = explode("/", $strSrc);
                    array_pop($strSrc);
                    $strSrc = implode("/", $strSrc) . '/';
                    $content = preg_replace('/url\([\"\'](.*)[\"\']\)/i','url(\''.$strSrc.'$1\')',$content);
                    fwrite($handle, $content. "\n\r");
                }
                fclose($handle);
                if (!$no_css_min) file_put_contents($index_css_pointer, CssMin::minify(file_get_contents($index_css_pointer)));
            }            
            $document->_styleSheets = array();
            $document->_styleSheets['cache/index.'.$http_url_reference.'.css'] = array(
                'mime' => 'text/css',
                'defer' => NULL,
                'async' => NULL
            );
            foreach($external_sources as $strSrc) {
               $document->_styleSheets[$strSrc] = array(
                    'mime' => 'text/css',
                    'defer' => NULL,
                    'async' => NULL
                ); 
            };
        }
        
        function jsmin($http_url_reference = NULL, $no_js_min = TRUE, $document = NULL) {
            // Generate script file links
            include_once JPATH_PLUGINS . '/system/traffic/jsmin.php';
            $index_js_pointer = JPATH_ROOT . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'index.'.$http_url_reference.'.js'; 
            $internal_sources = array();
            $external_sources = array();
            foreach ($document->_scripts as $strSrc => $strAttr) {
                if (preg_match("/http/i", $strSrc)) $external_sources[] = $strSrc;
                else $internal_sources[] = $strSrc;
            }
            if ( ! is_file($index_js_pointer) ) {
                $handle = fopen($index_js_pointer, "w+");
                foreach ($internal_sources as $strSrc) {
                    $content = file_get_contents(JPATH_ROOT . DIRECTORY_SEPARATOR . $strSrc);
                    fwrite($handle, $content. "\n\r");
                }
                fclose($handle);
                if (!$no_js_min) file_put_contents($index_js_pointer, JShrink\Minifier::minify(file_get_contents($index_js_pointer)));
            }
            $document->_scripts = array();
            $document->_scripts['cache/index.'.$http_url_reference.'.js'] = array(
                'mime' => 'text/javascript',
                'defer' => NULL,
                'async' => NULL
            );
            foreach($external_sources as $strSrc) {
               $document->_scripts[$strSrc] = array(
                    'mime' => 'text/javascript',
                    'defer' => NULL,
                    'async' => NULL
                ); 
            };
        }
	
}