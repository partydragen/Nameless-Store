{if count($FEATURED_PRODUCTS_LIST)}
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900" id="widget-featured-products">
    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/30">
        <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {$FEATURED_PRODUCTS}
        </h4>
    </div>

    <div class="p-5">
        <div class="space-y-4">
            {foreach from=$FEATURED_PRODUCTS_LIST item=product}
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/30">
                    {if $product.image}
                        <a href="{$product.link}" class="block">
                            <img src="{$product.image}"
                                 alt="{$product.name}"
                                 class="w-full object-cover">
                        </a>
                    {/if}

                    <div class="p-4">
                        <a href="{$product.link}"
                           class="block text-sm font-semibold text-slate-900 hover:underline dark:text-slate-100">
                            {$product.name}
                        </a>

                        {if isset($product.description)}
                            <div class="mt-2 line-clamp-3 text-sm text-slate-600 dark:text-slate-300">
                                {$product.description}
                            </div>
                        {/if}

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {if isset($product.sale_active) && $product.sale_active}
                                    <span class="mr-1 text-rose-600 line-through dark:text-rose-400">
                                        {$product.price_format}
                                    </span>
                                {/if}
                                <span>{$product.real_price_format}</span>
                            </div>

                            <a href="{$product.link}"
                               class="inline-flex items-center justify-center rounded-xl bg-{$PRIMARY_COLOR}-600 px-3 py-2 text-xs font-semibold text-white hover:bg-{$PRIMARY_COLOR}-500">
                                {$VIEW}
                            </a>
                        </div>
                    </div>
                </article>
            {/foreach}
        </div>
    </div>
</section>
{/if}