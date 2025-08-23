<div class="alert bg-info text-white">
    <h5><i class="icon fa fa-info-circle"></i> {$INFO}</h5>
    {$WIDGET_CACHED}
</div>

<form action="" method="post">
    <div class="form-group">
        <label for="inputFeaturedProducts">{$FEATURED_PRODUCTS}</label>
        <select class="form-control" name="featured_products[]" id="inputFeaturedProducts" multiple>
            {foreach from=$PRODUCTS_LIST item=product}
                <option value="{$product.value}"{if $product.selected} selected{/if}>{$product.name}</option>
            {/foreach}
        </select>
    </div>
    <div type="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>