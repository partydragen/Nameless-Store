{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui stackable grid">
    <div class="ui centered row">
        <div class="ui {if count($WIDGETS)}ten wide tablet twelve wide computer{else}sixteen wide{/if} column">
            <div class="ui padded segment">
				<h1 style="display:inline;">{$STORE}</h1>
				{if isset($STORE_PLAYER)}
				<span class="right floated">
					<form class="ui form" action="" method="post">
						<div class="ui labeled button" tabindex="0">
							<div class="ui label">
								{$STORE_PLAYER}
							</div>
							<input type="hidden" name="token" value="{$TOKEN}">
							<input type="hidden" name="type" value="store_logout">
							<input type="submit" class="ui red button" value="Logout">
						</div>
					</form>
				</span>
				{/if}

                <div class="ui top attached menu">
                    <a class="active item" href="{$HOME_URL}">
                        {$HOME}
                    </a>
                    {foreach from=$CATEGORIES item=category}
                        {if isset($category.subcategories) && count($category.subcategories)}
                            <div class="ui pointing dropdown link item">
                                <span class="text">{$category.title}</span>
                                <i class="dropdown icon"></i>
                                <div class="menu">
                                    <a class="item" href="{$category.url}">{$category.title}</a>
                                    {foreach from=$category.subcategories item=subcategory}
                                        <a class="item" href="{$subcategory.url}">{$subcategory.title}</a>
                                    {/foreach}
                                </div>
                            </div>
                        {else}
                            <a class="item" href="{$category.url}">
                                {$category.title}
                            </a>
                        {/if}
                    {/foreach}
                </div>
                <div class="ui bottom attached segment">
                    {$CONTENT}
                </div>
            </div>
        </div>
        {if count($WIDGETS)}
            <div class="ui six wide tablet four wide computer column">
                {foreach from=$WIDGETS item=widget}
                    {$widget}
                {/foreach}
            </div>
        {/if}
    </div>
</div>

{include file='footer.tpl'}