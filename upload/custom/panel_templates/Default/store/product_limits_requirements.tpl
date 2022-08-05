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
                        <h5 style="display:inline">{$PRODUCT_TITLE}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a>
                        </div>
                        <hr />

                        <ul class="nav nav-tabs">
                          <li class="nav-item">
                            <a class="nav-link" href="{$GENERAL_SETTINGS_LINK}">{$GENERAL_SETTINGS}</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" href="{$ACTIONS_LINK}">{$ACTIONS}</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link active">{$LIMITS_AND_REQUIREMENTS}</a>
                          </li>
                        </ul>

                        </br>

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        <div class="alert alert-warning" role="alert">
                          These features is currently for patreon supporters, it will be available for everyone in the future with means this wont function for you
                          </br></br>
                          <a href="https://partydragen.com/patreon/" target="_blank" class="btn btn-primary">Patreon</a>
                        </div>

                        <form action="" method="post">
                            <div class="form-group">
                                <label for="inputGlobalLimit">Global Limit (0 for unlimited)</label>
                                <div class="input-group">
                                  <input type="number" name="global_limit" class="form-control" id="inputGlobalLimit" value="{$GLOBAL_LIMIT_VALUE.limit}" disabled>
                                  <div class="input-group-prepend">
                                    <span class="input-group-text" id="">purchases every</span>
                                  </div>
                                  <input type="number" name="global_limit_interval" class="form-control" id="inputGlobalLimit" value="{$GLOBAL_LIMIT_VALUE.interval}" disabled>
                                  <select name="global_limit_period" class="form-control">
                                    <option value="no_period" {if $GLOBAL_LIMIT_VALUE.period == 'no_period'} selected{/if}>No Period</option>
                                    <option value="hour" {if $GLOBAL_LIMIT_VALUE.period == 'hour'} selected{/if}>Hour</option>
                                    <option value="day" {if $GLOBAL_LIMIT_VALUE.period == 'day'} selected{/if}>Day</option>
                                    <option value="week" {if $GLOBAL_LIMIT_VALUE.period == 'week'} selected{/if}>Week</option>
                                    <option value="month" {if $GLOBAL_LIMIT_VALUE.period == 'month'} selected{/if}>Month</option>
                                    <option value="year" {if $GLOBAL_LIMIT_VALUE.period == 'year'} selected{/if}>Year</option>
                                  </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="inputUserLimit">User Limit (0 for unlimited)</label>
                                <div class="input-group">
                                  <input type="number" name="user_limit" class="form-control" id="inputUserLimit" value="{$USER_LIMIT_VALUE.limit}" disabled>
                                  <div class="input-group-prepend">
                                    <span class="input-group-text" id="">purchases every</span>
                                  </div>
                                  <input type="number" name="user_limit_interval" class="form-control" id="inputUserLimit" value="{$USER_LIMIT_VALUE.interval}" disabled>
                                  <select name="user_limit_period" class="form-control">
                                    <option value="no_period" {if $USER_LIMIT_VALUE.period == 'no_period'} selected{/if}>No Period</option>
                                    <option value="hour" {if $USER_LIMIT_VALUE.period == 'hour'} selected{/if}>Hour</option>
                                    <option value="day" {if $USER_LIMIT_VALUE.period == 'day'} selected{/if}>Day</option>
                                    <option value="week" {if $USER_LIMIT_VALUE.period == 'week'} selected{/if}>Week</option>
                                    <option value="month" {if $USER_LIMIT_VALUE.period == 'month'} selected{/if}>Month</option>
                                    <option value="year" {if $USER_LIMIT_VALUE.period == 'year'} selected{/if}>Year</option>
                                  </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="inputRequiredProducts">Required Products</label> <span
                                        class="badge badge-info"><i class="fas fa-question-circle"
                                                data-container="body" data-toggle="popover"
                                                data-placement="top" title="Info"
                                                data-content="User will be required to have these products to purchase this product"></i></span>
                                <select name="required_products[]" id="inputRequiredProducts" class="form-control" multiple disabled>
                                    {foreach from=$PRODUCTS_LIST item=product}
                                        <option value="{$product.id}"{if $product.selected} selected{/if}>{$product.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="inputRequiredGroups">Required Groups</label> <span
                                        class="badge badge-info"><i class="fas fa-question-circle"
                                                data-container="body" data-toggle="popover"
                                                data-placement="top" title="Info"
                                                data-content="User will be required to have these groups to purchase this product (This will only function if user is logged in)"></i></span>
                                <select name="required_groups[]" id="inputRequiredGroups" class="form-control" multiple disabled>
                                    {foreach from=$GROUPS_LIST item=group}
                                        <option value="{$group.id}"{if $group.selected} selected{/if}>{$group.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="inputRequiredIntegrations">Required User Integrations</label> <span
                                        class="badge badge-info"><i class="fas fa-question-circle"
                                                data-container="body" data-toggle="popover"
                                                data-placement="top" title="Info"
                                                data-content="User will be required to have these integrations linked to purchase this product (This will only function if user is logged in)"></i></span>
                                <select name="required_integrations[]" id="inputRequiredIntegrations" class="form-control" multiple disabled>
                                    {foreach from=$INTEGRATIONS_LIST item=integration}
                                        <option value="{$integration.id}"{if $integration.selected} selected{/if}>{$integration.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="hidden" name="type" value="settings">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}" disabled>
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

{include file='scripts.tpl'}

<script type="text/javascript">
    $(document).ready(() => {
        $('#inputRequiredProducts').select2({ placeholder: "No products selected" });
    })

    $(document).ready(() => {
        $('#inputRequiredGroups').select2({ placeholder: "No groups selected" });
    })

    $(document).ready(() => {
        $('#inputRequiredIntegrations').select2({ placeholder: "No integrations selected" });
    })
</script>

</body>
</html>