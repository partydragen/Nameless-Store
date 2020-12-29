<div class="callout callout-info">
    <h5><i class="icon fa fa-info-circle"></i> {$INFO}</h5>
    {$FEATURED_PACKAGE_INFO}
</div>

<form action="" method="post">
    <div class="form-group">
        <label for="inputFeaturedPackages">{$FEATURED_PACKAGES}</label> <small>{$SELECT_MULTIPLE_WITH_CTRL}</small>
        <select class="form-control" name="featured_packages[]" id="inputFeaturedPackages" multiple>
            {foreach from=$PACKAGES item=package}
                <option value="{$package.value}"{if $package.selected} selected{/if}>{$package.name}</option>
            {/foreach}
        </select>
    </div>
    <div type="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>