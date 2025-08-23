<div class="ui fluid card" id="widget-featured-products">
    <div class="content">
        <h4 class="ui header">{$FEATURED_PRODUCTS}</h4>
        {foreach from=$FEATURED_PRODUCTS_LIST item=product}
        <div class="ui card">
            {if $product.image}
                <div class="image">
                    {if $product.sale_active}
                        <span class="ui right ribbon red label">
                            {$SALE}
                        </span>
                    {/if}
                    <img src="{$product.image}" alt="{$product.name}">
                </div>
            {/if}
            <div class="center aligned content">
                <div class="description">
                    <div class="ui list">
                        <div class="item">
                            <span class="header">{$product.name}</span>
                            <div class="ui divider"></div>
                            {if $product.has_discount}
                                <span style="color: #dc3545;text-decoration:line-through;">{$product.price_format}</span>
                            {/if}
                            <span class="text">{$product.real_price_format}</span>
                        </div>
                    </div>
                </div>
            </div>
            <a class="ui bottom attached blue button" href="{$product.link}">
                {$VIEW} &raquo;
            </a>
        </div>
        {/foreach}
    </div>
</div>