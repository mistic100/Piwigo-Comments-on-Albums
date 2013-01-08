{* this is inspired by theme/defaults/template/picture.tpl *}
 
{* <!-- need some css modifications, specific to each theme --> *}
{html_head}
<style type="text/css">
#comments .commentElement {ldelim} width:98%;}
{if $COMMENTS_ON_TOP}
#commentAdd, #pictureCommentList {ldelim} float:none; display:inline-block; width:47.5%; vertical-align:top;}
#commentsSwitcher {ldelim} float:none; display:inline-block; margin: 2px 0; cursor:pointer;}
.noCommentContent #commentsSwitcher  {ldelim} display: none;}
.switchArrow{ldelim} width: 16px; height: 16px; margin: 5px;}
.commentshidden #pictureComments {ldelim} display: none;}
.commentContent .comments_toggle   {ldelim} cursor: pointer;}
{/if}
{if $themeconf.name == 'Sylvia'}
#comments .description {ldelim} padding: 15px 2px 6px 12px;}
#comments .commentElement {ldelim} border: 1px solid #666;}
{/if}
{if $themeconf.name|strstr:"stripped"}
#comments {ldelim} text-align: left;}
#comments .description {ldelim} height:auto;}
#thumbnails_block2 {ldelim} min-height:0;}
{/if}

</style>
{/html_head}

{* <!-- if RV Thumb Scroller is installed comments block is displayed on top with a drop-down --> *}
{if $COMMENTS_ON_TOP}
{footer_script}{literal}
  // comments show/hide
  var commentsswicther=jQuery("#commentsSwitcher");
  var comments=jQuery("#theCategoryPage #comments");
  
  commentsswicther.html('<div class="switchArrow">&nbsp;</div>');
  {/literal}{if $themeconf.name != 'elegant'}switcharrow = commentsswicther.children(".switchArrow");{else}switcharrow = $('<div></div>');{/if}{literal}
  
  if (comments.length == 1) {
    var comments_button=jQuery("#comments h3");

    if (comments_button.length == 0) {
      jQuery("#addComment").before('<h3>Comments</h3>');
      comments_button=jQuery("#comments h3");
    }
  
    {/literal}{if $DISPLAY_COMMENTS_BLOCK}
    comments.addClass("commentsshown");
    comments_button.addClass("comments_toggle").addClass("comments_toggle_on");
    switcharrow.html("&uarr;");
    {else}
    comments.addClass("commentshidden");
    comments_button.addClass("comments_toggle").addClass("comments_toggle_off");
    switcharrow.html("&darr;");
    {/if}{literal}
    
    comments_button.click(function() { commentsToggle() });
    commentsswicther.click(function() { commentsToggle() });
  
  }
  
  function commentsToggle() {
    var comments=jQuery("#theCategoryPage #comments");
    var comments_button=jQuery("#comments h3");

    if (comments.hasClass("commentshidden")) {
        comments.removeClass("commentshidden").addClass("commentsshown");
        comments_button.addClass("comments_toggle_off").removeClass("comments_toggle_off");
        switcharrow.html("&uarr;");
      } else {
        comments.addClass("commentshidden").removeClass("commentsshown");
        comments_button.addClass("comments_toggle_on").removeClass("comments_toggle_on");
        switcharrow.html("&darr;");
      }

  }
{/literal}{/footer_script}
{/if}

{if isset($COMMENT_COUNT)}
<div id="comments" style="margin:10px 0 10px 0;" {if (!isset($comment_add) && ($COMMENT_COUNT == 0))}class="noCommentContent"{else}class="commentContent"{/if}>
  <h3 style="margin:0 0 0 5px;"><div id="commentsSwitcher"></div>{$pwg->l10n_dec('%d comment', '%d comments',$COMMENT_COUNT)}</h3>

  <div id="pictureComments"><fieldset>
    {if isset($comment_add)}
    <div id="commentAdd">
      <h4>{'Add a comment'|@translate}</h4>
      <form method="post" action="{$comment_add.F_ACTION}" id="addComment">
        {if $comment_add.SHOW_AUTHOR}
          <p><label for="author">{'Author'|@translate}{if $comment_add.AUTHOR_MANDATORY} ({'mandatory'|@translate}){/if} :</label></p>
          <p><input type="text" name="author" id="author" value="{$comment_add.AUTHOR}"></p>
        {/if}
        {if $comment_add.SHOW_EMAIL}
          <p><label for="email">{'Email'|@translate}{if $comment_add.EMAIL_MANDATORY} ({'mandatory'|@translate}){/if} :</label></p>
          <p><input type="text" name="email" id="email" value="{$comment_add.EMAIL}"></p>
        {/if}
        <p><label for="website_url">{'Website'|@translate} :</label></p>
        <p><input type="text" name="website_url" id="website_url" value="{$comment_add.WEBSITE_URL}"></p>
        <p><label for="contentid">{'Comment'|@translate} ({'mandatory'|@translate}) :</label></p>
        <p><textarea name="content" id="contentid" rows="5" cols="50">{$comment_add.CONTENT}</textarea></p>
        <p><input type="hidden" name="key" value="{$comment_add.KEY}">
          <input type="submit" value="{'Submit'|@translate}"></p>
      </form>
    </div>
    {/if}
    {if isset($comments)}
    <div id="pictureCommentList">
      {if (($COMMENT_COUNT > 2) || !empty($comment_navbar))}
        <div id="pictureCommentNavBar">
          {if $COMMENT_COUNT > 2}
            <a href="{$COMMENTS_ORDER_URL}#comments" rel="nofollow" class="commentsOrder">{$COMMENTS_ORDER_TITLE}</a>
          {/if}
          {if !empty($comment_navbar) }{include file=$COA_ABSOLUTE_PATH|@cat:'template/navigation_bar.tpl'|@get_extent:'navbar'}{/if}
        </div>
      {/if}
      {include file='comment_list.tpl'}
    </div>
    {/if}
    {if not $COMMENTS_ON_TOP}<div style="clear:both"></div>{/if}
  </fieldset></div>

</div>
{/if}{*comments*}