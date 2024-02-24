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
                    <h1 class="h3 mb-0 text-gray-800">{$STORE}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                    
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                            <form action="" method="post">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group custom-control custom-switch">
                                            <input type="checkbox" name="allow_guests" id="inputAllowGuests" class="custom-control-input" {if $ALLOW_GUESTS_VALUE} checked{/if} />
                                            <label class="custom-control-label" for="inputAllowGuests">{$ALLOW_GUESTS}</label>
                                        </div>

                                        <div class="form-group custom-control custom-switch">
                                            <input type="checkbox" name="player_login" id="inputPlayerLogin" class="custom-control-input" {if $PLAYER_LOGIN_VALUE} checked{/if} />
                                            <label class="custom-control-label" for="inputPlayerLogin">{$PLAYER_LOGIN}</label>
                                        </div>

                                        <div class="form-group custom-control custom-switch">
                                            <input type="checkbox" name="show_credits_amount" id="inputShowCreditsAmount" class="custom-control-input" {if $SHOW_CREDITS_AMOUNT_VALUE} checked{/if} />
                                            <label class="custom-control-label" for="inputShowCreditsAmount">{$SHOW_CREDITS_AMOUNT}</label>
                                        </div>
                                        
                                        <div class="form-group custom-control custom-switch">
                                            <input type="checkbox" name="user_send_credits" id="inputUserSendCredits" class="custom-control-input" {if $ALLOW_USERS_TO_SEND_CREDITS_VALUE} checked{/if} />
                                            <label class="custom-control-label" for="inputUserSendCredits">{$ALLOW_USERS_TO_SEND_CREDITS}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="inputStorePath">{$STORE_PATH}</label>
                                            <input type="text" class="form-control" id="inputStorePath" name="store_path" placeholder="{$STORE_PATH}" value="{$STORE_PATH_VALUE}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="link_location">{$LINK_LOCATION}</label>
                                            <select class="form-control" id="link_location" name="link_location">
                                                <option value="1"{if $LINK_LOCATION_VALUE eq 1} selected{/if}>{$LINK_NAVBAR}</option>
                                                <option value="2"{if $LINK_LOCATION_VALUE eq 2} selected{/if}>{$LINK_MORE}</option>
                                                <option value="3"{if $LINK_LOCATION_VALUE eq 3} selected{/if}>{$LINK_FOOTER}</option>
                                                <option value="4"{if $LINK_LOCATION_VALUE eq 4} selected{/if}>{$LINK_NONE}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="link_location">{$CURRENCY}</label>
                                            <div class="input-group">
                                                <select class="form-control" id="currency" name="currency">
                                                    {foreach from=$CURRENCY_LIST item=currency}
                                                        <option value="{$currency}"{if $CURRENCY_VALUE eq {$currency}} selected{/if}>{$currency}</option>
                                                    {/foreach}

                                                    {if !empty($CUSTOM_CURRENCY_VALUE)}
                                                        <option value="USD" selected>Custom</option>
                                                    {/if}
                                                </select>
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">OR</span>
                                                </div>
                                                <input type="text" name="custom_currency" class="form-control" id="inputCustomCurrency" value="{$CUSTOM_CURRENCY_VALUE}" placeholder="Custom code (Use at own risk)">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="inputCurrencySymbol">{$CURRENCY_SYMBOL}</label>
                                            <input type="text" class="form-control" id="inputCurrencySymbol" name="currency_symbol" placeholder="$" value="{$CURRENCY_SYMBOL_VALUE}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="inputCurrencyFormat">{$CURRENCY_FORMAT}</label>
                                            <span class="badge badge-info" data-html="true" data-toggle="popover" title="{$INFO}" data-content="{$CURRENCY_FORMAT_INFO}"><i class="fas fa-question-circle"></i></span>
                                            <input type="text" class="form-control" id="inputCurrencyFormat" name="currency_format" placeholder="{$CURRENCY_FORMAT}" value="{$CURRENCY_FORMAT_VALUE}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="inputValidationMethod">Minecraft Username Validation Method</label>
                                            <select class="form-control" id="inputValidationMethod" name="validation_method">
                                                <option value="nameless" {if $VALIDATION_METHOD_VALUE eq 'nameless'}selected{/if}>Nameless (Use same setting as your site)</option>
                                                <option value="mojang" {if $VALIDATION_METHOD_VALUE eq 'mojang'}selected{/if}>Mojang (Online Mode)</option>
                                                <option value="no_validation" {if $VALIDATION_METHOD_VALUE eq 'no_validation'}selected{/if}>No Validation (Offline - UUID wont work)</option>
                                                <option value="mcstatistics" {if $VALIDATION_METHOD_VALUE eq 'mcstatistics'}selected{/if} {if !$MCSTATISTICS_ENABLED}disabled{/if}>MCStatistics</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="inputCheckoutCompleteContent">{$CHECKOUT_COMPLETE_CONTENT}</label>
                                            <textarea id="inputCheckoutCompleteContent" name="checkout_complete_content">{$CHECKOUT_COMPLETE_CONTENT_VALUE}</textarea>
                                        </div>

                                        <div class="form-group">
                                            <label for="inputDiscordMessage">Custom discord message for payment complete</label>
                                            <span class="badge badge-info" data-html="true" data-toggle="popover" title="{$INFO}" {literal}data-content="Variables available are {customerUsername}, {recipientUsername}, {username}, {products}, {amount}, {currency} and {gateway}"{/literal}><i class="fas fa-question-circle"></i></span>
                                            <textarea id="inputDiscordMessage" class="form-control" name="discord_message" rows="3">{$DISCORD_MESSAGE_VALUE}</textarea>
                                        </div>

                                        <div class="form-group">
                                            <input type="hidden" name="token" value="{$TOKEN}">
                                            <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
                                        </div>
                                    </div>
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

</body>
</html>