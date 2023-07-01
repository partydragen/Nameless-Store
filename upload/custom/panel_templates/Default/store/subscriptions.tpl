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
                            <div class="table-responsive">
                                <table class="table table-striped dataTables-payments" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>{$USER}</th>
                                        <th>{$STATUS}</th>
                                        <th>{$AMOUNT}</th>
                                        <th>Last billing date</th>
                                        <th>Next billing date</th>
                                        <th>{$VIEW}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$SUBSCRIPTIONS_LIST item=subscription}
                                        <tr>
                                            <td><a href="{$subscription.user_link}" style="{$payment.user_style}"><img src="{$subscription.user_avatar}" class="rounded" style="max-width:32px;max-height:32px;" alt="{$subscription.username}" /> {$subscription.username}</a></td>
                                            <td>{$subscription.status}</td>
                                            <td>{$subscription.amount_format}</td>
                                            <td>{$subscription.last_billing_date}</td>
                                            <td>{$subscription.next_billing_date}</td>
                                            <td><a href="{$subscription.link}" class="btn btn-primary btn-sm">{$VIEW}</a></td>
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

</body>
</html>