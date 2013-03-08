<?php
/* Code adapted from admin/comments.php */
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $template, $conf, $user;
load_language('plugin.lang', COA_PATH);

// +-----------------------------------------------------------------------+
// |                               tabsheet                                |
// +-----------------------------------------------------------------------+
include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');

if (isset($_GET['section']) and $_GET['section'] == 'albums')
{
  $page['tab'] = 'albums';
} 
else 
{
  $page['tab'] = 'pictures';
}

$tabsheet = new tabsheet();
$tabsheet->add('pictures', l10n('Comments on photos'), get_root_url().'admin.php?page=comments');
$tabsheet->add('albums', l10n('Comments on albums'), get_root_url().'admin.php?page=comments&amp;section=albums');
$tabsheet->select($page['tab']);
$tabsheet->assign();


if ($page['tab'] == 'albums') 
{
  // clear template sended by original page
  $template->clear_assign(array('ADMIN_CONTENT', 'comments', 'LIST', 'F_ACTION'));

  // +-----------------------------------------------------------------------+
  // |                                actions                                |
  // +-----------------------------------------------------------------------+
  if (!empty($_POST)) 
  {
    if (empty($_POST['comments']))
    {
      array_push(
        $page['errors'],
        l10n('Select at least one comment')
        );
    }
    else
    {
      include_once(COA_PATH.'include/functions_comment.inc.php'); // custom functions
      check_input_parameter('comments', $_POST, true, PATTERN_ID);

      if (isset($_POST['validate_albums'])) 
      {
        validate_user_comment_albums($_POST['comments']);

        array_push(
          $page['infos'], 
          l10n_dec(
            '%d user comment validated', '%d user comments validated',
            count($_POST['comments'])
            )
          );
      }

      if (isset($_POST['reject_albums'])) 
      {
        delete_user_comment_albums($_POST['comments']);

        array_push(
          $page['infos'], 
          l10n_dec(
            '%d user comment rejected', '%d user comments rejected',
            count($_POST['comments'])
            )
          );
      }
    }
  }

  // +-----------------------------------------------------------------------+
  // |                             template init                             |
  // +-----------------------------------------------------------------------+

  $template->set_filename('comments', dirname(__FILE__) .'/../template/admin_comments.tpl');

  $template->assign(
    array(
      'F_ACTION' => get_root_url().'admin.php?page=comments&amp;section=albums'
      )
    );
    
  if (count($page['infos']) != 0) 
  {
    $template->assign('infos', $page['infos']);
  }

  // +-----------------------------------------------------------------------+
  // |                           comments display                            |
  // +-----------------------------------------------------------------------+
  $list = array();

  $query = '
SELECT 
    com.id, 
    com.category_id, 
    com.date, 
    com.author,
    u.'.$conf['user_fields']['username'].' AS username, 
    com.content, 
    cat.name,
    img.id AS image_id,
    img.path
  FROM '.COA_TABLE.' AS com
    LEFT JOIN '.CATEGORIES_TABLE.' AS cat
      ON cat.id = com.category_id
    LEFT JOIN '.USERS_TABLE.' AS u
      ON u.'.$conf['user_fields']['id'].' = com.author_id
    LEFT JOIN '.USER_CACHE_CATEGORIES_TABLE.' AS ucc 
      ON ucc.cat_id = com.category_id AND ucc.user_id = '.$user['id'].'
    LEFT JOIN '.IMAGES_TABLE.' AS img
      ON img.id = ucc.user_representative_picture_id
  WHERE validated = \'false\'
  ORDER BY com.date DESC
;';
  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result)) 
  {
    // author
    if (empty($row['author_id'])) 
    {
      $author_name = $row['author'];
    } 
    else 
    {
      $author_name = stripslashes($row['username']);
    }
    
    // thumbnail
    $row['thumb'] = DerivativeImage::thumb_url(
      array(
        'id'=>$row['image_id'],
        'path'=>$row['path'],
        )
     );

    // comment content
    $template->append(
      'comments', 
      array(
        'ID' => $row['id'],
        'CAT_URL' => PHPWG_ROOT_PATH.'admin.php?page=album-'.$row['category_id'],
        'CAT_NAME' => trigger_event('render_category_name', $row['name']),
        'TN_SRC' => $row['thumb'],
        'AUTHOR' => trigger_event('render_comment_author', $author_name),
        'DATE' => format_date($row['date'], true),
        'CONTENT' => trigger_event('render_comment_content', $row['content'], 'album'),
        )
      );

    array_push($list, $row['id']);
  }

  $template->assign('LIST', implode(',', $list));

  // +-----------------------------------------------------------------------+
  // |                           sending html code                           |
  // +-----------------------------------------------------------------------+

  $template->assign_var_from_handle('ADMIN_CONTENT', 'comments');

}

?>