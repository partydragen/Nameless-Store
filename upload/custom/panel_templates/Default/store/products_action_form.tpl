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

                        {if isset($SETTINGS_TEMPLATE)}
                            {include file=$SETTINGS_TEMPLATE}
                        {else}
                            <form class="ui form" action="" method="post" id="form-register">
                                {assign var=counter value=1}
                                {foreach $FIELDS as $field_key => $field}
                                    <div class="form-group">
                                        <label for="input{$field_key}">{$field.name}</label>
                                        {if $field.type eq 1}
                                            <input type="text" name="{$field_key}" class="form-control" id="input{$field_key}" value="{$field.value}" placeholder="{$field.placeholder}" tabindex="{$counter++}"{if $field.required} required{/if}>
                                        {else if $field.type eq 2}
                                            <textarea name="{$field_key}" class="form-control" id="{$field_key}" placeholder="{$field.placeholder}" tabindex="{$counter++}"></textarea>
                                        {else if $field.type eq 3}
                                            <input type="date" name="{$field_key}" class="form-control" id="{$field_key}" value="{$field.value}" tabindex="{$counter++}">
                                        {else if $field.type eq 4}
                                            <input type="password" name="{$field_key}" class="form-control" id="{$field_key}" value="{$field.value}" placeholder="{$field.placeholder}" tabindex="{$counter++}"{if $field.required} required{/if}>
                                        {else if $field.type eq 5}
                                            <select class="form-control" name="{$field_key}" id="{$field_key}" {if $field.required}required{/if}>
                                                {foreach from=$field.options item=option}
                                                    <option value="{$option.value}" {if $option.value eq $field.value} selected{/if}>{$option.option}</option>
                                                {/foreach}
                                            </select>
                                        {else if $field.type eq 6}
                                            <input type="number" name="{$field_key}" class="form-control" id="{$field_key}" value="{$field.value}" placeholder="{$field.name}" tabindex="{$counter++}"{if $field.required} required{/if}>
                                        {else if $field.type eq 7}
                                            <input type="email" name="{$field_key}" class="form-control" id="{$field_key}" value="{$field.value}" placeholder="{$field.placeholder}" tabindex="{$counter++}"{if $field.required} required{/if}>
                                        {/if}
                                    </div>
                                {/foreach}
                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                                </div>
                            </form>
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
                            <td>{gateway}</td>
                            <td>The gateway name used for the payment</td>
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

<script type="text/javascript">
    $(document).ready(() => {
        $('#inputConnections').select2({ placeholder: "{$NO_ITEM_SELECTED}" });
    })
</script>

</body>
</html>