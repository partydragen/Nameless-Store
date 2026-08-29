{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="grid gap-6 lg:grid-cols-12">
    {if count($WIDGETS_LEFT)}
        <aside class="space-y-6 lg:col-span-3">
            {foreach from=$WIDGETS_LEFT item=widget}
                {$widget}
            {/foreach}
        </aside>
    {/if}

    <main class="{if count($WIDGETS_LEFT) && count($WIDGETS_RIGHT)}lg:col-span-6{elseif count($WIDGETS_LEFT) || count($WIDGETS_RIGHT)}lg:col-span-9{else}lg:col-span-12{/if}">
        {include file='store/navbar.tpl'}

        <section class="">
            {if isset($SUCCESS)}
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-soft dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 text-emerald-600 dark:text-emerald-300"><i class="fas fa-check-circle"></i></div>
                        <div class="min-w-0 text-sm font-semibold">{$SUCCESS}</div>
                    </div>
                </div>
            {/if}

            {if isset($ERRORS)}
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-soft dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 text-rose-600 dark:text-rose-300"><i class="fas fa-times-circle"></i></div>
                        <div class="min-w-0 text-sm">
                            {foreach from=$ERRORS item=error}
                                <div class="font-semibold">{$error}</div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}


            {if $CONTENT}
                <div class="prose-nameless prose-slate max-w-none text-slate-700 dark:prose-invert dark:text-slate-200">
                    {$CONTENT}
                </div>
                <div class="my-6 h-px bg-slate-200 dark:bg-slate-800"></div>
            {/if}

            {if isset($NO_PRODUCTS)}
                {if empty($CONTENT)}
                    <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        {$NO_PRODUCTS}
                    </div>
                {/if}
            {else}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {foreach from=$PRODUCTS item=product}
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900"
                                 id="product-{$product.id}">
                            {if $product.image}
                                <div class="relative">
                                    {if $product.sale_active}
                                        <span class="absolute right-3 top-3 rounded-full bg-rose-600 px-3 py-1 text-xs font-semibold text-white">
                                            {$SALE}
                                        </span>
                                    {/if}
                                    <img class="w-full object-cover"
                                         src="{$product.image}" alt="{$product.name}">
                                </div>
                            {/if}

                            <div class="p-5">
                                <div class="text-base text-center font-semibold text-slate-900 dark:text-slate-100">
                                    {$product.name}
                                </div>

                                <div class="mt-3 text-sm text-center text-slate-700 dark:text-slate-200">
                                    {if $product.has_discount}
                                        <span class="text-rose-600 line-through dark:text-rose-400">
                                            {$product.price_format}
                                        </span>
                                    {/if}
                                    <span class="font-semibold">{$product.real_price_format}</span>
                                </div>

                                <div class="mt-4">
                                    <button type="button"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-{$PRIMARY_COLOR}-600 px-4 py-2 text-sm font-semibold text-white hover:bg-{$PRIMARY_COLOR}-500"
                                            onclick="document.getElementById('modal{$product.id}').showModal()">
                                        {$BUY} &raquo;
                                    </button>
                                </div>
                            </div>
                        </article>

                        <dialog id="modal{$product.id}" class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-soft backdrop:bg-black/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
                            <div class="p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="text-lg font-semibold">
                                            {$product.name}
                                        </div>
                                        <div class="mt-2 text-sm">
                                            {if $product.has_discount}
                                                <span class="text-rose-600 line-through dark:text-rose-400">{$product.price_format}</span>
                                            {/if}
                                            <span class="font-semibold">{$product.real_price_format}</span>
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                            onclick="document.getElementById('modal{$product.id}').close()"
                                            aria-label="{$CLOSE}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <div class="mt-5 grid gap-5 {if $product.image}sm:grid-cols-5{/if}">
                                    {if $product.image}
                                        <div class="sm:col-span-2">
                                            <img class="w-full rounded-xl border border-slate-200 dark:border-slate-800"
                                                 src="{$product.image}" alt="{$product.name}">
                                        </div>
                                    {/if}
                                    <div class="forum_post prose-nameless prose-slate max-w-none text-slate-700 dark:prose-invert dark:text-slate-200 {if $product.image}sm:col-span-3{/if}">
                                        {$product.description}
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-wrap justify-end gap-2">
                                    <button type="button"
                                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                            onclick="document.getElementById('modal{$product.id}').close()">
                                        {$CLOSE}
                                    </button>

                                    {if $product.subscribe_link != null}
                                        <a class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                                           href="{$product.subscribe_link}">
                                            {$SUBSCRIBE}
                                            <i class="fas fa-shopping-cart ml-2"></i>
                                        </a>
                                    {/if}

                                    {if $product.link != null}
                                        <a class="inline-flex items-center justify-center rounded-xl bg-{$PRIMARY_COLOR}-600 px-4 py-2 text-sm font-semibold text-white hover:bg-{$PRIMARY_COLOR}-500"
                                           href="{$product.link}">
                                            {$ADD_TO_CART}
                                            <i class="fas fa-shopping-cart ml-2"></i>
                                        </a>
                                    {/if}
                                </div>
                            </div>
                        </dialog>
                    {/foreach}
                </div>
            {/if}
        </section>
    </main>

    {if count($WIDGETS_RIGHT)}
        <aside class="space-y-6 lg:col-span-3">
            {if isset($LOGGED_IN_USER) && isset($SHOW_CREDITS_AMOUNT)}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900" id="widget-store-account">
                    <div class="px-5 py-4">
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            {$ACCOUNT}
                        </h4>

                        <div class="mt-4 text-sm">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/30">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{$CREDITS}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{$CREDITS_FORMAT_VALUE}</span>
                            </div>
                        </div>
                    </div>
                </section>
            {/if}

            {foreach from=$WIDGETS_RIGHT item=widget}
                {$widget}
            {/foreach}
        </aside>
    {/if}
</div>

{include file='footer.tpl'}