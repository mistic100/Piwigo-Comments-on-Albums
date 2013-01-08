<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

defined('COA_ID') or define('COA_ID', basename(dirname(__FILE__)));
include_once(PHPWG_PLUGINS_PATH . COA_ID . '/include/install.inc.php');

function plugin_install() 
{
  coa_install();
  define('coa_installed', true);
}

function plugin_activate()
{
  if (!defined('coa_installed'))
  {
    coa_install();
  }
}

function plugin_uninstall() 
{
  global $prefixeTable;

  pwg_query('DROP TABLE `' . $prefixeTable . 'comments_categories`;');
}

?>