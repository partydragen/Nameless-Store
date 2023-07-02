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

                <div class="row">
                    <div class="col-md-9">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 style="display:inline">{$VIEWING_SUBSCRIPTION}</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="float-md-right">
                                            <a href="{$BACK_LINK}" class="btn btn-warning">{$BACK}</a>
                                            {if isset($SYNC_SUBSCRIPTION)}
                                                <form role="form" action="" method="post" style="display:inline">
                                                    <input type="hidden" name="token" value="{$TOKEN}">
                                                    <input type="hidden" name="action" value="sync">
                                                    <input type="submit" value="{$SYNC_SUBSCRIPTION}" class="btn btn-primary">
                                                </form>
                                            {/if}
                                            {if isset($CANCEL_SUBSCRIPTION)}
                                                <form role="form" action="" method="post" style="display:inline">
                                                    <input type="hidden" name="token" value="{$TOKEN}">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="submit" value="{$CANCEL_SUBSCRIPTION}" class="btn btn-danger">
                                                </form>
                                            {/if}
                                        </span>
                                    </div>
                                </div>
                                <hr />

                                <!-- Success and Error Alerts -->
                                {include file='includes/alerts.tpl'}

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <colgroup>
                                            <col span="1" style="width: 50%">
                                            <col span="1" style="width: 50%">
                                        </colgroup>
                                        <tbody>
                                        <tr>
                                            <td><strong>{$CUSTOMER}</strong></td>
                                            <td><img src="{$AVATAR}" class="rounded" style="max-height:32px;max-width:32px;" alt="{$USERNAME}"> <a style="{$STYLE}" href="{$USER_LINK}">{$USERNAME}</a></td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$STATUS}</strong></td>
                                            <td>{$STATUS_VALUE}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$AGREEMENT_ID}</strong></td>
                                            <td>{$AGREEMENT_ID_VALUE}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$PAYMENT_METHOD}</strong></td>
                                            <td>{$PAYMENT_METHOD_VALUE}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$FREQUENCY}</strong></td>
                                            <td>Every {$FREQUENCY_VALUE}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$AMOUNT}</strong></td>
                                            <td>{$AMOUNT_FORMAT_VALUE}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$LAST_PAYMENT_DATE}</strong></td>
                                            <td>{$LAST_PAYMENT_DATE_VALUE}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{$NEXT_BILLING_DATE}</strong></td>
                                            <td>{$NEXT_BILLING_DATE_VALUE}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

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
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h5 style="display:inline">{$PAYMENTS}</h5>
                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tbody>
                                        {foreach from=$PAYMENTS_LIST item=payment}
                                            <tr>
                                                <td>{$payment.date} <a class="float-right btn btn-primary btn-sm" href="{$payment.link}">{$VIEW}</a></td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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