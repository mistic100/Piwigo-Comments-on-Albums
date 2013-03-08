<?php
/* This is a copy of include/functions_comment.inc.php but adapted for Comments On Albums */

include_once(PHPWG_ROOT_PATH.'include/functions_comment.inc.php');
add_event_handler('user_comment_check_albums', 'user_comment_check',
  EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

/**
 * Tries to insert a user comment in the database and returns one of :
 * validate, moderate, reject
 * @param array comm contains author, content, category_id
 * @param string key secret key sent back to the browser
 * @param array infos out array of messages
 */
function insert_user_comment_albums( &$comm, $key, &$infos )
{
  global $conf, $user;

  $comm = array_merge( $comm,
    array(
      'ip' => $_SERVER['REMOTE_ADDR'],
      'agent' => $_SERVER['HTTP_USER_AGENT']
    )
   );

  $infos = array();
  if (!$conf['comments_validation'] or is_admin())
  {
    $comment_action='validate'; //one of validate, moderate, reject
  }
  else
  {
    $comment_action='moderate'; //one of validate, moderate, reject
  }

  // display author field if the user status is guest or generic
  if (!is_classic_user())
  {
    if ( empty($comm['author']) )
    {
      if ($conf['comments_author_mandatory'])
      {
        array_push($infos, l10n('Username is mandatory') );
        $comment_action='reject';
      }
      $comm['author'] = 'guest';
    }
    $comm['author_id'] = $conf['guest_id'];
    // if a guest try to use the name of an already existing user, he must be
    // rejected
    if ( $comm['author'] != 'guest' )
    {
      $query = '
SELECT COUNT(*) AS user_exists
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['username']." = '".addslashes($comm['author'])."'";
      $row = pwg_db_fetch_assoc( pwg_query( $query ) );
      if ( $row['user_exists'] == 1 )
      {
        array_push($infos, l10n('This login is already used by another user') );
        $comment_action='reject';
      }
    }
  }
  else
  {
    $comm['author'] = addslashes($user['username']);
    $comm['author_id'] = $user['id'];
  }

  if ( empty($comm['content']) )
  { // empty comment content
    $comment_action='reject';
  }

  if ( !verify_ephemeral_key(@$key, $comm['category_id']) )
  {
    $comment_action='reject';
    $_POST['cr'][] = 'key';
  }
  
  // website
  if (!empty($comm['website_url']))
  {
    if (!preg_match('/^https?/i', $comm['website_url']))
    {
      $comm['website_url'] = 'http://'.$comm['website_url'];
    }
    if (!url_check_format($comm['website_url']))
    {
      array_push($infos, l10n('Your website URL is invalid'));
      $comment_action='reject';
    }
  }
  
  // email
  if (empty($comm['email']))
  {
    if (!empty($user['email']))
    {
      $comm['email'] = $user['email'];
    }
    else if ($conf['comments_email_mandatory'])
    {
      array_push($infos, l10n('Email address is missing. Please specify an email address.') );
      $comment_action='reject';
    }
  }
  else if (!email_check_format($comm['email']))
  {
    array_push($infos, l10n('mail address must be like xxx@yyy.eee (example : jack@altern.org)'));
    $comment_action='reject';
  }
  
  // anonymous id = ip address
  $ip_components = explode('.', $comm['ip']);
  if (count($ip_components) > 3)
  {
    array_pop($ip_components);
  }
  $comm['anonymous_id'] = implode('.', $ip_components);

  if ($comment_action!='reject' and $conf['anti-flood_time']>0 and !is_admin())
  { // anti-flood system
    $reference_date = pwg_db_get_flood_period_expression($conf['anti-flood_time']);

    $query = '
SELECT count(1) FROM '.COA_TABLE.'
  WHERE date > '.$reference_date.'
    AND author_id = '.$comm['author_id'];
    if (!is_classic_user())
    {
      $query.= '
      AND anonymous_id = "'.$comm['anonymous_id'].'"';
    }
    $query.= '
;';

    list($counter) = pwg_db_fetch_row(pwg_query($query));
    if ( $counter > 0 )
    {
      array_push( $infos, l10n('Anti-flood system : please wait for a moment before trying to post another comment') );
      $comment_action='reject';
    }
  }

  // perform more spam check
  $comment_action = trigger_event('user_comment_check_albums',
      $comment_action, $comm
    );

  if ( $comment_action!='reject' )
  {
    $query = '
INSERT INTO '.COA_TABLE.'
  (author, author_id, anonymous_id, content, date, validated, validation_date, category_id, website_url, email)
  VALUES (
    \''.$comm['author'].'\',
    '.$comm['author_id'].',
    \''.$comm['anonymous_id'].'\',
    \''.$comm['content'].'\',
    NOW(),
    \''.($comment_action=='validate' ? 'true':'false').'\',
    '.($comment_action=='validate' ? 'NOW()':'NULL').',
    '.$comm['category_id'].',
    '.(!empty($comm['website_url']) ? '\''.$comm['website_url'].'\'' : 'NULL').',
    '.(!empty($comm['email']) ? '\''.$comm['email'].'\'' : 'NULL').'
  )
';

    pwg_query($query);

    $comm['id'] = pwg_db_insert_id(COA_TABLE);

    if ( ($conf['email_admin_on_comment'] && 'validate' == $comment_action)
        or ($conf['email_admin_on_comment_validation'] and 'moderate' == $comment_action))
    {
      include_once(PHPWG_ROOT_PATH.'include/functions_mail.inc.php');

      $comment_url = get_absolute_root_url().'comments.php?display_mode=albums&comment_id='.$comm['id'];

      $keyargs_content = array
      (
        get_l10n_args('Author: %s', stripslashes($comm['author']) ),
        get_l10n_args('Email: %s', stripslashes($comm['email']) ),
        get_l10n_args('Comment: %s', stripslashes($comm['content']) ),
        get_l10n_args('', ''),
        get_l10n_args('Manage this user comment: %s', $comment_url)
      );

      if ('moderate' == $comment_action)
      {
        $keyargs_content[] = get_l10n_args('', '');
        $keyargs_content[] = get_l10n_args('(!) This comment requires validation', '');
      }

      pwg_mail_notification_admins
      (
        get_l10n_args('Comment by %s', stripslashes($comm['author']) ),
        $keyargs_content
      );
    }
  }
  return $comment_action;
}

/**
 * Tries to delete a user comment in the database
 * only admin can delete all comments
 * other users can delete their own comments
 * so to avoid a new sql request we add author in where clause
 *
 * @param comment_id
 */
function delete_user_comment_albums($comment_id) 
{
  $user_where_clause = '';
  if (!is_admin())
  {
    $user_where_clause = '   AND author_id = \''.$GLOBALS['user']['id'].'\'';
  }
  
  if (is_array($comment_id))
    $where_clause = 'id IN('.implode(',', $comment_id).')';
  else
    $where_clause = 'id = '.$comment_id;
  
  $query = '
DELETE FROM '.COA_TABLE.'
  WHERE '.$where_clause.
$user_where_clause.'
;';
  $result = pwg_query($query);
  
  if ($result) 
  {
    email_admin('delete', 
                array('author' => $GLOBALS['user']['username'],
                      'comment_id' => $comment_id
                  ));
  }
  
  trigger_action('user_comment_deletion', $comment_id, 'category');
}

/**
 * Tries to update a user comment in the database
 * only admin can update all comments
 * users can edit their own comments if admin allow them
 * so to avoid a new sql request we add author in where clause
 *
 * @param comment_id
 * @param post_key
 * @param content
 */
function update_user_comment_albums($comment, $post_key)
{
  global $conf;

  $comment_action = 'validate';

  if ( !verify_ephemeral_key($post_key, $comment['category_id']) )
  {
    $comment_action='reject';
  }
  elseif (!$conf['comments_validation'] or is_admin()) // should the updated comment must be validated
  {
    $comment_action='validate'; //one of validate, moderate, reject
  }
  else
  {
    $comment_action='moderate'; //one of validate, moderate, reject
  }

  // perform more spam check
  $comment_action =
    trigger_event('user_comment_check_albums',
      $comment_action,
      array_merge($comment,
            array('author' => $GLOBALS['user']['username'])
            )
      );

  if ( $comment_action!='reject' )
  {
    $user_where_clause = '';
    if (!is_admin())
    {
      $user_where_clause = '   AND author_id = \''.
  $GLOBALS['user']['id'].'\'';
    }

    $query = '
UPDATE '.COA_TABLE.'
  SET content = \''.$comment['content'].'\',
      validated = \''.($comment_action=='validate' ? 'true':'false').'\',
      validation_date = '.($comment_action=='validate' ? 'NOW()':'NULL').'
  WHERE id = '.$comment['comment_id'].
$user_where_clause.'
;';
    $result = pwg_query($query);
    
    // mail admin and ask to validate the comment
    if ($result and $conf['email_admin_on_comment_validation'] and 'moderate' == $comment_action) 
    {
      include_once(PHPWG_ROOT_PATH.'include/functions_mail.inc.php');

      $comment_url = get_absolute_root_url().'comments.php?display_mode=albums&amp;comment_id='.$comment['comment_id'];

      $keyargs_content = array
      (
        get_l10n_args('Author: %s', stripslashes($GLOBALS['user']['username']) ),
        get_l10n_args('Comment: %s', stripslashes($comment['content']) ),
        get_l10n_args('', ''),
        get_l10n_args('Manage this user comment: %s', $comment_url),
        get_l10n_args('', ''),
        get_l10n_args('(!) This comment requires validation', ''),
      );

      pwg_mail_notification_admins
      (
        get_l10n_args('Comment by %s', stripslashes($GLOBALS['user']['username']) ),
        $keyargs_content
      );
    }
    // just mail admin
    else if ($result)
    {
      email_admin('edit', array('author' => $GLOBALS['user']['username'],
        'content' => stripslashes($comment['content'])) );
    }
  }
  
  return $comment_action;
}

function get_comment_author_id_albums($comment_id, $die_on_error=true)
{
  $query = '
SELECT
    author_id
  FROM '.COA_TABLE.'
  WHERE id = '.$comment_id.'
;';
  $result = pwg_query($query);
  if (pwg_db_num_rows($result) == 0)
  {
    if ($die_on_error)
    {
      fatal_error('Unknown comment identifier');
    }
    else
    {
      return false;
    }
  }
  
  list($author_id) = pwg_db_fetch_row($result);

  return $author_id;
}

/**
 * Tries to validate a user comment in the database
 * @param int or array of int comment_id
 */
function validate_user_comment_albums($comment_id)
{
  if (is_array($comment_id))
    $where_clause = 'id IN('.implode(',', $comment_id).')';
  else
    $where_clause = 'id = '.$comment_id;
    
  $query = '
UPDATE '.COA_TABLE.'
  SET validated = \'true\'
    , validation_date = NOW()
  WHERE '.$where_clause.'
;';
  pwg_query($query);
  
  trigger_action('user_comment_validation', $comment_id, 'category');
}
?>