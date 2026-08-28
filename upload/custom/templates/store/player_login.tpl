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

        <section class="rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <div class="p-6">
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-950/20">
                    {if isset($ERRORS)}
                        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-soft dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 text-rose-600 dark:text-rose-300">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="min-w-0 text-sm">
                                    {foreach from=$ERRORS item=error}
                                        {$error}<br />
                                    {/foreach}
                                </div>
                            </div>
                        </div>
                    {/if}

                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        {$PLEASE_ENTER_USERNAME}
                    </h3>

                    <form action="" method="post" class="mt-4 space-y-5">
                        <div>
                            <input type="text"
                                   name="username"
                                   id="username"
                                   placeholder="{$PLEASE_ENTER_USERNAME}"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100 dark:placeholder:text-slate-500">
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <input type="hidden" name="token" value="{$TOKEN}">
                            <input type="hidden" name="type" value="store_login">
                            <input type="submit"
                                   class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-{$PRIMARY_COLOR}-600 px-4 py-2 text-sm font-semibold text-white hover:bg-{$PRIMARY_COLOR}-500"
                                   value="{$CONTINUE} &raquo;">
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    {if count($WIDGETS_RIGHT)}
        <aside class="space-y-6 lg:col-span-3">
            {if isset($LOGGED_IN_USER) && isset($SHOW_CREDITS_AMOUNT)}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900" id="widget-store-account">
                    <div class="px-5 py-4">
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{$ACCOUNT}</h4>
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-950/30">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{$CREDITS}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">
                              {$CURRENCY_SYMBOL}{$CREDITS_VALUE} {$CURRENCY}
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