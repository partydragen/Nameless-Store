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

                <div class="row">
                    <div class="col-md-9">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h5 style="display:inline">{$VIEWING_PAYMENT}</h5>
                                <div class="float-md-right">
                                    <a class="btn btn-primary" href="{$BACK_LINK}">{$BACK}</a>
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
                                                <td><strong>{$IGN}</strong></td>
                                                <td><img src="{$AVATAR}" class="rounded" style="max-height:32px;max-width:32px;" alt="{$IGN_VALUE}"> <a style="{$STYLE}" href="{$USER_LINK}">{$IGN_VALUE}</a></td>
                                            </tr>
                                            <tr>
                                                <td><strong>{$STATUS}</strong></td>
                                                <td>{$STATUS_VALUE}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{$TRANSACTION}</strong></td>
                                                <td>{$TRANSACTION_VALUE}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{$PAYMENT_METHOD}</strong></td>
                                                <td>{$PAYMENT_METHOD_VALUE}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{$UUID}</strong></td>
                                                <td>{$UUID_VALUE}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{$PRICE}</strong></td>
                                                <td>{$CURRENCY_SYMBOL}{$PRICE_VALUE} ({$CURRENCY_ISO})</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{$DATE}</strong></td>
                                                <td>{$DATE_VALUE}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                </br>
                                
                                <hr />
                                <h5 style="display:inline">{$PENDING_COMMANDS}</h5>
                                {if count($PENDING_COMMANDS_LIST)}
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{$COMMAND}</th>
                                                <th>{$CONNECTION}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        {foreach from=$PENDING_COMMANDS_LIST item=command}
                                            <tr>
                                                <td>{$command.command}</td>
                                                <td>{$command.connection_name}</td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                {else}
                                    <p>{$NO_PENDING_COMMANDS}</p>
                                {/if}
                                
                                </br>

                                <hr />
                                <h5 style="display:inline">{$PROCESSED_COMMANDS}</h5>
                                {if count($PROCESSED_COMMANDS_LIST)}
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{$COMMAND}</th>
                                                <th>{$CONNECTION}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        {foreach from=$PROCESSED_COMMANDS_LIST item=command}
                                            <tr>
                                                <td>{$command.command}</td>
                                                <td>{$command.connection_name}</td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                {else}
                                    <p>{$NO_PROCESSED_COMMANDS}</p>
                                {/if}

                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h5 style="display:inline">{$PRODUCTS}</h5>
                                <hr>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tbody>
                                            {foreach from=$PRODUCTS_LIST item=product}
                                                <tr>
                                                    <td>{$product.name} <a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#productModal{$product.id}">{$DETAILS}</a></td>
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

{foreach from=$PRODUCTS_LIST item=product}
    <div class="modal fade" id="productModal{$product.id}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{$product.name}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <tbody>
                            {if count($product.fields)}
                                <tr><td><strong>Fields:</strong></tr>
                                {foreach from=$product.fields item=field}
                                    <tr>
                                        <td>{$field.identifier} <span class="float-right">{$field.value}</span></td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr><td>Fields: <span class="float-right">No fields selected for this product.</span></td></tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
{/foreach}

{include file='scripts.tpl'}

</body>
</html>