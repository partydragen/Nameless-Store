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
        <label for="inputAddGroups">Add any groups to user?</label>
        <select name="add_groups[]" id="inputAddGroups" class="form-control" multiple>
            {foreach from=$ALL_GROUPS item=item}
                <option value="{$item->id}"{if in_array($item->id, $ADD_GROUPS_VALUE)} selected{/if}>{$item->name|escape}</option>
            {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label for="inputRemoveGroups">Remove any groups from user?</label>
        <select name="remove_groups[]" id="inputRemoveGroups" class="form-control" multiple>
            {foreach from=$ALL_GROUPS item=item}
                <option value="{$item->id}"{if in_array($item->id, $REMOVE_GROUPS_VALUE)} selected{/if}>{$item->name|escape}</option>
            {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label for="inputRemoveCredits">Add Credits to user?</label>
        <input type="number" class="form-control" id="inputRemoveCredits" name="add_credits" value="{$ADD_CREDITS_VALUE}" step="0.01" min="0.00" value="0.00">
    </div>
    <div class="form-group">
        <label for="inputAddCredits">Remove Credits from user?</label>
        <input type="number" class="form-control" id="inputAddCredits" name="remove_credits" value="{$REMOVE_CREDITS_VALUE}" step="0.01" min="0.00" value="0.00">
    </div>
    <div class="form-group">
        <label for="inputAlert">Send notification to user?</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">{$VIEW_PLACEHOLDERS}</a>
        <input type="text" class="form-control" id="inputAlert" name="alert" value="{$ALERT_VALUE}" placeholder="Send notification to user?">
    </div>
    <div class="form-group">
        <label for="inputAddTrophies">Reward trophies to user?</label>
        {if isset($TROPHIES_LIST)}
            <select name="add_trophies[]" id="inputAddTrophies" class="form-control" multiple>
                {foreach from=$TROPHIES_LIST item=item}
                    <option value="{$item.id}"{if in_array($item.id, $ADD_TROPHIES_VALUE)} selected{/if}>{$item.id} - {$item.title}</option>
                {/foreach}
            </select>
        {else}
            <input type="text" class="form-control" id="inputTrophies" name="trophies" value="Trophies module not installed or enabled" readonly>
        {/if}
    </div>
    <div class="form-group custom-control custom-switch">
        <input id="inputEachQuantity" name="each_quantity" type="checkbox" class="custom-control-input"{if $EACH_QUANTITY_VALUE eq 1} checked{/if} />
        <label class="custom-control-label" for="inputEachQuantity">Run action for each quantity</label> <span
                class="badge badge-info"><i class="fas fa-question-circle"
                                            data-container="body" data-toggle="popover"
                                            data-placement="top" title="Info"
                                            data-content="Run the action for every quantity the user purchased if enabled, Otherwise the action will only be run once (Note: It will still run for each service connection)"></i></span>
    </div>
    {if $ACTION_TYPE != 'product'}
        <div class="form-group custom-control custom-switch">
            <input id="inputEachProduct" name="each_product" type="checkbox" class="custom-control-input"{if $EACH_PRODUCT_VALUE eq 1} checked{/if} />
            <label class="custom-control-label" for="inputEachProduct">Run action for each product</label> <span
                    class="badge badge-info"><i class="fas fa-question-circle"
                                                data-container="body" data-toggle="popover"
                                                data-placement="top" title="Info"
                                                data-content="Run action for every product the user purchased if enabled, Otherwise the action will only be run once (Warning the product placeholders wont work)"></i></span>
        </div>
    {/if}
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>