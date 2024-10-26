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
                    <h1 class="h3 mb-0 text-gray-800">{$GATEWAYS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$GATEWAYS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if isset($GATEWAYS_LIST)}
                        <div class="table-responsive">
                            <table class="table table-striped dataTables-payments">
                                <thead>
                                    <tr>
                                        <th>{$PAYMENT_METHOD}</th>
                                        <th>{$SUPPORTS_SUBSCRIPTIONS}</th>
                                        <th>{$ENABLED}</th>
                                        <th><div class="float-right">{$EDIT}</div></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$GATEWAYS_LIST item=gateway}
                                        <tr>
                                            <td><strong>{$gateway.name}</strong> <small>{$gateway.version}</small><br />
                                            <small>{$gateway.author_x}</small></td>
                                            <td>{if $gateway.supports_subscriptions}<i class="fa fa-check-circle text-success"></i>{else}<i class="fa fa-times-circle text-danger"></i>{/if}</td>
                                            <td>{if $gateway.enabled}<span class="badge badge-success">{$ENABLED}</span>{else}<span class="badge badge-danger">{$DISABLED}</span>{/if}</td>
                                            <td><a href="{$gateway.edit_link}" class="btn btn-primary btn-sm float-right">{$EDIT}</a></td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>

                        <br />
                        <h5 style="display:inline">{$FIND_GATEWAYS}</h5>
                        <div class="float-md-right">
                            <a href="{$VIEW_ALL_GATEWAYS_LINK}" class="btn btn-primary" target="_blank">{$VIEW_ALL_GATEWAYS} &raquo;</a>
                        </div>
                        <br /><br />

                        {if count($WEBSITE_GATEWAYS)}
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <colgroup>
                                        <col width="70%">
                                        <col width="20%">
                                        <col width="10%">
                                    </colgroup>
                                    <thead>
                                    <tr>
                                        <th>{$GATEWAY}</th>
                                        <th>{$STATS}</th>
                                        <th>{$ACTIONS}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$WEBSITE_GATEWAYS item=item}
                                        <tr>
                                            <td>
                                                <strong>{$item.name}</strong> <small>{$item.latest_version}</small>
                                                <br />
                                                <small>{$item.author_x}</small>
                                                <br />
                                                <small>{$item.updated_x}</small>
                                            </td>
                                            <td>
                                                <div class="star-rating view">
                                                        <span class="far fa-star" data-rating="1"
                                                              style="color:gold;"></span>
                                                    <span class="far fa-star" data-rating="2" style="color:gold"></span>
                                                    <span class="far fa-star" data-rating="3"
                                                          style="color:gold;"></span>
                                                    <span class="far fa-star" data-rating="4"
                                                          style="color:gold;"></span>
                                                    <span class="far fa-star" data-rating="5"
                                                          style="color:gold;"></span>
                                                    <input type="hidden" name="rating" class="rating-value"
                                                           value="{($item.rating/10)|round}">
                                                </div>
                                                {$item.downloads_full}<br />
                                                {$item.views_full}
                                            </td>
                                            <td><a href="{$item.url}" target="_blank"
                                                   class="btn btn-primary btn-sm">{$VIEW} &raquo;</a></td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        {else}
                            <div class="alert alert-warning">{$UNABLE_TO_RETRIEVE_GATEWAYS}</div>
                        {/if}
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