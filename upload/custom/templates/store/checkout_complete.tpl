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

    <main class="{if count($WIDGETS_LEFT) && count($WIDGETS_RIGHT) }lg:col-span-6{elseif count($WIDGETS_LEFT) || count($WIDGETS_RIGHT)}lg:col-span-9{else}lg:col-span-12{/if}">
        {include file='store/navbar.tpl'}

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900 shadow-soft dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 text-emerald-600 dark:text-emerald-300">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-lg font-semibold">
                            Checkout Complete
                        </div>

                        <div class="prose-nameless prose-emerald mt-3 max-w-none text-emerald-900 dark:prose-invert dark:text-emerald-100">
                            {$CHECKOUT_COMPLETE_CONTENT}
                        </div>
                    </div>
                </div>
            </div>
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

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-950/30">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">
                                    {$CREDITS}
                                </span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">
                                    <b>{$CURRENCY_SYMBOL}{$CREDITS_VALUE} {$CURRENCY}</b>
                                </span>
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