<?php
/* adds info about comments count on main admin page, uses javascript */
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $template;

// comments count
$query = '
SELECT COUNT(*)
  FROM '.COA_TABLE.'
;';
list($nb_comments) = pwg_db_fetch_row(pwg_query($query));

$template->assign(
  'DB_COMMENTS_ALBUMS', 
  l10n_dec('%d comment', '%d comments', $nb_comments)
  );

// unvalidated comments
$query = '
SELECT COUNT(*)
  FROM '.COA_TABLE.'
  WHERE validated=\'false\'
;';
list($nb_comments) = pwg_db_fetch_row(pwg_query($query));

if ($nb_comments > 0) 
{
  $template->assign(
    'unvalidated_albums', 
    array(
      'URL' => PHPWG_ROOT_PATH.'admin.php?page=comments&amp;section=albums',
      'INFO' => sprintf(l10n('%d waiting for validation'), $nb_comments)
      )
    );
}

$template->set_prefilter('intro', 'coa_admin_intro_prefilter');

function coa_admin_intro_prefilter($content, &$smarty)
{
  $search = '(<a href="{$unvalidated.URL}">{$unvalidated.INFO}</a>)';
        
  $replace = $search.'
        {/if}
        [{\'Photos\'|@translate}]
      </li>
      <li>
        {$DB_COMMENTS_ALBUMS}
        {if isset($unvalidated_albums)}
        (<a href="{$unvalidated_albums.URL}">{$unvalidated_albums.INFO}</a>)
        {/if}
        [{\'Albums\'|@translate}]
      {if true}';

  return str_replace($search, $replace, $content);
}

?>