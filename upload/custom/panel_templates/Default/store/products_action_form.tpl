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
                                <label for="InputCommand">Command (Without /)</label><a class="float-right btn btn-primary btn-sm" href="" data-toggle="modal" data-target="#placeholders">Placeholders</a></br>
                                <input type="text" name="command" class="form-control" id="InputCommand" value="{$COMMAND_VALUE}" placeholder="{literal}say Thanks {username} for purchasing {productName}{/literal}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                            </div>
                        </form>

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

    <!-- End Wrapper -->
</div>

<div class="modal fade" id="placeholders" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Placeholders</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th><strong>Placeholder</strong></th>
                            <th><strong>Replaces With</strong></th>
                        <tr>
                        {literal}
                        <tr>
                            <td>{uuid}</td>
                            <td>The UUID of the player</td>
                        </tr>
                        <tr>
                            <td>{username}</td>
                            <td>The username of the player</td>
                        </tr>
                        <tr>
                            <td>{transaction}</td>
                            <td>The transaction ID of the payment</td>
                        </tr>
                        <tr>
                            <td>{connection}</td>
                            <td>The name of the connection the command is executed on</td>
                        </tr>
                        <tr>
                            <td>{amount}</td>
                            <td>The amount of the payment</td>
                        </tr>
                        <tr>
                            <td>{currency}</td>
                            <td>The currency of the payment.</td>
                        </tr>
                        <tr>
                            <td>{time}</td>
                            <td>The time of the payment, e.g. 15:30</td>
                        </tr>
                        <tr>
                            <td>{date}</td>
                            <td>The date of the payment, e.g. 19 Feb 2022</td>
                        </tr>
                        <tr>
                            <td>{userId}</td>
                            <td>The ID of the NamelessMC User if user was logged in</td>
                        </tr>
                        <tr>
                            <td>{orderId}</td>
                            <td>The ID of the order</td>
                        </tr>
                        <tr>
                            <td>{productId}</td>
                            <td>The ID of the product</td>
                        </tr>
                        <tr>
                            <td>{productPrice}</td>
                            <td>The price of the product</td>
                        </tr>
                        <tr>
                            <td>{productName}</td>
                            <td>The name of the product</td>
                        </tr>
                        <tr>
                            <th><strong>Custom Fields</strong></th>
                            <th><strong></strong></th>
                        <tr>
                        <tr>
                            <td>{your field name}</td>
                            <td>The value the customer entered</td>
                        </tr>
                        {/literal}
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{include file='scripts.tpl'}

</body>
</html>