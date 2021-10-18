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

		<h1 style="display:inline;">{$STORE} &raquo; {$CHECKOUT}</h1>
		{include file='store/navbar.tpl'}
        
        </br>
        
        {if isset($SUCCESS)}
          <div class="ui success icon message">
            <i class="check icon"></i>
            <div class="content">
             {$SUCCESS}
            </div>
          </div>
        {/if}
                    
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
        
        <form class="ui form" action="" method="post" id="forms">
          <h3>Shopping Cart</h3>
          <table class="ui fixed single line selectable unstackable small padded res table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$SHOPPING_CART_LIST item=item}
                <tr>
                  <td>{$item.name}</td>
                  <td>{$item.quantity}</td>
                  <td>{$CURRENCY_SYMBOL}{$item.price} {$CURRENCY}</td>
                  <td><a href="{$item.remove_link}" class="ui icon remove red tiny button right floated"><i class="icon remove"></i></a></td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        
          <h4>Total Price: {$TOTAL_PRICE} {$CURRENCY}<h4>
          
          <h3>Payment method</h3>
          <hr />
          
          {foreach from=$PAYMENT_METHODS item=gateway}
            <div class="field">
              <div class="ui radio checkbox">
                <input type="radio" name="payment_method" value="{$gateway.name}" required>
                <label>{$gateway.displayname}</label>
              </div>
            </div>
          {/foreach}
        
        
          <h3>Purchase</h3>
          <hr />
          <div class="field">
            <div class="ui checkbox" style="display:inline;">
              <input type="hidden" name="token" value="{$TOKEN}">
              <input type="checkbox" name="t_and_c" value="1" required> <label>I agree to the terms and conditions of this purchase. <span class="right floated"><input type="submit" class="ui green button right floated" value="Purchase &raquo;"></span></label>
            </div>
          </div>
          </br>
        </form>
        
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