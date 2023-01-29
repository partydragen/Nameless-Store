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
                    <h1 class="h3 mb-0 text-gray-800">{$PAYMENTS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$PAYMENTS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 style="display:inline">{$CREATE_PAYMENT}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a>
                        </div>
                        <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if isset($NO_PRODUCTS)}
                            <p>{$NO_PRODUCTS}</p>
                        {else}
                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="inputUsername">{$USERNAME}</label>
                                    <input type="text" class="form-control" id="inputUsername" name="username" placeholder="{$USERNAME}" required>
                                </div>
                                <div class="form-group">
                                    <label for="inputProduct">{$PRODUCTS}</label>
                                    <select class="form-control" name="products[]" id="inputProducts" multiple required>
                                        {foreach from=$PRODUCTS_LIST item=item}
                                            <option value="{$item.id}">{$item.name}</option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="InputPrice">{$PRICE}</label>
                                    <input type="number" name="price" class="form-control" id="InputPrice" step="0.01" min="0.00" value="0.00">
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                                </div>
                            </form>
                        {/if}

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
        $('#inputProducts').select2({ placeholder: "No products selected" });
    })
</script>

</body>
</html>