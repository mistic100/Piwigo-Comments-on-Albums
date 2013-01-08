{* this is a copy of theme/defaults/template/navigation_bar.tpl 
  but with other variables to not interfer with the thumbnails' navigation bar *}
  
<div class="navigationBar">
  {if isset($comment_navbar.URL_FIRST)}
    <a href="{$comment_navbar.URL_FIRST}#comments" rel="first">{'First'|@translate}</a> |
    <a href="{$comment_navbar.URL_PREV}#comments" rel="prev">{'Previous'|@translate}</a> |
  {else}
    {'First'|@translate} |
    {'Previous'|@translate} |
  {/if}

  {assign var='prev_page' value=0}
  {foreach from=$comment_navbar.pages key=page item=url}
    {if $page > $prev_page+1}...{/if}
    {if $page == $comment_navbar.CURRENT_PAGE}
      <span class="pageNumberSelected">{$page}</span>
    {else}
      <a href="{$url}#comments">{$page}</a>
    {/if}
    {assign var='prev_page' value=$page}
  {/foreach}

  {if isset($comment_navbar.URL_NEXT)}
    | <a href="{$comment_navbar.URL_NEXT}#comments" rel="next">{'Next'|@translate}</a>
    | <a href="{$comment_navbar.URL_LAST}#comments" rel="last">{'Last'|@translate}</a>
  {else}
    | {'Next'|@translate}
    | {'Last'|@translate}
  {/if}
</div>
