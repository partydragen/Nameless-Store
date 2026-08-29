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

        <section class="rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <div class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        {$STORE} &raquo; {$PRODUCT_NAME}
                    </h1>
                </div>

                <div class="mt-6">
                    {if isset($SUCCESS)}
                        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-soft dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 text-emerald-600 dark:text-emerald-300">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="min-w-0 text-sm font-semibold">
                                    {$SUCCESS}
                                </div>
                            </div>
                        </div>
                    {/if}

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

                    <form action="" method="post" id="forms" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{$PRODUCT_NAME}</h3>
                            <div class="mt-4 h-px bg-slate-200 dark:bg-slate-800"></div>
                        </div>

                        {foreach from=$PRODUCT_FIELDS item=field}
                            <div>
                                <label for="{$field.id}" class="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {$field.description}
                                    {if $field.required}
                                        <span class="text-rose-600"><strong>*</strong></span>
                                    {/if}
                                </label>

                                {if $field.type == "1"}
                                    <input type="text"
                                           name="{$field.id}"
                                           id="{$field.id}"
                                           value="{$field.value}"
                                           placeholder="{$field.description}"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100 dark:placeholder:text-slate-500"
                                           {if $field.required}required{/if}>
                                {elseif $field.type == "2"}
                                    <select name="{$field.id}"
                                            id="{$field.id}"
                                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100"
                                            {if $field.required}required{/if}>
                                        <option value="">{$field.description}</option>
                                        {foreach from=$field.selections item=option}
                                            <option value="{$option.value}" {if $option.value eq $field.value} selected{/if}>
                                                {$option.description} {if isset($option.price)}({$option.price}){/if}
                                            </option>
                                        {/foreach}
                                    </select>
                                {elseif $field.type == "3"}
                                    <textarea name="{$field.id}"
                                              id="{$field.id}"
                                              class="mt-2 min-h-24 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-soft outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100"
                                              {if $field.required}required{/if}>{$field.value}</textarea>
                                {elseif $field.type == "4"}
                                    <input type="number"
                                           name="{$field.id}"
                                           id="{$field.id}"
                                           value="{$field.value}"
                                           placeholder="{$field.description}"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100 dark:placeholder:text-slate-500"
                                           {if $field.required}required{/if}>
                                {elseif $field.type == "5"}
                                    <input type="email"
                                           name="{$field.id}"
                                           id="{$field.id}"
                                           value="{$field.value}"
                                           placeholder="{$field.description}"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100 dark:placeholder:text-slate-500"
                                           {if $field.required}required{/if}>
                                {elseif $field.type == "6"}
                                    <div class="mt-3 space-y-2">
                                        {foreach from=$field.selections item=option}
                                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-200 dark:hover:bg-slate-800">
                                                <input type="radio"
                                                       name="{$field.id}"
                                                       value="{$option.value}"
                                                       class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950/30"
                                                       {if $field.required}required{/if}
                                                        {if $option.value eq $field.value} checked{/if}>
                                                <span class="font-semibold">
                          {$option.description} {if isset($option.price)}({$option.price}){/if}
                        </span>
                                            </label>
                                        {/foreach}
                                    </div>
                                {elseif $field.type == "7"}
                                    <div class="mt-3 space-y-2">
                                        {foreach from=$field.selections item=option}
                                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-200 dark:hover:bg-slate-800">
                                                <input type="checkbox"
                                                       name="{$field.id}[]"
                                                       value="{$option.value}"
                                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950/30">
                                                <span class="font-semibold">
                          {$option.description} {if isset($option.price)}({$option.price}){/if}
                        </span>
                                            </label>
                                        {/foreach}
                                    </div>
                                {/if}
                            </div>
                        {/foreach}

                        <div class="flex items-center justify-end gap-2">
                            <input type="hidden" name="token" value="{$TOKEN}">
                            <input type="submit"
                                   class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                                   value="{$CONTINUE}">
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