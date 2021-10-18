{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui stackable grid">
  <div class="ui centered row">
  
    {if count($WIDGETS_LEFT)}
      <div class="ui six wide tablet four wide computer column">
        {foreach from=$WIDGETS_LEFT item=widget}
          {$widget}
        {/foreach}
      </div>
    {/if}
    
    <div class="ui {if count($WIDGETS_LEFT) && count($WIDGETS_RIGHT) }four wide tablet eight wide computer{elseif count($WIDGETS_LEFT) || count($WIDGETS_RIGHT)}ten wide tablet twelve wide computer{else}sixteen wide{/if} column">
      <div class="ui segment">

		<h1 style="display:inline;">{$STORE} &raquo; {$ACTIVE_CATEGORY}</h1>
		{include file='store/navbar.tpl'}
                
        <div class="ui bottom attached segment">
          {if isset($STORE_PLAYER)}
            {if isset($NO_PACKAGES)}
              <div class="ui icon message">
                <i class="info icon"></i>
                <div class="content">
                  <p>{$NO_PACKAGES}</p>
                </div>
              </div>
            {else}
              <div class="ui centered stackable grid">
                {foreach from=$PACKAGES item=package}
                  <div class="four wide column">
                    <div class="ui card" style="height: 100%">
                      {if $package.image}
                        <div class="image">
                          {if $package.sale_active}
                            <span class="ui right ribbon red label">
                              {$SALE}
                            </span>
                          {/if}
                          <img src="{$package.image}" alt="{$package.name}">
                        </div>
                      {/if}
                      
                      <div class="center aligned content">
                        <span class="header">{$package.name}</span>
                        <div class="ui divider"></div>
                        {if $package.sale_active}
                          <span style="color: #dc3545;text-decoration:line-through;">{$CURRENCY}{$package.price}</span>
                        {/if}
                        {$CURRENCY}{$package.real_price}
                      </div>
                      <div class="ui bottom attached blue button" onClick="$('#modal{$package.id}').modal('show');">
                        {$BUY} &raquo;
                      </div>
                    </div>
                  </div>

                  <div class="ui small modal" id="modal{$package.id}">
                    <div class="header">
                      {$package.name} | {$CURRENCY}{$package.real_price}
                    </div>
                    <div class="{if $package.image}image {/if}content">
                      {if $package.image}
                        <div class="ui small image">
                          <img src="{$package.image}" alt="{$package.name}">
                        </div>
                      {/if}
                      <div class="description forum_post">
                        {$package.description}
                      </div>
                    </div>
                    <div class="actions">
                      <div class="ui red deny button">
                        {$CLOSE}
                      </div>
                      <a class="ui positive right labeled icon button" href="{$package.link}">
                        {$BUY}
                        <i class="shopping cart icon"></i>
                      </a>
                    </div>
                  </div>
                {/foreach}
              </div>
			{/if}
		  {else}
			{if isset($ERRORS)}
			  <div class="ui negative icon message">
				<i class="x icon"></i>
				<div class="content">
				  {foreach from=$ERRORS item=error}
					{$error}<br />
				  {/foreach}
				</div>
			  </div>
			{/if}
					
			<h3>{$PLEASE_ENTER_USERNAME}</h3>
			<form class="ui form" action="" method="post">
			  <div class="field">
                <input type="text" name="username" id="username" placeholder="{$PLEASE_ENTER_USERNAME}">
			  </div>
			  <div class="field">
                <input type="hidden" name="token" value="{$TOKEN}">
                <input type="hidden" name="type" value="store_login">
                <input type="submit" class="ui primary button" value="{$CONTINUE} &raquo;">
			  </div>
			</form>
          {/if}
        </div>
                
      </div>
    </div>
    
    {if count($WIDGETS_RIGHT)}
      <div class="ui six wide tablet four wide computer column">
        {foreach from=$WIDGETS_RIGHT item=widget}
          {$widget}
        {/foreach}
      </div>
    {/if}
        
  </div>
</div>

{include file='footer.tpl'}