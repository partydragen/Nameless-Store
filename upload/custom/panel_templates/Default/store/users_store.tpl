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
                    <h1 class="h3 mb-0 text-gray-800">{$STORE}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$USER_MANAGEMENT}</li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-9">
                                <h5 style="margin-top: 7px; margin-bottom: 7px;">{$VIEWING_USER}</h5>
                            </div>
                            <div class="col-md-3">
                                <span class="float-md-right">
                                    <a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a>
                                </span>
                            </div>
                        </div>
                        <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        <div class="form-group">
                            <label for="credits">{$CREDITS}</label>
                            <input id="credits" type="number" class="form-control" value="{$CREDITS_VALUE}" readonly>
                        </div>

                        {if isset($ADD_CREDITS)}
                        <a class="btn btn-success" href="#" onclick="showAddCreditsModal()">{$ADD_CREDITS}</a>
                        <a class="btn btn-danger" href="#" onclick="showRemoveCreditsModal()">{$REMOVE_CREDITS}</a>
                        </br>
                        {/if}

                        </br>
                        <h5 style="margin-top: 7px; margin-bottom: 7px;">{$VIEWING_PAYMENTS_FOR_USER}</h5>
                        {if count($PAYMENTS_LIST)}
                            <div class="table-responsive">
                                <table class="table table-striped dataTables-payments" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{$AMOUNT}</th>
                                            <th>{$STATUS}</th>
                                            <th>{$DATE}</th>
                                            <th><span class="float-md-right">{$VIEW}</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$PAYMENTS_LIST item=payment}
                                            <tr>
                                                <td>{$payment.amount_format}</td>
                                                <td>{$payment.status}</td>
                                                <td data-sort="{$payment.date_unix}">{$payment.date}</td>
                                                <td><a href="{$payment.link}" class="btn btn-primary btn-sm float-md-right">{$VIEW}</a></td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        {else}
                            <p>{$NO_PAYMENTS}</p>
                        {/if}

                        </br>

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
    
    {if isset($ADD_CREDITS)}
        <div class="modal fade" id="addCreditsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="" method="post">
                        <div class="modal-header">
                            <h5 class="modal-title">{$ADD_CREDITS}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <label for="InputCredits">{$ENTER_AMOUNT_TO_ADD}</label>
                            <input type="number" class="form-control" id="InputCredits" name="credits" step="0.01" min="0.01" value="0.00">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{$CANCEL}</button>
                            <input type="hidden" name="token" value="{$TOKEN}">
                            <input type="hidden" name="action" value="addCredits">
                            <input type="submit" class="btn btn-success" value="{$ADD_CREDITS}">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="removeCreditsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="" method="post">
                        <div class="modal-header">
                            <h5 class="modal-title">{$REMOVE_CREDITS}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <label for="InputCredits">{$ENTER_AMOUNT_TO_REMOVE}</label>
                            <input type="number" class="form-control" id="InputCredits" name="credits" step="0.01" min="0.01" value="0.00">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{$CANCEL}</button>
                            <input type="hidden" name="token" value="{$TOKEN}">
                            <input type="hidden" name="action" value="removeCredits">
                            <input type="submit" class="btn btn-danger" value="{$REMOVE_CREDITS}">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}

    <!-- End Wrapper -->
</div>

{include file='scripts.tpl'}

<script type="text/javascript">
    {if isset($ADD_CREDITS)}
    function showAddCreditsModal() {
      $('#addCreditsModal').modal().show();
    }
    function showRemoveCreditsModal() {
      $('#removeCreditsModal').modal().show();
    }
    {/if}
</script>

</body>

</html>
