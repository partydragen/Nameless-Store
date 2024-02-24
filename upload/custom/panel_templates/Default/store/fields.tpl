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
                    <h1 class="h3 mb-0 text-gray-800">{$FIELDS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$FIELDS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <p style="margin-top: 7px; margin-bottom: 7px;">{$FIELDS_INFO}</p>
                            </div>
                            <div class="col-md-3">
                                <span class="float-md-right"><a href="{$NEW_FIELD_LINK}" class="btn btn-primary"><i class="fas fa-plus-circle"></i> {$NEW_FIELD}</a></span>
                            </div>
                        </div>
                            
                        <hr>
                            
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
                        
                        {if count($FIELDS_LIST)}
                            <div class="table-responsive">
                                <table class="table table-striped" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th style="width:20%">{$IDENTIFIER}</th>
                                        <th style="width:25%">{$DESCRIPTION}</th>
                                        <th style="width:20%">{$TYPE}</th>
                                        <th style="width:20%">{$REQUIRED}</th>
                                        <th style="width:15%"><div class="float-md-right">{$ACTIONS}</div></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$FIELDS_LIST item=field}
                                        <tr>
                                            <td><a href="{$field.edit_link}">{$field.identifier}</a></td>
                                            <td>{$field.description}</td>
                                            <td>{$field.type}</td>
                                            <td>{if $field.required eq 1}<i class="fa fa-check-circle text-success"></i>{else}<i class="fa fa-times-circle text-danger"></i>{/if}</td>
                                            <td>
                                                <div class="float-md-right">
                                                    <a class="btn btn-warning btn-sm" href="{$field.edit_link}"><i class="fas fa-edit fa-fw"></i></a>
                                                    {if !$field.reserved}<button class="btn btn-danger btn-sm" type="button" onclick="showDeleteModal('{$field.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>{else}<button class="btn btn-danger btn-sm" type="button"><i class="fa fa-lock fa-fw"></i></button>{/if}
                                                </div>
                                            </td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                            
                        {else}
                            <hr>
                            {$NONE_FIELDS_DEFINED}
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
                {$CONFIRM_DELETE_FIELD}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                <a href="#" id="deleteLink" class="btn btn-primary">{$YES}</a>
            </div>
        </div>
    </div>
</div>

{include file='scripts.tpl'}

<script type="text/javascript">
    function showDeleteModal(id){
        $('#deleteLink').attr('href', id);
        $('#deleteModal').modal().show();
    }
</script>
</body>
</html>