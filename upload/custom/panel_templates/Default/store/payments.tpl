{include file='header.tpl'}
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    {include file='navbar.tpl'}
    {include file='sidebar.tpl'}

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">{$PAYMENTS}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$STORE}</li>
                            <li class="breadcrumb-item active">{$PAYMENTS}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                {if isset($NEW_UPDATE)}
                {if $NEW_UPDATE_URGENT eq true}
                <div class="alert alert-danger">
                    {else}
                    <div class="alert alert-primary alert-dismissible" id="updateAlert">
                        <button type="button" class="close" id="closeUpdate" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        {/if}
                        {$NEW_UPDATE}
                        <br />
                        <a href="{$UPDATE_LINK}" class="btn btn-primary" style="text-decoration:none">{$UPDATE}</a>
                        <hr />
                        {$CURRENT_VERSION}<br />
                        {$NEW_VERSION}
                    </div>
                    {/if}

                    <div class="card">
                        <div class="card-body">
                            <!---<a class="btn btn-primary" href="{$NEW_PAYMENT_LINK}">{$NEW_PAYMENT}</a>--->
                            <hr />

                            {if isset($SUCCESS)}
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h5><i class="icon fa fa-check"></i> {$SUCCESS_TITLE}</h5>
                                    {$SUCCESS}
                                </div>
                            {/if}

                            {if isset($ERRORS) && count($ERRORS)}
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> {$ERRORS_TITLE}</h5>
                                    <ul>
                                        {foreach from=$ERRORS item=error}
                                            <li>{$error}</li>
                                        {/foreach}
                                    </ul>
                                </div>
                            {/if}

                            {if isset($NO_PAYMENTS)}
                                <p>{$NO_PAYMENTS}</p>
                            {else}
                                <div class="table-responsive">
                                    <table class="table table-striped dataTables-payments">
                                        <thead>
                                        <tr>
                                            <th>{$USER}</th>
                                            <th>{$AMOUNT}</th>
                                            <th>{$STATUS}</th>
                                            <th>{$DATE}</th>
                                            <th>{$VIEW}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {foreach from=$ALL_PAYMENTS item=payment}
                                            <tr>
                                                <td><a href="{$payment.user_link}" style="{$payment.user_style}"><img src="{$payment.user_avatar}" class="rounded" style="max-width:32px;max-height:32px;" alt="{$payment.username}" /> {$payment.username}</a></td>
                                                <td>{$payment.currency_symbol}{$payment.amount}</td>
                                                <td>{$payment.status}</td>
                                                <td data-sort="{$payment.date_unix}">{$payment.date}</td>
                                                <td><a href="{$payment.link}" class="btn btn-primary btn-sm">{$VIEW}</a></td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            {/if}

                        </div>
                    </div>

                    <!-- Spacing -->
                    <div style="height:1rem;"></div>

                </div>
        </section>
    </div>

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

</body>
</html>