<div class="ui segment">
  <h4>Latest Subscribers</h4>
  <hr>
  <small>
  {foreach from=$LATEST_SUBSCRIBERS_ARRAY item=item}
    <div class="ui middle aligned centered two column stackable grid">
	  <div class="row">
		<div class="four wide column">
		  <a href="{$item.updated_by_link}"><img class="ui circular image" style="max-height:40px;max-width:40px;" src="{$item.user_avatar}" alt="{$item.user_username}"/></a>
		</div>
		<div class="twelve wide column">
		  <a href="{$item.user_link}"><strong>{$item.user_username}</strong></a><br />
		  <strong>{$item.package_name} - ${$item.package_price} USD</strong>
		</div>
	  </div>
    </div>
  {/foreach}
  </small>
</div>