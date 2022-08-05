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
                            <a class="nav-link active">{$GENERAL_SETTINGS}</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" href="{$ACTIONS_LINK}">{$ACTIONS}</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" href="{$LIMITS_AND_REQUIREMENTS_LINK}">{$LIMITS_AND_REQUIREMENTS}</a>
                          </li>
                        </ul>

                        </br>

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
                            
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="InputName">{$PRODUCT_NAME}</label>
                                <input type="text" name="name" class="form-control" id="InputName" value="{$PRODUCT_NAME_VALUE}" placeholder="{$PRODUCT_NAME}" required>
                            </div>
                            <div class="form-group">
                                <strong><label for="inputDescription">{$PRODUCT_DESCRIPTION}</label></strong>
                                <textarea id="inputDescription" name="description">{$PRODUCT_DESCRIPTION_VALUE}</textarea>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="inputPrice">{$PRICE}</label>
                                        <div class="input-group">
                                            <input type="number" name="price" class="form-control" id="inputPrice" step="0.01" min="0.00" value="{$PRODUCT_PRICE_VALUE}" placeholder="{$PRICE}" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">{$CURRENCY}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="inputCategory">{$CATEGORY}</label>
                                        <select name="category" class="form-control" id="inputCategory" required>
                                            {foreach from=$CATEGORY_LIST item=category}
                                                <option value="{$category.id}" {if $PRODUCT_CATEGORY_VALUE == {$category.id}} selected{/if}>{$category.name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="inputConnections">{$CONNECTIONS}</label> <span
                                            class="badge badge-info"><i class="fas fa-question-circle"
                                                    data-container="body" data-toggle="popover"
                                                    data-placement="top" title="Info"
                                                    data-content="Each action will be executed on these connections unless the action override it"></i></span>
                                        <select name="connections[]" id="inputConnections" class="form-control" multiple>
                                            {foreach from=$CONNECTIONS_LIST item=connection}
                                                <option value="{$connection.id}"{if $connection.selected} selected{/if}>{$connection.name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="inputFields">{$FIELDS}</label> <span
                                            class="badge badge-info"><i class="fas fa-question-circle"
                                                    data-container="body" data-toggle="popover"
                                                    data-placement="top" title="Info"
                                                    data-content="Customer will be requested to fill in these fields on checkout, Those can be used as command placeholders"></i></span>
                                        <select name="fields[]" id="inputFields" class="form-control" multiple>
                                            {foreach from=$FIELDS_LIST item=field}
                                                <option value="{$field.id}"{if $field.selected} selected{/if}>{$field.identifier}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group custom-control custom-switch">
                                <input id="inputHidden" name="hidden" type="checkbox" class="custom-control-input"{if $HIDE_PRODUCT_VALUE eq 1} checked{/if} />
                                <label class="custom-control-label" for="inputHidden">{$HIDE_PRODUCT}</label>
                            </div>
                            <div class="form-group custom-control custom-switch">
                                <input id="inputDisabled" name="disabled" type="checkbox" class="custom-control-input"{if $DISABLE_PRODUCT_VALUE eq 1} checked{/if} />
                                <label class="custom-control-label" for="inputDisabled">{$DISABLE_PRODUCT}</label>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="hidden" name="type" value="settings">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                            </div>
                        </form>
                            
                        </br>
                            
                        {if isset($ACTIONS)}
                            <h5 style="display:inline">{$ACTIONS}</h5>
                            <div class="float-md-right">
                                <a href="{$NEW_ACTION_LINK}" class="btn btn-primary">{$NEW_ACTION}</a>
                            </div>
                                
                            {if count($ACTION_LIST)}
                                <div class="table-responsive">
                                   <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Trigger On</th>
                                                <th>Service</th>
                                                <th>Command</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="sortable">
                                        {foreach from=$ACTION_LIST item=action}
                                            <tr data-id="{$command.id}">
                                                <td>{$action.type}</td>
                                                <td>{$action.service}</td>
                                                <td>{$action.command}</td>
                                                <td>
                                                    <div class="float-md-right">
                                                        <a class="btn btn-warning btn-sm" href="{$action.edit_link}"><i class="fas fa-edit fa-fw"></i></a>
                                                        <a class="btn btn-danger btn-sm" href="{$action.delete_link}"><i class="fas fa-trash fa-fw"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                {else}
                                <hr>
                                There are no actions yet.
                                </br></br>
                                {/if}
                            {/if}
                            
                            {if isset($ACTIONS)}
                                <hr>
                                <h5>{$PRODUCT_IMAGE}</h5>
                                <form action="" method="post" enctype="multipart/form-data" style="display:inline;">
                                    <div class="form-group">
                                        {if $PRODUCT_IMAGE_VALUE}
                                            <img src="{$PRODUCT_IMAGE_VALUE}" alt="{$PRODUCT_NAME}" style="max-height:200px;max-width:200px;"><br />
                                        {/if}
                                        <strong>{$UPLOAD_NEW_IMAGE}</strong><br />
                                        <label class="btn btn-secondary">
                                            {$BROWSE} <input type="file" name="product_image" hidden/>
                                        </label>
                                    </div>
                                    
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="hidden" name="type" value="image">
                                    <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
                                </form>
                                {if $PRODUCT_IMAGE_VALUE}
                                    <a href="{$REMOVE_IMAGE_LINK}" class="btn btn-danger">{$REMOVE}</a>
                                {/if}
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
        $('#inputConnections').select2({ placeholder: "No connections selected" });
    })
    
    $(document).ready(() => {
        $('#inputFields').select2({ placeholder: "No fields selected" });
    })
</script>

</body>
</html>