<form role="form" action="" method="post">
    <div class="form-group">
        <label for="InputName">{$NAME}</label>
        <input type="text" name="name" class="form-control" id="InputName" placeholder="{$NAME}" value="{$NAME_VALUE}">
    </div>
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>