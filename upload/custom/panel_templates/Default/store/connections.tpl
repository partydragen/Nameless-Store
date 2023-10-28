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
                        <h1 class="h3 mb-0 text-gray-800">{$SERVICE_CONNECTIONS}</h1>
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$STORE}</li>
                            <li class="breadcrumb-item active">{$SERVICE_CONNECTIONS}</li>
                        </ol>
                    </div>

                    <!-- Update Notification -->
                    {include file='includes/update.tpl'}

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <p style="margin-top: 7px; margin-bottom: 7px;">{$CONNECTIONS_INFO}</p>
                                </div>
                                <div class="col-md-3">
                                    <span class="float-md-right"><a href="{$NEW_CONNECTION_LINK}" class="btn btn-primary"><i class="fas fa-plus-circle"></i> {$NEW_CONNECTION}</a></span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Success and Error Alerts -->
                            {include file='includes/alerts.tpl'}

                            {if isset($CONNECTIONS_LIST)}
                            <div class="table-responsive">
                                <table class="table table-borderless table-striped">
                                    <thead>
                                        <tr>
                                            <th>{$CONNECTION_ID}</th>
                                            <th>{$NAME}</th>
                                            <th>Service Name</th>
                                            <th>{$QUEUED_ACTIONS}</th>
                                            <th class="float-md-right">{$ACTIONS}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable">
                                        {foreach from=$CONNECTIONS_LIST item=connection}
                                        <tr data-id="{$announcement[0]->id}">
                                            <td>{$connection.id}{if $connection.error}
                                            &nbsp;
                                            <button role="button" class="btn btn-sm btn-warning" data-toggle="popover"
                                                data-title="{$WARNING}" data-content="{$connection.error}"><i
                                                    class="fa fa-exclamation-triangle"></i></button>
                                            {/if}</td>
                                            <td>{$connection.name}</td>
                                            <td>{$connection.service}</td>
                                            <td>{$connection.queued_actions}</td>
                                            <td class="float-md-right">
                                                <a href="{$connection.edit_link}" class="btn btn-warning btn-sm"><i class="fa fa-fw fa-edit"></i></a>
                                                <a href="#" onclick="showDeleteModal({$connection.id})" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                            {else}
                                {$NO_CONNECTIONS}
                            {/if}
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <p style="margin-top: 7px; margin-bottom: 7px;">Downloads</p>
                            <hr>
                            
                            Minecraft Plugin - <a href="https://www.spigotmc.org/resources/nameless-plugin-for-v2.59032/" target="_blank" class="btn btn-primary btn-sm">Go to Spigot</a>

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
                        {$CONFIRM_DELETE_CONNECTION}
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="deleteId" value="">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                        <button type="button" onclick="deleteConnection()" class="btn btn-primary">{$YES}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- End Wrapper -->
    </div>

    {include file='scripts.tpl'}
    
    <script type="text/javascript">
        function showDeleteModal(id) {
            $('#deleteId').attr('value', id);
            $('#deleteModal').modal().show();
        }
        
        function deleteConnection() {
          const id = $('#deleteId').attr('value');
          if (id) {
            const response = $.post("{$DELETE_LINK}", { id, action: 'delete', token: "{$TOKEN}" });
            response.done(function() { window.location.reload(); });
          }
        }
    </script>

</body>
</html>