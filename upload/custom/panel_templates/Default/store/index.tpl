{include file='header.tpl'}
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    {include file='navbar.tpl'}
    {include file='sidebar.tpl'}

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">{$STORE}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$STORE}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                {if isset($NEW_UPDATE)}
                {if $NEW_UPDATE_URGENT eq true}
                <div class="alert alert-danger">
                    {else}
                    <div class="alert alert-primary alert-dismissible" id="updateAlert">
                        <button type="button" class="close" id="closeUpdate" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        {/if}
                        {$NEW_UPDATE}
                        <br />
                        <a href="{$UPDATE_LINK}" class="btn btn-primary" style="text-decoration:none">{$UPDATE}</a>
                        <hr />
                        {$CURRENT_VERSION}<br />
                        {$NEW_VERSION}
                    </div>
                    {/if}

                    <div class="card">
                        <div class="card-body">
                            {if isset($SUCCESS)}
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h5><i class="icon fa fa-check"></i> {$SUCCESS_TITLE}</h5>
                                    {$SUCCESS}
                                </div>
                            {/if}

                            {if isset($ERRORS) && count($ERRORS)}
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> {$ERRORS_TITLE}</h5>
                                    <ul>
                                        {foreach from=$ERRORS item=error}
                                            <li>{$error}</li>
                                        {/foreach}
                                    </ul>
                                </div>
                            {/if}

                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="inputAllowGuests">{$ALLOW_GUESTS}</label>
                                    <input type="checkbox" name="allow_guests" id="inputAllowGuests" class="js-switch" {if $ALLOW_GUESTS_VALUE} checked{/if} />
                                </div>

                                <div class="form-group">
                                    <label for="inputStorePath">{$STORE_PATH}</label>
                                    <input type="text" class="form-control" id="inputStorePath" name="store_path" placeholder="{$STORE_PATH}" value="{$STORE_PATH_VALUE}">
                                </div>

                                <div class="form-group">
                                    <label for="inputStoreContent">{$STORE_INDEX_CONTENT}</label>
                                    <textarea id="inputStoreContent" name="store_content">{$STORE_INDEX_CONTENT_VALUE}</textarea>
                                </div>
								
                                <div class="form-group">
                                    <label for="inputCheckoutContent">{$STORE_CHECKOUT_CONTENT}</label>
                                    <textarea id="inputCheckoutContent" name="store_checkout_content">{$STORE_CHECKOUT_CONTENT_VALUE}</textarea>
                                </div>
								
								<h5>PayPal API Details</h5>
								<div class="callout callout-info">
                                    <h5><i class="icon fa fa-info-circle"></i> Info</h5>
                                    The values of these fields are hidden for security reasons.<br />If you are updating these settings, please enter both the client ID and the client secret together.
                                </div>
								
								<div class="form-group">
                                    <label for="inputPaypalId">PayPal Client ID</label>
                                    <input class="form-control" type="text" id="inputPaypalId" name="client_id">
                                </div>

                                <div class="form-group">
                                    <label for="inputPaypalSecret">PayPal Client Secret</label>
                                    <input class="form-control" type="text" id="inputPaypalSecret" name="client_secret">
                                </div

                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
                                </div>
                            </form>

                        </div>
                    </div>

                    <!-- Spacing -->
                    <div style="height:1rem;"></div>

                </div>
        </section>
    </div>

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

</body>
</html>