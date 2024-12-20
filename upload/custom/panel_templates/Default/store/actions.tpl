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
                    <h1 class="h3 mb-0 text-gray-800">{$ACTIONS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$GLOBAL_ACTIONS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 style="display:inline">{$GLOBAL_ACTIONS}</h5>
                        <div class="float-md-right">
                            <a href="{$NEW_ACTION_LINK}" class="btn btn-primary">{$NEW_ACTION}</a>
                        </div>

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if count($ACTION_LIST)}
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Trigger On</th>
                                    <th>Service</th>
                                    <th>Command</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody id="sortable">
                                {foreach from=$ACTION_LIST item=action}
                                    <tr data-id="{$command.id}">
                                        <td>{$action.type}</td>
                                        <td>{$action.service}{if $action.warning}
                                                <button role="button" class="btn btn-sm btn-warning" data-toggle="popover"
                                                        data-title="{$WARNING}" data-content="{$action.warning}"><i
                                                            class="fa fa-exclamation-triangle"></i></button>
                                            {/if}</td>
                                        <td>{$action.command}</td>
                                        <td>
                                            <div class="float-md-right">
                                                <a class="btn btn-warning btn-sm" href="{$action.edit_link}"><i class="fas fa-edit fa-fw"></i></a>
                                                <a class="btn btn-danger btn-sm" href="{$action.delete_link}"><i class="fas fa-trash fa-fw"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                        {else}
                        <hr>
                        There are no global actions.
                        </br></br>
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

</body>
</html>