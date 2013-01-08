<?php

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
global $user, $conf;

// +-----------------------------------------------------------------------+
// |                         comments management                           |
// +-----------------------------------------------------------------------+
// comments deletion
if (isset($_GET['delete_album']) and is_numeric($_GET['delete_album']) and is_admin())
{
    check_status(ACCESS_ADMINISTRATOR);
    check_pwg_token();
    $query = '
DELETE FROM ' . COA_TABLE . '
  WHERE id=' . $_GET['delete_album'] . '
;';
    pwg_query($query);
}

// comments validation
if (isset($_GET['validate_album']) and is_numeric($_GET['validate_album']) and is_admin())
{
    check_status(ACCESS_ADMINISTRATOR);
    check_pwg_token();
    $query = '
UPDATE ' . COA_TABLE . '
  SET validated = \'true\'
  , validation_date = NOW()
  WHERE id=' . $_GET['validate_album'] . '
;';
    pwg_query($query);
}

// +-----------------------------------------------------------------------+
// |                        last comments display                          |
// +-----------------------------------------------------------------------+
$comments = array();
$element_ids = array();
$category_ids = array();
$max_width = 0;
if (!is_admin())
{
  $clauses[] = 'validated="true"';
}
$clauses[] = get_sql_condition_FandF (
    array ('forbidden_categories' => 'category_id',
        'visible_categories' => 'category_id'), '', true);

$query = '
SELECT
    com.id AS comment_id,
    com.category_id,
    com.author,
    com.author_id,
    '.$conf['user_fields']['username'].' AS username,
    com.date,
    com.content,
    com.validated
  FROM '.COA_TABLE.' AS com
    LEFT JOIN '.USERS_TABLE.' As u
      ON u.'.$conf['user_fields']['id'].' = com.author_id
  WHERE '.implode('
    AND ', $clauses).'
  GROUP BY
    comment_id
  ORDER BY date DESC
  LIMIT 0, ' . $datas[0] . '
;';

$result = pwg_query($query);
while ($row = mysql_fetch_assoc($result))
{
  array_push($comments, $row);
  array_push($element_ids, $row['category_id']);
}

if (count($comments) > 0)
{
  $block['TITLE_URL'] = 'comments.php?display_mode=albums';
  $block['comments'] = array();

  // retrieving category informations
  $query = '
SELECT 
    cat.id, 
    cat.name, 
    cat.permalink, 
    cat.uppercats, 
    com.id as comment_id,
    img.id AS image_id,
    img.path,
    img.tn_ext
  FROM '.CATEGORIES_TABLE.' AS cat
    LEFT JOIN '.COA_TABLE.' AS com
      ON com.category_id = cat.id
    LEFT JOIN '.USER_CACHE_CATEGORIES_TABLE.' AS ucc 
      ON ucc.cat_id = cat.id AND ucc.user_id = '.$user['id'].'
    LEFT JOIN '.IMAGES_TABLE.' AS img
      ON img.id = ucc.user_representative_picture_id
  '.get_sql_condition_FandF(
    array(
      'forbidden_categories' => 'cat.id',
      'visible_categories' => 'cat.id'
      ), 
    'WHERE'
    ).'
    AND cat.id IN ('.implode(',', $element_ids).')
;';
  $categories = hash_from_query($query, 'comment_id');

  foreach ($comments as $comment)
  {
    // category url
    $comment['cat_url'] = duplicate_index_url(
      array(
        'category' => array(
          'id' => $categories[$comment['comment_id']]['id'], 
          'name' => $categories[$comment['comment_id']]['name'], 
          'permalink' => $categories[$comment['comment_id']]['permalink'],
          ),
        array('start')
        )
      );
     
    // category thumbnail
    $comment['thumb'] = get_thumbnail_url(
      array(
        'id' => $categories[$comment['comment_id']]['image_id'],
        'path' => $categories[$comment['comment_id']]['path'],
        'tn_ext' => @$categories[$comment['comment_id']]['tn_ext'],
        )
     );

    // author
    $author = $comment['author'];
    if (empty($comment['author']))
    {
      $author = l10n('guest');
    }
    
    // comment content
    $tpl_comment = array(
      'ID' => $comment['comment_id'],
      'U_PICTURE' => $comment['cat_url'],
      'ALT' => trigger_event('render_category_name', $categories[$comment['comment_id']]['name']),
      'TN_SRC' => $comment['thumb'],
      'AUTHOR' => trigger_event('render_comment_author', $author),
      'DATE' => format_date($comment['date'], true),
      'CONTENT' => trigger_event('render_comment_content', $comment['content'], 'album'),
      'WIDTH' => $datas[3],
      'HEIGHT' => $datas[4],
      );

    switch ($datas[2])
    {
      case 1 :
        $tpl_comment['CLASS'] = 'one_comment';
        break;
      case 2 :
        $tpl_comment['CLASS'] = 'two_comment';
        break;
      case 3 :
        $tpl_comment['CLASS'] = 'three_comment';
        break;
    }

    // actions
    if ( is_admin() and $datas[1])
    {
      $url = get_root_url().'index.php'.get_query_string_diff(array('delete_album','validate_album'));
      $tpl_comment['U_DELETE'] = add_url_params($url, array(
            'delete_album' => $comment['comment_id'],
            'pwg_token' => get_pwg_token()));

      if ($comment['validated'] != 'true')
      {
        $tpl_comment['U_VALIDATE'] = add_url_params($url, array(
            'validate_album' => $comment['comment_id'],
            'pwg_token' => get_pwg_token()));
      }
    }
    
    array_push($block['comments'], $tpl_comment);
  }
  $block['TEMPLATE'] = dirname(__FILE__).'/stuffs_lastcoms.tpl';
}

?>