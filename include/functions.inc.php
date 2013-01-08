<?php
/* This is from include/functions.inc.php but adapted for Comments On Albums */

/**
 * return an array which will be sent to template to display navigation bar
 */
function create_comment_navigation_bar($url, $nb_element, $start, $nb_element_page)
{
  global $conf;

  $navbar = array();
  $pages_around = $conf['paginate_pages_around'];
  $start_str = (strpos($url, '?')===false ? '?':'&amp;').'start_comments=';

  if (!isset($start) or !is_numeric($start) or (is_numeric($start) and $start < 0))
  {
    $start = 0;
  }

  // navigation bar useful only if more than one page to display !
  if ($nb_element > $nb_element_page)
  {
    $cur_page = ceil($start / $nb_element_page) + 1;
    $maximum = ceil($nb_element / $nb_element_page);
    $previous = $start - $nb_element_page;
    $next = $start + $nb_element_page;
    $last = ($maximum - 1) * $nb_element_page;

    $navbar['CURRENT_PAGE'] = $cur_page;

    // link to first page and previous page?
    if ($cur_page != 1)
    {
      $navbar['URL_FIRST'] = $url;
      $navbar['URL_PREV'] = $url.($previous > 0 ? $start_str.$previous : '');
    }
    // link on next page and last page?
    if ($cur_page != $maximum)
    {
      $navbar['URL_NEXT'] = $url.$start_str.$next;
      $navbar['URL_LAST'] = $url.$start_str.$last;
    }

    // pages to display
    $navbar['pages'] = array();
    $navbar['pages'][1] = $url;
    $navbar['pages'][$maximum] = $url.$start_str.$last;

    for ($i = max($cur_page - $pages_around , 2), $stop = min($cur_page + $pages_around + 1, $maximum);
         $i < $stop; $i++)
    {
      $navbar['pages'][$i] = $url.$start_str.(($i - 1) * $nb_element_page);
    }
    ksort($navbar['pages']);
  }
  return $navbar;
}

?>