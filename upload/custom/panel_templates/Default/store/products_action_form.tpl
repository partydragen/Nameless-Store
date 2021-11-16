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
                    <h1 class="h3 mb-0 text-gray-800">{$PRODUCTS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$PRODUCTS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <h5 style="display:inline">{$ACTION_TITLE}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a>
                        </div>
                        <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        <form action="" method="post">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="inputTrigger">Trigger On</label>
                                        <select name="trigger" class="form-control" id="inputTrigger">
                                            <option value="1" {if $TRIGGER_VALUE == 1} selected{/if}>Purchase</option>
                                            <option value="2" {if $TRIGGER_VALUE == 2} selected{/if}>Refund</option>
                                            <option value="3" {if $TRIGGER_VALUE == 3} selected{/if}>Changeback</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="inputRequirePlayer">Require the player to be online</label>
                                        <select name="requirePlayer" class="form-control" id="inputRequirePlayer">
                                            <option value="1" {if $REQUIRE_PLAYER_VALUE == 1} selected{/if}>Yes</option>
                                            <option value="0" {if $REQUIRE_PLAYER_VALUE == 0} selected{/if}>No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="inputConnections">{$CONNECTIONS}</label>
                                <select name="connections[]" id="label_connections" size="3" class="form-control" multiple style="overflow:auto;" required>
                                    {foreach from=$CONNECTIONS_LIST item=connection}
                                        <option value="{$connection.id}"{if $connection.selected} selected{/if}>{$connection.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="InputName">Command (Without /)</label></br>
                                <label for="InputName">{literal}Placeholders: {username} {uuid}{/literal}</label>
                                <input type="text" name="command" class="form-control" id="InputName" value="{$COMMAND_VALUE}" placeholder="{literal}say Thanks {name} for purchasing {productName}{/literal}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                            </div>
                        </form>

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