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
                        <h1 class="m-0 text-dark">{$PAYMENTS}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$BUYCRAFT}</li>
                            <li class="breadcrumb-item active">{$PAYMENTS}</li>
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
                        <h5 style="display:inline">{$NEW_PAYMENT}</h5>
                        <div class="float-md-right">
                            <button type="button" onclick="showCancelModal()" class="btn btn-warning">{$CANCEL}</button>
                        </div>
                        <hr />

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
                                <label for="inputPrice">{$PRICE}</label>
                                <input id="inputPrice" name="bc_payment_price" class="form-control" type="number" step="0.01" min="0.01" value="{$PRICE}">
                            </div>
                            {if count($PACKAGE_FIELDS)}
                                {foreach from=$PACKAGE_FIELDS item=field}
                                    <div class="form-group">
                                        <label for="input{$field.id}">{$field.name_title}</label><br />
                                        {if $field.description}{$field.description}{/if}
                                        {if $field.type == 'numeric'}
                                            <input {if $field.id == 'price'}disabled {/if}id="input{$field.id}" name="{$field.name}" class="form-control" type="number" min="0" step="1">
                                        {elseif $field.type == 'text'}
                                            <textarea id="input{$field.id}" name="{$field.name}" class="form-control"></textarea>
                                        {elseif $field.type == 'alpha'}
                                            <input id="input{$field.id}" name="{$field.name}" class="form-control" type="text" pattern="([A-zÀ-ž\s]){literal}{{/literal}{$field.min_length},{literal}}{/literal}">
                                        {elseif $field.type == 'username'}
                                            <input id="input{$field.id}" name="{$field.name}" class="form-control" type="text">
                                        {elseif $field.type == 'email'}
                                            <input id="input{$field.id}" name="{$field.name}" class="form-control" type="email">
                                        {elseif $field.type == 'dropdown'}
                                            <select class="form-control" id="input{$field.id}" name="{$field.name}">
                                                {foreach from=$field.options item=option}
                                                    <option value="{$option.value}">{$option.label}</option>
                                                {/foreach}
                                            </select>
                                        {/if}
                                    </div>
                                {/foreach}
                            {/if}

                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                            </div>
                        </form>

                    </div>
                </div>

                <!-- Spacing -->
                <div style="height:1rem;"></div>

            </div>
        </section>
    </div>

    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$CONFIRM_CANCEL}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                    <a href="{$CANCEL_LINK}" class="btn btn-primary">{$YES}</a>
                </div>
            </div>
        </div>
    </div>

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

<script type="text/javascript">
    function showCancelModal(){
        $('#cancelModal').modal().show();
    }
</script>

</body>
</html>