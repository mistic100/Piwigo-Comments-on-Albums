{combine_css path=$block.CR_PATH|@cat:'template/style.css'}

<ul class="thumbnailCategories">
  {foreach from=$block.contests item=contest}
  <li class="{$block.SIZE_CLASS} {if !$contest.VISIBLE}novisible{/if}">
    <div class="thumbnailCategory">
      <div class="illustration">
        <a href="{$contest.URL}">
        {if $contest.STATUS != 'finished' AND !empty($contest.LOGO)}
          <img src="{$contest.LOGO}" alt="{$contest.NAME}" style="max-height:120px;max-width:120px;">
        {elseif !empty($contest.RESULTS.1.TN_SRC)}
          <img src="{$contest.RESULTS.1.TN_SRC}" alt="{$contest.NAME|@replace:'"':' '}">
        {/if}
        </a>
      </div>
      <div class="description">
        <h3><a href="{$contest.URL}">{$contest.NAME}</a></h3>
        <div class="text">
          <p class="Nb_images">{$contest.DATE_BEGIN} - {$contest.DATE_END}</p>
          <span class="CR_finished">({$contest.DAYS})</span>
          <p>
          {if $contest.STATUS != 'finished' AND !empty($contest.SUMMARY)}
            {$contest.SUMMARY}
          {else}
            {foreach from=$contest.RESULTS item=result}
              {'CR_order_'|cat:$result.RANK|@translate} {'CR_place'|@translate} - <u>{$result.AUTHOR}</u><br>
            {/foreach}
          {/if}
          </p>
        </div>
      </div>
    </div>
  </li>
  {/foreach}
</ul>