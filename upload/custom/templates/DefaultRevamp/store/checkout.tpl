{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui container">
	<div class="ui padded segment">
		<div class="ui stackable grid">
			<div class="ui row">
				<div class="twelve wide column">
					<h1 style="display:inline;">{$STORE} &raquo; {$CHECKOUT}</h1>
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
					
					<div class="ui divider"></div>
					
					<p>You will be redirected to PayPal to complete your purchase.</p>
					</br>
				  
					<form class="ui form" action="" method="post">
					  <div class="field">
					    <input type="hidden" name="token" value="{$TOKEN}">
						<input type="hidden" name="type" value="single">
					    <input type="submit" class="ui primary button" value="{$PURCHASE}">
					  </div>
					</form>
					
				</div>
				<div class="four wide column">
					<div class="ui segments">
					  <h4 class="ui top attached header">{$SUMMARY}</h4>
					  <div class="ui segment">
						<strong>Package:</strong> {$PACKAGE}</br>
						<strong>Price:</strong> ${$TOTAL_PRICE} USD</br>
					  </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{include file='footer.tpl'}