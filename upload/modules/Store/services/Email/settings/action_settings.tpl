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
        <label for="inputEmailSubject">Email Subject</label>
        <input type="text" class="form-control" id="inputEmailSubject" name="subject" value="{$EMAIL_SUBJECT_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputEmailContent">Email Content</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">Placeholders</a>
        <textarea id="inputEmailContent" name="content">{$EMAIL_CONTENT_VALUE}</textarea>
    </div>
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>