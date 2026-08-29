<div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/30 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                {$STORE} &raquo; {if isset($ACTIVE_CATEGORY)}{$ACTIVE_CATEGORY}{else}{$CHECKOUT}{/if}
            </h1>
        </div>

        {if isset($STORE_PLAYER)}
            <div class="flex shrink-0 items-center justify-end">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    <i class="fas fa-user"></i> {$STORE_PLAYER}
                </span>

                <form action="" method="post" class="inline">
                    <input type="hidden" name="token" value="{$TOKEN}">
                    <input type="hidden" name="type" value="store_logout">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                        {$LOGOUT}
                    </button>
                </form>
            </div>
        {/if}
    </div>

    <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            {foreach from=$CATEGORIES item=category}
                {if isset($category.subcategories) && count($category.subcategories)}
                    <details class="relative">
                        <summary class="list-none cursor-pointer rounded-xl px-3 py-2 text-sm font-semibold
                            {if $category.active}bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white{else}text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800{/if}">
                            <span class="inline-flex items-center gap-2">
                                {$category.title}
                                <i class="fas fa-chevron-down text-xs text-slate-400 dark:text-slate-500"></i>
                            </span>
                        </summary>

                        <div class="absolute left-0 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900">
                            <div class="p-2">
                                {if !$category.only_subcategories}
                                    <a class="block rounded-lg px-3 py-2 text-sm font-semibold
                                        {if $category.active}bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white{else}text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800{/if}"
                                       href="{$category.url}">
                                        {$category.title}
                                    </a>
                                {/if}

                                {foreach from=$category.subcategories item=subcategory}
                                    <a class="block rounded-lg px-3 py-2 text-sm font-semibold
                                        {if $subcategory.active}bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white{else}text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800{/if}"
                                       href="{$subcategory.url}">
                                        {$subcategory.title}
                                    </a>
                                {/foreach}
                            </div>
                        </div>
                    </details>
                {else}
                    <a class="rounded-xl px-3 py-2 text-sm font-semibold
                        {if $category.active}bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white{else}text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800{/if}"
                       href="{$category.url}">
                        {$category.title}
                    </a>
                {/if}
            {/foreach}
        </div>

        <div class="flex shrink-0 items-center justify-end">
            {if count($SHOPPING_CART_PRODUCTS)}
                <a href="{$CHECKOUT_LINK}"
                   class="inline-flex items-center justify-center rounded-xl bg-{$PRIMARY_COLOR}-600 px-4 py-2 text-sm font-semibold text-white hover:bg-{$PRIMARY_COLOR}-500">
                    <i class="fas fa-shopping-cart mr-2"></i>{$X_ITEMS_FOR_Y}
                </a>
            {else}
                <span class="inline-flex cursor-not-allowed items-center justify-center rounded-xl bg-{$PRIMARY_COLOR}-600/60 px-4 py-2 text-sm font-semibold text-white">
                    <i class="fas fa-shopping-cart mr-2"></i>{$X_ITEMS_FOR_Y}
                </span>
            {/if}
        </div>
    </div>
</div>