<?php
/*
Plugin Name: Comments on Albums
Version: auto
Description: Activate comments on albums pages
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=512
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

if (mobile_theme())
{
  return;
}

global $prefixeTable;

// +-----------------------------------------------------------------------+
//          Global variables
// +-----------------------------------------------------------------------+
defined('COA_ID') or define('COA_ID', basename(dirname(__FILE__)));
define('COA_PATH' ,   PHPWG_PLUGINS_PATH . COA_ID . '/');
define('COA_TABLE' ,  $prefixeTable . 'comments_categories');
define('COA_ADMIN',   get_root_url().'admin.php?page=plugin-' . COA_ID);
define('COA_VERSION', 'auto');


// +-----------------------------------------------------------------------+
//          Triggers
// +-----------------------------------------------------------------------+
add_event_handler('init', 'coa_init');
function coa_init()
{
  global $user, $conf, $pwg_loaded_plugins;
  
  // luciano doesn't use comments
  // incompatible with dynamic display of Stripped & Collumns
  if ($user['theme'] == 'luciano' or $user['theme'] == 'stripped_black_bloc') return;
  
  // apply upgrade if needed
  if (
    COA_VERSION == 'auto' or
    $pwg_loaded_plugins[COA_ID]['version'] == 'auto' or
    version_compare($pwg_loaded_plugins[COA_ID]['version'], COA_VERSION, '<')
  )
  {
    // call install function
    include_once(COA_PATH . 'include/install.inc.php');
    coa_install();
    
    // update plugin version in database
    if ( $pwg_loaded_plugins[COA_ID]['version'] != 'auto' and COA_VERSION != 'auto' )
    {
      $query = '
UPDATE '. PLUGINS_TABLE .'
SET version = "'. COA_VERSION .'"
WHERE id = "'. COA_ID .'"';
      pwg_query($query);
      
      $pwg_loaded_plugins[COA_ID]['version'] = COA_VERSION;
      
      if (defined('IN_ADMIN'))
      {
        $_SESSION['page_infos'][] = 'Comments on Albums updated to version '. COA_VERSION;
      }
    }
  }
  
  // add events handlers
  if (defined('IN_ADMIN'))
  {
    add_event_handler('loc_begin_admin_page', 'coa_admin_intro');
    add_event_handler('loc_end_admin', 'coa_admin_comments');
  }
  else
  {
    add_event_handler('loc_after_page_header', 'coa_albums');
    add_event_handler('loc_after_page_header', 'coa_comments_page');
  }  
  add_event_handler('get_stuffs_modules', 'coa_register_stuffs_module');
}


// +-----------------------------------------------------------------------+
//          Functions
// +-----------------------------------------------------------------------+

function coa_albums() 
{
  global $template, $page, $conf, $pwg_loaded_plugins;
  
  if ( !empty($page['section']) AND $page['section'] == 'categories' AND isset($page['category']) AND $page['body_id'] == 'theCategoryPage' )
  {    
    trigger_action('loc_begin_coa');
    include(COA_PATH . 'include/coa_albums.php');
  }
}

function coa_comments_page() 
{
  global $template, $page, $conf, $user;
  
  if (isset($page['body_id']) AND $page['body_id'] == 'theCommentsPage') 
  {
    include(COA_PATH . 'include/coa_comments_page.php');
  }
}

function coa_admin_intro() 
{
  global $page;
  
  if ($page['page'] == 'intro') 
  {  
    include(COA_PATH . 'include/coa_admin_intro.php');
  } 
}

function coa_admin_comments() 
{
  global $page;
  
  if ($page['page'] == 'comments') 
  {  
    include(COA_PATH . 'include/coa_admin_comments.php');
  }
}

function coa_register_stuffs_module($modules) 
{
  load_language('plugin.lang', COA_PATH);
  
  array_push($modules, array(
    'path' => COA_PATH . '/stuffs_module',
    'name' => l10n('Last comments on albums'),
    'description' => l10n('Display last posted comments on albums'),
  ));

  return $modules;
}

?>