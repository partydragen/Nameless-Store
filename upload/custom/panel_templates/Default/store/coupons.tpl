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
                    <h1 class="h3 mb-0 text-gray-800">{$COUPONS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$COUPONS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}
                
                <div class="alert alert-warning" role="alert">
                    This features is currently for patreon supporters, it will be available for everyone in the future with means this wont function for you
                    </br></br>
                    <a href="https://partydragen.com/patreon/" target="_blank" class="btn btn-primary">Patreon</a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <span class="float-md-right">
                            <a href="{$NEW_COUPON_LINK}" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_COUPON}</a>
                        </span>

                        </br>
                        </br>

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
                        
                        {if isset($COUPONS_LIST)}
                            <div class="table-responsive">
                                <table class="table table-striped" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>{$CODE}</th>
                                        <th>{$ACTIVE}</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$COUPONS_LIST item=coupon}
                                        <tr>
                                            <td><code>{$coupon.code}</code></td>
                                            <td>{if $coupon.active}<i class="fa fa-check-circle text-success"></i>{else}<i class="fa fa-times-circle text-danger"></i>{/if}</td>
                                            <td>
                                                <div class="float-md-right">
                                                    <a class="btn btn-warning btn-sm" href="{$coupon.edit_link}"><i class="fas fa-edit fa-fw"></i></a>
                                                    <button class="btn btn-danger btn-sm" type="button" onclick="showDeleteModal('{$coupon.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                            
                        {else}
                            <hr>
                            {$NO_COUPONS}
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

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {$CONFIRM_DELETE_COUPON}
            </div>
            <div class="modal-footer">
                <form action="" id="deleteForm" method="post">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                    <input type="hidden" name="token" value="{$TOKEN}">
                    <button type="submit" class="btn btn-primary">{$YES}</button>
                </form>
            </div>
        </div>
    </div>
</div>

{include file='scripts.tpl'}

<script type="text/javascript">
    function showDeleteModal(id){
        $('#deleteForm').attr('action', id);
        $('#deleteModal').modal().show();
    }
</script>
</body>
</html>