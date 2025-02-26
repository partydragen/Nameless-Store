<form action="" method="post">
    <div class="row">
        <div class="col-md-12">

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

        </div>
        <div class="col-md-2">
            <label for="inputType">Request Type</label>
            <select name="http_type" class="form-control" id="inputType">
                <option value="GET" {if $HTTP_TYPE_VALUE == 'GET'} selected{/if}>GET</option>
                <option value="POST" {if $HTTP_TYPE_VALUE == 'POST'} selected{/if}>POST</option>
                <option value="PUT" {if $HTTP_TYPE_VALUE == 'PUT'} selected{/if}>PUT</option>
                <option value="PATCH" {if $HTTP_TYPE_VALUE == 'PATCH'} selected{/if}>PATCH</option>
                <option value="DELETE" {if $HTTP_TYPE_VALUE == 'DELETE'} selected{/if}>DELETE</option>
            </select>
        </div>
        <div class="col-md-10">
            <div class="form-group">
                <label for="inputURL">URL</label>
                <input type="text" class="form-control" id="inputURL" name="http_url" value="{$HTTP_URL_VALUE}" placeholder="https://www.example.com/api/v2/users/{literal}{user}{/literal}/groups/add">
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="inputHeaders">Headers (One per line)</label>
                <textarea id="inputHeaders" class="form-control" name="http_headers" placeholder="Authorization=Bearer {literal}{token}{/literal}" rows="4">{$HTTP_HEADERS_VALUE}</textarea>
            </div>

            <div class="form-group">
                <label for="inputBody">Body</label>
                <textarea id="inputBody" class="form-control" name="http_body" placeholder="{$BODY_JSON}" rows="4">{$HTTP_BODY_VALUE}</textarea>
            </div>

            <div class="form-group">
                <input type="hidden" name="token" value="{$TOKEN}">
                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
            </div>
        </div>
    </div>

</form>