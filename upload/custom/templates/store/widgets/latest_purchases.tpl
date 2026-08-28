<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft dark:border-slate-800 dark:bg-slate-900" id="widget-latest-purchases">
    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/30">
        <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {$LATEST_PURCHASES}
        </h4>
    </div>

    <div class="p-5">
        {if  isset($LATEST_PURCHASES_LIST) && count($LATEST_PURCHASES_LIST)}
            <div class="space-y-3">
                {foreach from=$LATEST_PURCHASES_LIST item=purchase}
                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/30">
                        {if isset($purchase.avatar)}
                            <img src="{$purchase.avatar}"
                                 alt="{$purchase.username}"
                                 class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-800">
                        {else}
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                        {/if}

                        <div class="min-w-0 flex-1">
                            <div class="text-sm text-slate-700 dark:text-slate-200">
                                {if isset($purchase.profile)}
                                    <a href="{$purchase.profile}"
                                       {if isset($purchase.style)}style="{$purchase.style}"{/if}
                                            {if isset($purchase.user_id)}data-poload="{$USER_INFO_URL}{$purchase.user_id}"{/if}
                                       class="font-semibold hover:underline">
                                        {$purchase.username}
                                    </a>
                                {else}
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">
                                        {$purchase.username}
                                    </span>
                                {/if}

                                <br />

                                <span class="font-semibold text-slate-900 dark:text-slate-100 text-xs">
                                    {$purchase.price_format} &bull; {$purchase.description}
                                </span>
                            </div>

                            {if isset($purchase.date)}
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                                     {if isset($purchase.date_full)}data-toggle="tooltip" data-content="{$purchase.date_full}"{/if}>
                                    {$purchase.date}
                                </div>
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-200">
                {$NO_PURCHASES}
            </div>
        {/if}
    </div>
</section>