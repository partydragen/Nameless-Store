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

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                {$SHOPPING_CART}
            </h3>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-950/30 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">{$NAME}</th>
                        <th class="px-4 py-3">{$OPTIONS}</th>
                        <th class="px-4 py-3">{$QUANTITY}</th>
                        <th class="px-4 py-3">{$PRICE}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                    {foreach from=$SHOPPING_CART_LIST item=item}
                        <tr class="text-slate-700 dark:text-slate-200">
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{$item.name}</td>
                            <td class="px-4 py-3">
                                {if count($item.fields)}
                                    {foreach from=$item.fields item=field name=fields}
                                        <div>
                                            <strong>{$field.description}</strong>: {$field.value}
                                        </div>
                                    {/foreach}
                                {/if}
                            </td>
                            <td class="px-4 py-3">{$item.quantity}</td>
                            <td class="px-4 py-3">
                                {if $item.has_discount}
                                    <span class="text-rose-600 line-through dark:text-rose-400">{$item.price_format}</span>
                                {/if}
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{$item.real_price_format}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{$item.remove_link}"
                                   class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-600 text-white hover:bg-rose-500"
                                   aria-label="{$REMOVE}">
                                    <i class="fas fa-times"></i>
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-950/30">
                    <dl class="space-y-2">
                        {if $TOTAL_DISCOUNT_VALUE > 0}
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600 dark:text-slate-300">{$TOTAL_PRICE}</dt>
                                <dd class="font-semibold text-slate-900 dark:text-slate-100">{$TOTAL_PRICE_FORMAT_VALUE}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600 dark:text-slate-300">{$TOTAL_DISCOUNT}</dt>
                                <dd class="font-semibold text-slate-900 dark:text-slate-100">{$TOTAL_DISCOUNT_FORMAT_VALUE}</dd>
                            </div>
                        {/if}
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-600 dark:text-slate-300">{$PRICE_TO_PAY}</dt>
                            <dd id="store-checkout-total" class="text-base font-bold text-slate-900 dark:text-slate-100">{$TOTAL_REAL_PRICE_FORMAT_VALUE}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/20">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {$REDEEM_COUPON}
                    </h3>

                    <form action="{$REDEEM_COUPON_URL}" method="post" id="coupon" class="mt-3 flex gap-2">
                        <input type="text"
                               name="coupon"
                               value="{$REDEEM_COUPON_VALUE}"
                               placeholder="{$REDEEM_COUPON_HERE}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100">

                        <input type="hidden" name="token" value="{$TOKEN}">
                        <button type="submit"
                                class="inline-flex items-center justify-center whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                            {$REDEEM} &raquo;
                        </button>
                    </form>
                </div>
            </div>

            <div class="my-6 h-px bg-slate-200 dark:bg-slate-800"></div>

            <form action="" method="post" id="forms" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        {$PAYMENT_METHOD}
                    </h3>

                    <div class="mt-4 space-y-2">
                        {foreach from=$PAYMENT_METHODS item=gateway}
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-200 dark:hover:bg-slate-800">
                                <input class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950/30"
                                       type="radio" name="payment_method" value="{$gateway.name}" required>
                                <span class="font-semibold">{$gateway.displayname}</span>
                            </label>
                        {/foreach}
                    </div>
                </div>

                {if $CAN_SPLIT_PAYMENT}
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm dark:border-indigo-900/50 dark:bg-indigo-950/30">
                        <label class="flex cursor-pointer items-start gap-3 text-slate-800 dark:text-slate-100">
                            <input id="store-use-credits"
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950/30"
                                   type="checkbox" name="use_credits" value="1"{if $SPLIT_CREDITS_CHECKED} checked{/if}>
                            <span>
                                <span class="block font-semibold">{$USE_CREDITS}</span>
                                <span class="mt-1 block text-slate-600 dark:text-slate-300">{$USE_CREDITS_HELP}</span>
                            </span>
                        </label>

                        <dl class="mt-4 space-y-2 border-t border-indigo-200 pt-3 dark:border-indigo-900/50">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600 dark:text-slate-300">{$CREDITS_AVAILABLE}</dt>
                                <dd class="font-semibold">{$SPLIT_CREDITS_AVAILABLE_FORMAT}</dd>
                            </div>
                            <div class="store-credit-breakdown flex items-center justify-between gap-3{if !$SPLIT_CREDITS_CHECKED} hidden{/if}">
                                <dt class="text-slate-600 dark:text-slate-300">{$CREDITS_APPLIED}</dt>
                                <dd class="font-semibold text-indigo-700 dark:text-indigo-300">-{$SPLIT_CREDITS_APPLIED_FORMAT}</dd>
                            </div>
                            <div class="store-credit-breakdown flex items-center justify-between gap-3{if !$SPLIT_CREDITS_CHECKED} hidden{/if}">
                                <dt class="font-semibold text-slate-800 dark:text-slate-100">{$REMAINING_TO_PAY}</dt>
                                <dd class="font-bold text-slate-900 dark:text-white">{$SPLIT_REMAINING_FORMAT}</dd>
                            </div>
                        </dl>
                    </div>
                {/if}

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-950/30">
                    <label class="flex cursor-pointer items-start gap-2 text-slate-700 dark:text-slate-200">
                        <input class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-950/30"
                               type="checkbox" name="t_and_c" value="1" required>
                        <span class="font-semibold">{$AGREE_T_AND_C_PURCHASE}</span>
                    </label>
                </div>

                <input type="hidden" name="token" value="{$TOKEN}">

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        {if isset($CHECKOUT_SUBSCRIBE)}{$CHECKOUT_SUBSCRIBE}{else}{$PURCHASE}{/if} &raquo;
                    </button>
                </div>
            </form>
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

{if $CAN_SPLIT_PAYMENT}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById('store-use-credits');
            const total = document.getElementById('store-checkout-total');
            const breakdown = document.querySelectorAll('.store-credit-breakdown');
            if (!checkbox) return;

            const refreshCredits = function () {
                breakdown.forEach(function (row) {
                    row.classList.toggle('hidden', !checkbox.checked);
                });
                if (total) {
                    total.innerHTML = checkbox.checked ? '{$SPLIT_REMAINING_FORMAT|escape:"javascript"}' : '{$SPLIT_TOTAL_FORMAT|escape:"javascript"}';
                }
            };

            checkbox.addEventListener('change', refreshCredits);
            refreshCredits();
        });
    </script>
{/if}

{include file='footer.tpl'}
