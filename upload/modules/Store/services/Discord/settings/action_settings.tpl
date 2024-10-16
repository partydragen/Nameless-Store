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
        <label for="inputAddRoles">Add any roles to user?</label>
        <select name="add_roles[]" id="inputAddRoles" class="form-control" multiple>
            {foreach from=$ALL_ROLES item=item}
                <option value="{$item.id}"{if in_array($item.id, $ADD_ROLES_VALUE)} selected{/if}>{$item.name|escape}</option>
            {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label for="inputRemoveRoles">Remove any roles from user?</label>
        <select name="remove_roles[]" id="inputRemoveRoles" class="form-control" multiple>
            {foreach from=$ALL_ROLES item=item}
                <option value="{$item.id}"{if in_array($item.id, $REMOVE_ROLES_VALUE)} selected{/if}>{$item.name|escape}</option>
            {/foreach}
        </select>
    </div>
    <br /><br />

    <h5 style="display:inline">Webhook Message</h5><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">{$VIEW_PLACEHOLDERS}</a>
    <hr>
    <div class="form-group">
        <label for="inputWebhookURL">Discord Webhook URL</label>
        <input type="text" class="form-control" id="inputWebhookURL" name="webhook_url" value="{$WEBHOOK_URL_VALUE}" placeholder="Discord Webhook URL">
    </div>
    <div class="form-group">
        <label for="inputWebhookContent">Content</label>
        <textarea id="inputWebhookContent" class="form-control" name="webhook_content" placeholder="Content message (Can be empty)">{$WEBHOOK_CONTENT_VALUE}</textarea>
    </div>
    <label for="inputWebhookContent">Embed</label>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="form-group">
                <label for="inputEmbedTitle">Embed Title</label>
                <input type="text" class="form-control" id="inputEmbedTitle" name="embed_title" value="{$WEBHOOK_EMBED_TITLE_VALUE}" placeholder="Embed title">
            </div>
            <div class="form-group">
                <label for="inputEmbedContent">Embed Content</label>
                <textarea id="inputEmbedContent" class="form-control" name="embed_content" placeholder="Embed content (Can be empty)" rows="4">{$WEBHOOK_EMBED_CONTENT_VALUE}</textarea>
            </div>
            <div class="form-group">
                <label for="inputEmbedFooter">Embed Footer</label>
                <input type="text" class="form-control" id="inputEmbedFooter" name="embed_footer" value="{$WEBHOOK_EMBED_FOOTER_VALUE}" placeholder="Embed footer">
            </div>
        </div>
    </div>

    <hr>
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