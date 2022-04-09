<div class="callout callout-info">
    <h5><i class="icon fa fa-info-circle"></i> {$INFO}</h5>
    {$WIDGET_CACHED}
</div>

<form action="" method="post">
    <div class="form-group">
        <label for="inputPackageLimit">{$LATEST_PURCHASES_LIMIT}</label>
        <input id="inputPackageLimit" name="limit" type="number" min="1" class="form-control" placeholder="{$LATEST_PURCHASES_LIMIT}" value="{$LATEST_PURCHASES_LIMIT_VALUE}">
    </div>
    <div type="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>