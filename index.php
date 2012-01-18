<?php

/*
 * Funky Cache - Frog CMS caching plugin
 *
 * Copyright (c) 2008-2009 Mika Tuupola
 * Modified by Matthew Sowden for WolfCMS
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/funky_cache
 *
 * @package Plugins
 * @subpackage funky_cache
 */
 
require_once 'models/FunkyCachePage.php';

Plugin::setInfos(array(
    'id'          => 'funky_cache',
    'title'       => 'Funky Cache', 
    'description' => 'Enables funky caching which makes your site ultra fast.', 
    'version'     => '0.4.0-devwolf', 
    'license'     => 'MIT',
    'author'      => 'Matthew Sowden (Original: Mika Tuupola)',
    'update_url'  => 'http://www.resbah.com/check/wolfplugins.xml',
    'require_wolf_version' => '0.7.5',
    'website'     => 'http://www.resbah.com/'
));

/* Stuff for backend. */
    AutoLoader::addFolder(dirname(__FILE__) . '/lib');
    
	Plugin::addController('funky_cache', 'Cache', 'administrator', false);
    
    #Observer::observe('page_edit_after_save',   'funky_cache_delete_one');
    Observer::observe('page_edit_after_save',   'funky_cache_delete_all');
    Observer::observe('page_add_after_save',    'funky_cache_delete_all');
    Observer::observe('page_delete',            'funky_cache_delete_all');
    Observer::observe('view_page_edit_plugins', 'funky_cache_show_select');
    
    Observer::observe('comment_after_add',       'funky_cache_delete_all');
    Observer::observe('comment_after_edit',      'funky_cache_delete_all');
    Observer::observe('comment_after_delete',    'funky_cache_delete_all');
    Observer::observe('comment_after_approve',   'funky_cache_delete_all');
    Observer::observe('comment_after_unapprove', 'funky_cache_delete_all');
    
    /* These currently only work in MIT fork of Frog. */
    Observer::observe('layout_after_edit',      'funky_cache_delete_all');
    Observer::observe('snippet_after_edit',     'funky_cache_delete_all');
    
    /* TODO Fix this to work with configurable cache folder. */
    function funky_cache_delete_one($page) {
        $data['url'] = '/' . $page->getUri() . URL_SUFFIX;
        if (($cache = Record::findOneFrom('FunkyCachePage', 'url=?', array($data['url'])))) {
            $cache->delete();
        }
    }

    function funky_cache_delete_all() {
        $cache = Record::findAllFrom('FunkyCachePage');
        foreach ($cache as $page) {
            $page->delete();
        }
        $message = sprintf('Cache was automatically cleared.');
        Observer::notify('log_event', $message, 'funky_cache', 7);
    }
    
    function funky_cache_show_select($page) {
        $enabled = isset($page->funky_cache_enabled) ? 
                         $page->funky_cache_enabled  : funky_cache_by_default();
        print '
          <p><label for="page_funky_cache_enabled">'.__('Should cache').'</label>
            <select id="page_funky_cache_enabled" name="page[funky_cache_enabled]">
              <option value="0"'.($enabled == 0 ? ' selected="selected"': '').'>'.__('No').'</option>
              <option value="1"'.($enabled == 1 ? ' selected="selected"': '').'>'.__('Yes').'</option>
             </select>
          </p>';
    }
        
     if( strstr("/".CURRENT_URI,"admin/")==FALSE ){
/* Stuff for frontend. */    

    global $__FROG_CONN__;
    Record::connection($__FROG_CONN__);
    
    Observer::observe('page_found',           'funky_cache_create');
    Observer::observe('page_requested',       'funky_cache_debug');

    function funky_cache_debug($page) {
        if (DEBUG) {
            print "Cache miss...";            
        }
    }

    function funky_cache_create($page) {
        if ($page->funky_cache_enabled) {
            funky_cache_suffix();
            #$data['url'] = "/" . $_SERVER['QUERY_STRING'];
            $data['url'] = "/" . CURRENT_URI;
            
            /* Frontpage should become index.html */
            if ('/' . URL_SUFFIX == $data['url'] . URL_SUFFIX) {
                $data['url'] = '/index' . funky_cache_suffix(); 
            /* If Frog suffix is not used, use suffix from cache settings */
            /* For example /articles becomes /articles.html */
            } else {
                $data['url'] = '/'. CURRENT_URI . funky_cache_suffix();
            }
            $data['url'] = funky_cache_folder() . $data['url'];
            $data['url'] = preg_replace('#//#', '/', $data['url']);
            $data['page'] = $page;
            if (!($cache = Record::findOneFrom('FunkyCachePage', 'url=?', array($data['url'])))) {
                $cache = new FunkyCachePage($data);          
            }
            $cache->page = $page;
            $cache->save();            
        }
    }
}
function funky_cache_suffix() {
    return Setting::get('funky_cache_suffix');
}

function funky_cache_by_default() {
    return Setting::get('funky_cache_by_default');
}

function funky_cache_folder() {
	$folder = '/' . Setting::get('funky_cache_folder') . '/';
	$folder = preg_replace('#//*#', '/', $folder);
    return $folder;
}

function funky_cache_folder_is_root() {
    return '/' == funky_cache_folder();
}