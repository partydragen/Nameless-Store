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
                    <h1 class="h3 mb-0 text-gray-800">{$FIELDS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$FIELDS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 style="display:inline">{$FIELD_TITLE}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-warning">{$BACK}</a>
                        </div>
                        <hr>
                        
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
                        
                        <form role="form" action="" method="post">
                            <div class="form-group">
                                <label for="InputIdentifier">{$IDENTIFIER}</label>
                                <input type="text" name="identifier" class="form-control" id="InputIdentifier" placeholder="{$IDENTIFIER}" value="{$IDENTIFIER_VALUE}" {if $RESERVED_FIELD}readonly{/if}>
                            </div>
                            <div class="form-group">
                                <label for="InputName">{$DESCRIPTION}</label>
                                <input type="text" name="description" class="form-control" id="InputDescription" placeholder="{$DESCRIPTION}" value="{$DESCRIPTION_VALUE}">
                            </div>
                            <div class="form-group">
                                <label for="type">{$TYPE}</label>
                                <select class="form-control" id="type" name="type" {if $RESERVED_FIELD}readonly{/if}>
                                  {foreach from=$TYPES item=type}
                                    <option value="{$type.id}"{if $TYPE_VALUE eq {$type.id}} selected{/if}>{$type.name}</option>
                                  {/foreach}
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="InputDefault">Default Value</label>
                                <input type="text" name="default" class="form-control" id="InputDefault" value="{$DEFAULT_VALUE}">
                            </div>
                            <div class="form-group">
                                <label for="InputOptions">{$OPTIONS} / {$CHECKBOX} / {$RADIO} - {$OPTIONS_HELP}</label>
                                <textarea rows="5" class="form-control" name="options" id="options" placeholder="{$OPTIONS} / {$CHECKBOX} / {$RADIO}" {if $RESERVED_FIELD}readonly{/if}>{$OPTIONS_VALUE}</textarea>
                            </div>
                            <div class="row">
                              <div class="col-md-4">
                                <div class="form-group">
                                    <label for="InputOrder">{$FIELD_ORDER}</label>
                                    <input type="number" min="0" class="form-control" id="InputOrder" name="order" value="{$ORDER_VALUE}">
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="form-group">
                                    <label for="InputMinimum">{$MINIMUM_CHARACTERS}</label>
                                    <input type="number" min="0" class="form-control" id="InputMinimum" name="minimum" value="{$MINIMUM_CHARACTERS_VALUE}">
                                </div>
                              </div>
                              <div class="col-md-4">
                                <div class="form-group">
                                    <label for="InputMaximum">{$MAXIMUM_CHARACTERS}</label>
                                    <input type="number" min="0" class="form-control" id="InputMaximum" name="maximum" value="{$MAXIMUM_CHARACTERS_VALUE}">
                                </div>
                              </div>
                            </div>
                            <div class="form-group">
                                <label for="InputRegex">Regex</label>
                                <input type="text" name="regex" class="form-control" id="InputRegex" value="{$REGEX_VALUE}">
                            </div>
                            <div class="form-group custom-control custom-switch">
                                <input id="inputRequired" name="required" type="checkbox" class="custom-control-input"{if $REQUIRED_VALUE eq 1} checked{/if} />
                                <label class="custom-control-label" for="inputRequired">{$REQUIRED}</label>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
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