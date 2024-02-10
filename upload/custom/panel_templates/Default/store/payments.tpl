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
                    <div class="card-body">
                        {if isset($CREATE_PAYMENT)}
                        <span class="float-md-right">
                            <a href="{$CREATE_PAYMENT_LINK}" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$CREATE_PAYMENT}</a>
                        </span>
                        
                        </br>
                        </br>
                        <hr>
                        {/if}

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if isset($NO_PAYMENTS)}
                            <p>{$NO_PAYMENTS}</p>
                        {else}
                            <div class="table-responsive">
                                <table class="table table-striped dataTables-payments" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{$USER}</th>
                                            <th>{$AMOUNT}</th>
                                            <th>{$STATUS}</th>
                                            <th>{$DATE}</th>
                                            <th><div class="float-right">{$VIEW}</div></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        {/if}

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
        var paymentsTable = $('.dataTables-payments').DataTable({
            columnDefs: [
                { targets: [0], sClass: "hide" }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: "{$QUERY_PAYMENTS_LINK}",
            columns: [
                { data: "id", hidden: true },
                {
                    data: "username", "orderable": false,
                    render: function(data, type, row) {
                        return '<a href="' + row.user_profile + '" style="' + row.user_style + '"><img src="' + row.user_avatar + '" alt="" style="padding-right: 5px; max-height: 30px;"> ' + row.username  + '</a>';
                    }
                },
                { data: "amount" },
                { data: "status", "orderable": false },
                { data: "date" },
                {
                    data: "view", "orderable": false,
                    render: function(data, type, row) {
                        return '<a href="{$VIEW_PAYMENT_LINK}' + row.id + '" class="btn btn-primary btn-sm float-right">{$VIEW}</a>';
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
    });
</script>

</body>
</html>