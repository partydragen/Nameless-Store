<form action="" method="post">
    <div class="form-group">
        <label for="inputTrigger">Trigger On</label>
        <select name="trigger" class="form-control" id="inputTrigger">
            <option value="1" {if $TRIGGER_VALUE == 1} selected{/if}>Purchase</option>
            <option value="2" {if $TRIGGER_VALUE == 2} selected{/if}>Refund</option>
            <option value="3" {if $TRIGGER_VALUE == 3} selected{/if}>Changeback</option>
            <option value="4" {if $TRIGGER_VALUE == 4} selected{/if}>Renewal</option>
            <option value="5" {if $TRIGGER_VALUE == 5} selected{/if}>Expire</option>
        </select>
    </div>
    <div class="form-group">
        <label for="inputConnections">{$SERVICE_CONNECTIONS}</label>
        <select name="connections[]" id="inputConnections" size="3" class="form-control" multiple style="overflow:auto;" required>
            {foreach from=$CONNECTIONS_LIST item=connection}
                <option value="{$connection.id}"{if $connection.selected} selected{/if}>{$connection.name}</option>
            {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label for="InputCommand">MySQL Command</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">Placeholders</a></br>
        <input type="text" name="command" class="form-control" id="InputCommand" value="{$COMMAND_VALUE}" placeholder="{literal}INSERT INTO your_table (`column1`, `column2`, `column3`) VALUES ({orderId}, {productId}, '{uuid}'){/literal}">
    </div>
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>