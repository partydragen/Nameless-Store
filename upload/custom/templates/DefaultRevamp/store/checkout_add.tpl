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

        <h1 style="display:inline;">{$STORE} &raquo; {$PRODUCT_NAME}</h1>
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
          <h3>{$PRODUCT_NAME}</h3>
          <div class="ui divider"></div>
          
          {foreach from=$PRODUCT_FIELDS item=field}
            <div class="field">
              <label for="{$field.id}">{$field.description} {if $field.required} <span class="text-danger"><strong>*</strong></span>{/if}</label>
              
              {if $field.type == "1"}
                <input type="text" name="{$field.id}" id="{$field.id}" value="{$field.value}" placeholder="{$field.description}" {if $field.required}required{/if}>
              {elseif $field.type == "2"}
                <select class="ui fluid dropdown" name="{$field.id}" id="{$field.id}" {if $field.required}required{/if}>
                  <option value="">{$field.description}</option>
                  {foreach from=$field.selections item=option}
                  <option value="{$option.value}" {if $option.value eq $field.value} selected{/if}>{$option.description} {if isset($option.price)}({$option.price}){/if}</option>
                  {/foreach}
                </select>
              {elseif $field.type == "3"}
                <textarea name="{$field.id}" id="{$field.id}" {if $field.required}required{/if}>{$field.value}</textarea>
              {elseif $field.type == "4"}
                <input type="number" name="{$field.id}" id="{$field.id}" value="{$field.value}" placeholder="{$field.description}" {if $field.required}required{/if}>
              {elseif $field.type == "5"}
                <input type="email" name="{$field.id}" id="{$field.id}" value="{$field.value}" placeholder="{$field.description}" {if $field.required}required{/if}>
              {elseif $field.type == "6"}
                {foreach from=$field.selections item=option}
                  <div class="field">
                    <div class="ui radio checkbox">
                      <input type="radio" name="{$field.id}" value="{$option.value}" {if $field.required}required{/if} {if $option.value eq $field.value} checked{/if}>
                      <label>{$option.description} {if isset($option.price)}({$option.price}){/if}</label>
                    </div>
                  </div>
                {/foreach}
              {elseif $field.type == "7"}
                {foreach from=$field.selections item=option}
                  <div class="field">
                    <div class="ui checkbox">
                      <input type="checkbox" name="{$field.id}[]" value="{$option.value}">
                      <label>{$option.description} {if isset($option.price)}({$option.price}){/if}</label>
                    </div>
                  </div>
                {/foreach}
              {/if}
            </div>
          {/foreach}
          
          <div class="field">
              <input type="hidden" name="token" value="{$TOKEN}">
              <input type="submit" class="ui green button" value="{$CONTINUE}">
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
                                <div class="description right floated"><b>{$CURRENCY_SYMBOL}{$CREDITS_VALUE} {$CURRENCY}</b></div>
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

{include file='footer.tpl'}