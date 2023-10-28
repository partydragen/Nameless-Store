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
                                    <h5 style="margin-top: 7px; margin-bottom: 7px;">{$CONNECTIONS_TITLE}</h5>
                                </div>
                                <div class="col-md-3">
                                    <span class="float-md-right"><a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a></span>
                                </div>
                            </div>
                            
                            <hr />
                            
                            <!-- Success and Error Alerts -->
                            {include file='includes/alerts.tpl'}

                            {assign var="counter" value=0}
                            <div class="row justify-content-md-center text-center">
                                {foreach from=$SERVICES_LIST item=service}
                                    {if $counter > 8} {assign var="counter" value=0}
                            </div>
                            </br>
                            <div class="row justify-content-md-center text-center">
                                    {/if}
                                    {assign var="counter" value=($counter+4)}
                                    <div class="col-md-4">
                                        <div class="card shadow h-100">
                                            <div class="card-header"><strong>{$service.name}</strong></div>
                                            <div class="card-body d-flex flex-column">
                                                {$service.description}
                                                <a href="{$service.select_link}" class="btn btn-primary btn-sm btn-block mt-auto">Select</a>
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                            
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

        <!-- End Wrapper -->
    </div>

    {include file='scripts.tpl'}

</body>
</html>