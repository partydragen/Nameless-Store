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
                    <h1 class="h3 mb-0 text-gray-800">{$SALES}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$SALES}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}
                
                <div class="alert alert-warning" role="alert">
                    This features is currently for patreon supporters, it will be available for everyone in the future with means this wont function for you
                    </br></br>
                    <a href="https://partydragen.com/patreon/" target="_blank" class="btn btn-primary">Patreon</a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 style="display:inline">{$SALE_TITLE}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-warning">{$BACK}</a>
                        </div>
                        <hr>
                        
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
                        
                        <form role="form" action="" method="post">
                            <div class="form-group">
                                <label for="InputName">{$NAME}</label>
                                <input type="text" name="name" class="form-control" id="InputName" placeholder="{$NAME}" value="{$NAME_VALUE}">
                            </div>
                            <div class="form-group">
                                <label for="inputProducts">{$PRODUCTS}</label>
                                <select name="products[]" id="inputProducts" class="form-control" multiple>
                                    {foreach from=$PRODUCTS_LIST item=product}
                                        <option value="{$product.id}"{if $product.selected} selected{/if}>{$product.id} - {$product.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputDiscountType">{$DISCOUNT_TYPE}</label>
                                    <select name="discount_type" id="InputDiscountType" class="form-control">
                                        <option value="1" {if $DISCOUNT_TYPE_VALUE == '1'} selected{/if}>{$PERCENTAGE}</option>
                                        <option value="2" {if $DISCOUNT_TYPE_VALUE == '2'} selected{/if}>{$AMOUNT}</option>
                                    </select>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputDiscountAmount">{$AMOUNT}</label>
                                    <input type="number" name="discount_amount" class="form-control" id="InputDiscountAmount" placeholder="{$AMOUNT}" value="{$AMOUNT_VALUE}" min="0">
                                </div>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputMinimum">{$START_DATE}</label>
                                    <input type="datetime-local" id="inputStart" name="start_date" value="{$START_DATE_VALUE}" min="{$START_DATE_MIN}" class="form-control" />
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputMaximum">{$EXPIRE_DATE}</label>
                                    <input type="datetime-local" id="inputExpire" name="expire_date" value="{$EXPIRE_DATE_VALUE}" min="{$EXPIRE_DATE_MIN}" class="form-control" />
                                </div>
                              </div>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <span data-toggle="popover" data-title="Early access" data-content="This feature is currently for patreon supporters, it will be available for everyone in the future with means this wont function for you"><input type="submit" class="btn btn-primary" value="{$SUBMIT}" disabled></span>
                            </div>
                        </form>

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

<script type="text/javascript">
    $(document).ready(() => {
        $('#inputProducts').select2({ placeholder: "No products selected" });
    })
</script>

</body>
</html>