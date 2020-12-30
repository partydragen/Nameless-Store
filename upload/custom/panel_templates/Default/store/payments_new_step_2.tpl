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
                        <h5 style="display:inline">{$NEW_PAYMENT}</h5>
                        <div class="float-md-right">
                            <button type="button" onclick="showCancelModal()" class="btn btn-warning">{$CANCEL}</button>
                        </div>
                        <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

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

                <!-- End Page Content -->
            </div>

            <!-- End Main Content -->
        </div>

        {include file='footer.tpl'}

        <!-- End Content Wrapper -->
    </div>

    <!-- End Wrapper -->
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

{include file='scripts.tpl'}

<script type="text/javascript">
    function showCancelModal(){
        $('#cancelModal').modal().show();
    }
</script>

</body>
</html>