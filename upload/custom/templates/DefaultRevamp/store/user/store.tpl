{include file='header.tpl'}
{include file='navbar.tpl'}

<h2 class="ui header">
    {$TITLE}
</h2>

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

<div class="ui stackable grid" id="user">
    <div class="ui centered row">
        <div class="ui six wide tablet four wide computer column">
            {include file='user/navigation.tpl'}
        </div>
        <div class="ui ten wide tablet twelve wide computer column">
            <div class="ui segment">
                <h3 class="ui header">{$STORE}
                    {if isset($CAN_SEND_CREDITS)}<div class="res right floated"><a class="ui mini green button" data-toggle="modal" data-target="#modal-send-credits">{$SEND_CREDITS}</a></div>{/if}
                </h3>
                
                <p>{$CREDITS}: {$CREDITS_FORMAT_VALUE}</p>
            </div>
            
            <div class="ui segment">
                <h3 class="ui header">{$MY_TRANSACTIONS}</h3>
                {nocache}
                    {if count($TRANSACTIONS_LIST)}
                        <table class="ui fixed single line selectable unstackable small padded res table">
                            <thead>
                                <tr>
                                    <th>{$TRANSACTION}</th>
                                    <th>{$AMOUNT}</th>
                                    <th>{$DATE}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$TRANSACTIONS_LIST item=transaction}
                                    <tr>
                                        <td>{$transaction.transaction}</td>
                                        <td>{$transaction.amount_format}</td>
                                        <td><span data-toggle="tooltip" data-content="{$transaction.date_full}">{$transaction.date_friendly}</span></td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {else}
                        <div class="ui info message">
                            <div class="content">
                                {$NO_TRANSACTIONS}
                            </div>
                        </div>
                    {/if}
                {/nocache}
            </div>
        </div>
    </div>
</div>

{if isset($CAN_SEND_CREDITS)}
<div class="ui small modal" id="modal-send-credits">
    <div class="header">
        {$SEND_CREDITS}
    </div>
    <div class="content">
        <form class="ui form" action="" method="post" id="sendCredits">
            <div class="field">
                <label for="InputTo">{$TO}</label>
                <div class="ui fluid search selection dropdown">
                    <input name="to" id="InputTo" type="hidden">
                    <i class="dropdown icon"></i>
                    <div class="default text">{$TO}</div>
                    <div class="menu">
                        {if count($ALL_USERS) > 0}
                            {foreach from=$ALL_USERS item="username"}
                                <div class="item" data-value="{$username}">{$username}</div>
                            {/foreach}
                        {/if}
                    </div>
                </div>
            </div>
            <div class="field">
                <label for="inputCredits">{$AMOUNT} {$YOU_HAVE_X_CREDITS}</label>
                <input type="number" id="InputCredits" name="credits" step="0.01" min="0.01" max="{$CREDITS_VALUE}" value="0.00">
            </div>
            <input type="hidden" value="{$TOKEN}" name="token" />
        </form>
    </div>
    <div class="actions">
        <a class="ui negative button">{$CANCEL}</a>

        <a type="submit" class="ui positive button" onclick="document.getElementById('sendCredits').submit()">{$SEND_CREDITS}</a>
    </div>
</div>
{/if}

{include file='footer.tpl'}