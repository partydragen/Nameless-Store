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
                                            <th class="float-md-right">{$ACTIONS}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable">
                                        {foreach from=$CONNECTIONS_LIST item=connection}
                                        <tr data-id="{$announcement[0]->id}">
                                            <td>{$connection.id}</td>
                                            <td>{$connection.name}</td>
                                            <td>{$connection.service}</td>
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
                            
                            Minecraft Plugin for Spigot or Bukkit - <a href="https://www.spigotmc.org/resources/nameless-store.87221/" target="_blank" class="btn btn-primary btn-sm">Go to Spigot</a>
                            
                            <center><p>Store Module by <a href="https://partydragen.com/" target="_blank">Partydragen</a></br>Support on <a href="https://discord.gg/TtH6tpp" target="_blank">Discord</a></p></center>
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