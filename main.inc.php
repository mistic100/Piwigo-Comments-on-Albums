<?php
/*
Plugin Name: Comments on Albums
Version: auto
Description: Activate comments on albums pages
Plugin URI: auto
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (basename(dirname(__FILE__)) != 'Comments_on_Albums')
{
  add_event_handler('init', 'coa_error');
  function coa_error()
  {
    global $page;
    $page['errors'][] = 'Comments on Albums folder name is incorrect, uninstall the plugin and rename it to "Comments_on_Albums"';
  }
  return;
}

if (mobile_theme())
{
  return;
}

global $prefixeTable;

define('COA_PATH' ,  PHPWG_PLUGINS_PATH . 'Comments_on_Albums/');
define('COA_TABLE' , $prefixeTable . 'comments_categories');
define('COA_ADMIN',  get_root_url().'admin.php?page=plugin-Comments_on_Albums');


add_event_handler('init', 'coa_init');


function coa_init()
{
  global $user, $conf;

  // luciano doesn't use comments
  // incompatible with dynamic display of Stripped & Collumns
  if ($user['theme'] == 'luciano' or $user['theme'] == 'stripped_black_bloc') return;

  include_once(COA_PATH . 'include/events.inc.php');

  if (defined('IN_ADMIN'))
  {
    add_event_handler('tabsheet_before_select', 'coa_tabsheet_before_select', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
    add_event_handler('loc_begin_admin_page', 'coa_admin_intro');
  }
  else
  {
    add_event_handler('loc_after_page_header', 'coa_albums');
    add_event_handler('loc_end_comments', 'coa_comments');
  }

  add_event_handler('get_stuffs_modules', 'coa_register_stuffs_module');

  load_language('plugin.lang', COA_PATH);
}
