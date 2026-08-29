{include file='header.tpl'}
{include file='navbar.tpl'}

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

<div class="grid gap-6 lg:grid-cols-12" id="user">
    <aside class="lg:col-span-3">
        {include file='user/navigation.tpl'}
    </aside>

    <main class="lg:col-span-9 space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {$STORE}
                </h3>

                {if isset($CAN_SEND_CREDITS)}
                    <button type="button"
                            onclick="document.getElementById('modal-send-credits').showModal()"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        {$SEND_CREDITS}
                    </button>
                {/if}
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/30">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {$CREDITS}
                    </span>
                    <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {$CREDITS_FORMAT_VALUE}
                    </span>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                {$MY_TRANSACTIONS}
            </h3>

            <div class="mt-5">
                {nocache}
                    {if count($TRANSACTIONS_LIST)}
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-950/30 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">{$TRANSACTION}</th>
                                    <th class="px-4 py-3">{$AMOUNT}</th>
                                    <th class="px-4 py-3">{$DATE}</th>
                                </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                {foreach from=$TRANSACTIONS_LIST item=transaction}
                                    <tr class="text-slate-700 dark:text-slate-200">
                                        <td class="px-4 py-3">
                                            {$transaction.transaction}
                                        </td>

                                        <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400">
                                            {$transaction.amount_format}
                                        </td>

                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                                <span data-toggle="tooltip" data-content="{$transaction.date_full}">
                                                    {$transaction.date_friendly}
                                                </span>
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-200">
                            {$NO_TRANSACTIONS}
                        </div>
                    {/if}
                {/nocache}
            </div>
        </section>
    </main>
</div>

{if isset($CAN_SEND_CREDITS)}
    <dialog id="modal-send-credits" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-soft backdrop:bg-black/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h3 class="text-lg font-semibold">
                {$SEND_CREDITS}
            </h3>

            <button type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    onclick="document.getElementById('modal-send-credits').close()"
                    aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6">
            <form id="sendCredits" action="" method="post" class="space-y-5">
                <input type="hidden" name="token" value="{$TOKEN}">

                <div>
                    <label class="block text-sm font-semibold text-slate-900 dark:text-slate-100" for="InputTo">
                        {$TO}
                    </label>

                    <select name="to"
                            id="InputTo"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100">
                        <option value="">{$TO}</option>
                        {if count($ALL_USERS) > 0}
                            {foreach from=$ALL_USERS item="username"}
                                <option value="{$username}">{$username}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-900 dark:text-slate-100" for="InputCredits">
                        {$AMOUNT}
                        <span class="font-normal text-slate-500 dark:text-slate-400">
                            ({$YOU_HAVE_X_CREDITS})
                        </span>
                    </label>

                    <input type="number"
                           name="credits"
                           id="InputCredits"
                           step="0.01"
                           min="0.01"
                           max="{$CREDITS_VALUE}"
                           value="0.00"
                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-soft outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-100">
                </div>
            </form>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
            <button type="button"
                    onclick="document.getElementById('modal-send-credits').close()"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                {$CANCEL}
            </button>

            <button type="button"
                    onclick="document.getElementById('sendCredits').submit()"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                {$SEND_CREDITS}
            </button>
        </div>
    </dialog>
{/if}

{include file='footer.tpl'}