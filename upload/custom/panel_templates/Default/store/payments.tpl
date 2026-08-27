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
                    <h1 class="h3 mb-0 text-gray-800">{$PAYMENTS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$PAYMENTS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">{$PAYMENTS}</h6>
                        {if isset($CREATE_PAYMENT)}
                            <a href="{$CREATE_PAYMENT_LINK}" class="btn btn-primary btn-sm mt-2 mt-md-0"><i class="fas fa-plus-circle fa-fw"></i> {$CREATE_PAYMENT}</a>
                        {/if}
                    </div>
                    <div class="card-body">
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        <div class="row mb-3">
                            <div class="col-md-6 col-lg-4 mb-2 mb-md-0">
                                <label for="paymentStatusFilter" class="small font-weight-bold text-uppercase text-muted">{$FILTER_BY_STATUS}</label>
                                <select id="paymentStatusFilter" class="form-control form-control-sm">
                                    <option value="">{$ALL_STATUSES}</option>
                                    <option value="0">{$PENDING}</option>
                                    <option value="1">{$COMPLETED}</option>
                                    <option value="2">{$REFUNDED}</option>
                                    <option value="3">{$REVERSED}</option>
                                    <option value="4">{$DENIED}</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="paymentTypeFilter" class="small font-weight-bold text-uppercase text-muted">{$FILTER_BY_PAYMENT_TYPE}</label>
                                <select id="paymentTypeFilter" class="form-control form-control-sm">
                                    <option value="">{$ALL_PAYMENT_TYPES}</option>
                                    <option value="one_time">{$ONE_TIME_PAYMENT}</option>
                                    <option value="subscription">{$SUBSCRIPTION_PAYMENT}</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover dataTables-payments" style="width:100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>{$USER}</th>
                                        <th>{$AMOUNT}</th>
                                        <th>{$STATUS}</th>
                                        <th>{$PAYMENT_TYPE}</th>
                                        <th>{$DATE}</th>
                                        <th class="text-right">{$VIEW}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <center>
                            <p>Store Module by <a href="https://partydragen.com/" target="_blank">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a></br>
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

<script type="text/javascript">
    $(document).ready(function() {
        const paymentsTable = $('.dataTables-payments').DataTable({
            columnDefs: [
                { targets: [0], visible: false, searchable: false }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            searchDelay: 350,
            pageLength: 25,
            order: [[5, "desc"]],
            ajax: {
                url: "{$QUERY_PAYMENTS_LINK}",
                data: function(data) {
                    data.status = $('#paymentStatusFilter').val();
                    data.payment_type = $('#paymentTypeFilter').val();
                }
            },
            columns: [
                { data: "id" },
                {
                    data: "username",
                    render: function(data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }
                        return '<a href="' + row.user_profile + '" style="' + row.user_style + '"><img src="' + row.user_avatar + '" alt="" style="padding-right: 5px; max-height: 30px;"> ' + row.username  + '</a>';
                    }
                },
                { data: "amount" },
                { data: "status" },
                {
                    data: "type",
                    render: function(data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }
                        const badge = row.is_subscription ? 'badge-info' : 'badge-secondary';
                        return '<span class="badge ' + badge + '">' + data + '</span>';
                    }
                },
                { data: "date" },
                {
                    data: "id", orderable: false, searchable: false,
                    render: function(data, type, row) {
                        return '<a href="{$VIEW_PAYMENT_LINK}' + row.id + '" class="btn btn-primary btn-sm float-right"><i class="fas fa-eye fa-fw"></i> {$VIEW}</a>';
                    }
                },
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

        $('#paymentStatusFilter, #paymentTypeFilter').on('change', function() {
            paymentsTable.ajax.reload();
        });
    });
</script>

</body>
</html>
