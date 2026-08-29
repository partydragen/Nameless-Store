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

        <h3>{$SHOPPING_CART}</h3>
        <table class="ui fixed single line selectable unstackable small padded res table">
          <thead>
            <tr>
              <th>{$NAME}</th>
              <th>{$OPTIONS}</th>
              <th>{$QUANTITY}</th>
              <th>{$PRICE}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$SHOPPING_CART_LIST item=item}
              <tr>
                <td>{$item.name}</td>
                <td>{if count($item.fields)} {foreach from=$item.fields item=field name=fields}<strong>{$field.description}</strong>: {$field.value}{if not $smarty.foreach.fields.last}</br>{/if}{/foreach} {/if}</td>
                <td>{$item.quantity}</td>
                <td>{if $item.has_discount}<span style="color: #dc3545;text-decoration:line-through;">{$item.price_format}</span>{/if} {$item.real_price_format}</td>
                <td><a href="{$item.remove_link}" class="ui icon remove red tiny button right floated"><i class="icon remove"></i></a></td>
              </tr>
            {/foreach}
          </tbody>
        </table>

        <table class="ui collapsing table">
          <tbody>
            {if $TOTAL_DISCOUNT_VALUE > 0}
            <tr>
              <td>{$TOTAL_PRICE}</td>
              <td>{$TOTAL_PRICE_FORMAT_VALUE}</td>
            </tr>
            <tr>
              <td>{$TOTAL_DISCOUNT}</td>
              <td>{$TOTAL_DISCOUNT_FORMAT_VALUE}</td>
            </tr>
            {/if}
            <tr>
              <td>{$PRICE_TO_PAY}</td>
              <td id="store-checkout-total">{$TOTAL_REAL_PRICE_FORMAT_VALUE}</td>
            </tr>
          </tbody>
        </table>

        <h3>{$REDEEM_COUPON}</h3>
        <div class="ui divider"></div>
        <form class="ui form" action="{$REDEEM_COUPON_URL}" method="post" id="coupon">
          <div class="field">
              <div class="ui action input">
                  <input type="text" name="coupon" id="coupon" value="{$REDEEM_COUPON_VALUE}" placeholder="{$REDEEM_COUPON_HERE}"/>
                  <input type="hidden" name="token" value="{$TOKEN}">
                  <button class="ui green button">{$REDEEM} &raquo;</button>
              </div>
          </div>
        </form>

        <h3>{$PAYMENT_METHOD}</h3>
        <div class="ui divider"></div>
        <form class="ui form" action="" method="post" id="forms">
          {foreach from=$PAYMENT_METHODS item=gateway}
            <div class="field">
              <div class="ui radio checkbox">
                <input type="radio" name="payment_method" value="{$gateway.name}" required>
                <label>{$gateway.displayname}</label>
              </div>
            </div>
          {/foreach}

          {if $CAN_SPLIT_PAYMENT}
            <div class="ui info message">
              <div class="field">
                <div class="ui checkbox">
                  <input id="store-use-credits" type="checkbox" name="use_credits" value="1"{if $SPLIT_CREDITS_CHECKED} checked{/if}>
                  <label><strong>{$USE_CREDITS}</strong></label>
                </div>
              </div>
              <p>{$USE_CREDITS_HELP}</p>
              <div class="ui relaxed divided list">
                <div class="item">
                  <span>{$CREDITS_AVAILABLE}</span>
                  <strong class="right floated">{$SPLIT_CREDITS_AVAILABLE_FORMAT}</strong>
                </div>
                <div class="item store-credit-breakdown"{if !$SPLIT_CREDITS_CHECKED} style="display:none"{/if}>
                  <span>{$CREDITS_APPLIED}</span>
                  <strong class="right floated">-{$SPLIT_CREDITS_APPLIED_FORMAT}</strong>
                </div>
                <div class="item store-credit-breakdown"{if !$SPLIT_CREDITS_CHECKED} style="display:none"{/if}>
                  <span>{$REMAINING_TO_PAY}</span>
                  <strong class="right floated">{$SPLIT_REMAINING_FORMAT}</strong>
                </div>
              </div>
            </div>
          {/if}

            <h3>{$PURCHASE}</h3>
            <div class="ui divider"></div>
            <div class="ui equal width grid">
                <div class="column">
                    <div class="field">
                        <div class="ui checkbox" style="display:inline;">
                            <input type="checkbox" name="t_and_c" value="1" required> <label>{$AGREE_T_AND_C_PURCHASE}</label>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="field">
                        <input type="hidden" name="token" value="{$TOKEN}">
                        <span class="right floated"><input type="submit" class="ui green button right floated" value="{if isset($CHECKOUT_SUBSCRIBE)}{$CHECKOUT_SUBSCRIBE}{else}{$PURCHASE}{/if} &raquo;"></span>
                    </div>
                </div>
            </div>

        </form>

      </div>
    </div>
    
    {if count($WIDGETS_RIGHT)}
      <div class="ui six wide tablet four wide computer column">
        {if isset($LOGGED_IN_USER) && isset($SHOW_CREDITS_AMOUNT)}
            <div class="ui fluid card" id="widget-store-account">
                <div class="content">
                    <h4 class="ui header">{$ACCOUNT}</h4>
                    <div class="description">
                        <div class="ui list">
                            <div class="item">
                                <span class="text">{$CREDITS}</span>
                                <div class="description right floated"><b>{$CREDITS_FORMAT_VALUE}</b></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        {foreach from=$WIDGETS_RIGHT item=widget}
          {$widget}
        {/foreach}
      </div>
    {/if}

  </div>
</div>

{if $CAN_SPLIT_PAYMENT}
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('store-use-credits');
    var total = document.getElementById('store-checkout-total');
    var breakdown = document.querySelectorAll('.store-credit-breakdown');
    if (!checkbox) return;

    var refreshCredits = function () {
      for (var i = 0; i < breakdown.length; i++) {
        breakdown[i].style.display = checkbox.checked ? '' : 'none';
      }
      if (total) {
        total.innerHTML = checkbox.checked ? '{$SPLIT_REMAINING_FORMAT|escape:"javascript"}' : '{$SPLIT_TOTAL_FORMAT|escape:"javascript"}';
      }
    };

    checkbox.addEventListener('change', refreshCredits);
    refreshCredits();
  });
</script>
{/if}

{include file='footer.tpl'}
