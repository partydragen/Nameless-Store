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
        <label for="inputEmailSubject">Email Subject</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">{$VIEW_PLACEHOLDERS}</a>
        <input type="text" class="form-control" id="inputEmailSubject" name="subject" value="{$EMAIL_SUBJECT_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputEmailContent">Email Content</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">{$VIEW_PLACEHOLDERS}</a>
        <textarea id="inputEmailContent" name="content">{$EMAIL_CONTENT_VALUE}</textarea>
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