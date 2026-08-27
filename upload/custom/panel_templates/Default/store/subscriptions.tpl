{include file='header.tpl'}

<body id="page-top">

<!-- Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    {include file='sidebar.tpl'}

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main content -->
        <div id="content">

            <!-- Topbar -->
            {include file='navbar.tpl'}

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">{$SUBSCRIPTIONS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$SUBSCRIPTIONS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if isset($NO_SUBSCRIPTIONS)}
                            <p>{$NO_SUBSCRIPTIONS}</p>
                        {else}
                            <div class="row mb-3">
                                <div class="col-md-6 col-lg-4">
                                    <label for="subscriptionStatusFilter" class="small font-weight-bold text-uppercase text-muted">{$FILTER_BY_STATUS}</label>
                                    <select id="subscriptionStatusFilter" class="form-control form-control-sm">
                                        <option value="">{$ALL_STATUSES}</option>
                                        <option value="0">{$PENDING}</option>
                                        <option value="1">{$ACTIVE}</option>
                                        <option value="2">{$CANCELLED}</option>
                                        <option value="3">{$SUSPENDED}</option>
                                        <option value="4">{$UNKNOWN}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover dataTables-subscriptions" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>{$USER}</th>
                                        <th>{$STATUS}</th>
                                        <th>{$AMOUNT}</th>
                                        <th>{$FREQUENCY}</th>
                                        <th>{$LAST_PAYMENT_DATE}</th>
                                        <th>{$NEXT_BILLING_DATE}</th>
                                        <th class="text-right">{$VIEW}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$SUBSCRIPTIONS_LIST item=subscription}
                                        <tr>
                                            <td><a href="{$subscription.user_link}" style="{$subscription.user_style}"><img src="{$subscription.user_avatar}" class="rounded" style="max-width:32px;max-height:32px;" alt="{$subscription.username}" /> {$subscription.username}</a></td>
                                            <td><span class="d-none">status-filter-{$subscription.status_id}</span>{$subscription.status}</td>
                                            <td data-order="{$subscription.amount_cents}">{$subscription.amount_format}</td>
                                            <td>{$subscription.frequency}</td>
                                            <td data-order="{$subscription.last_billing_date_unix}">{$subscription.last_billing_date}</td>
                                            <td data-order="{$subscription.next_billing_date_unix}">{$subscription.next_billing_date}</td>
                                            <td class="text-right"><a href="{$subscription.link}" class="btn btn-primary btn-sm"><i class="fas fa-eye fa-fw"></i> {$VIEW}</a></td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        {/if}

                        <center>
                            <p>Store Module by <a href="https://partydragen.com/" target="_blank">Partydragen</a></br>
                                <a class="ml-1" href="https://partydragen.com/suggestions/" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="You can submit suggestions here"><i class="fa-solid fa-thumbs-up text-warning"></i></a>
                                <a class="ml-1" href="https://discord.gg/TtH6tpp" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="Discord"><i class="fab fa-discord fa-fw text-discord"></i></a>
                                <a class="ml-1" href="https://partydragen.com/" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="Website"><i class="fas fa-globe fa-fw text-primary"></i></a>
                                <a class="ml-1" href="https://www.patreon.com/partydragen" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="Support the development on Patreon"><i class="fas fa-heart fa-fw text-danger"></i></a>
                            </p>
                        </center>
                    </div>
                </div>

                <!-- Spacing -->
                <div style="height:1rem;"></div>

                <!-- End Page Content -->
            </div>

            <!-- End Main Content -->
        </div>

        {include file='footer.tpl'}

        <!-- End Content Wrapper -->
    </div>

    <!-- End Wrapper -->
</div>

{include file='scripts.tpl'}

{if !isset($NO_SUBSCRIPTIONS)}
<script type="text/javascript">
    $(document).ready(function() {
        const subscriptionsTable = $('.dataTables-subscriptions').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[5, 'asc']],
            columnDefs: [
                { targets: [6], orderable: false, searchable: false }
            ],
            language: {
                "lengthMenu": "{$DISPLAY_RECORDS_PER_PAGE}",
                "zeroRecords": "{$NOTHING_FOUND}",
                "info": "{$PAGE_X_OF_Y}",
                "infoEmpty": "{$NO_RECORDS}",
                "infoFiltered": "{$FILTERED}",
                "search": "{$SEARCH}",
                "paginate": {
                    "next": "{$NEXT}",
                    "previous": "{$PREVIOUS}"
                }
            }
        });

        $('#subscriptionStatusFilter').on('change', function() {
            const status = $(this).val();
            subscriptionsTable.column(1).search(status === '' ? '' : 'status-filter-' + status).draw();
        });
    });
</script>
{/if}

</body>
</html>
