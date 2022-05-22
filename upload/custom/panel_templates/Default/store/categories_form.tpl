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
                    <h1 class="h3 mb-0 text-gray-800">{$CATEGORIES}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$CATEGORIES}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <h5 style="display:inline">{$CATEGORY_TITLE}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a>
                        </div>
                        <hr />
     
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        <form action="" method="post">
                            <div class="form-group">
                                <label for="InputName">{$CATEGORY_NAME}</label>
                                <input type="text" name="name" class="form-control" id="InputName" value="{$CATEGORY_NAME_VALUE}" placeholder="{$CATEGORY_NAME}" required>
                            </div>
                            <div class="form-group">
                                <strong><label for="inputDescription">{$CATEGORY_DESCRIPTION}</label></strong>
                                <textarea id="inputDescription" name="description">{$CATEGORY_DESCRIPTION_VALUE}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="inputParentCategory">{$PARENT_CATEGORY}</label>
                                <select name="parent_category" class="form-control" id="inputParentCategory" required>
                                    <option value="0" {if $PARENT_CATEGORY_VALUE == 0} selected{/if}>{$NO_PARENT}</option>
                                    {foreach from=$PARENT_CATEGORY_LIST item=category}
                                    <option value="{$category.id}" {if $PARENT_CATEGORY_VALUE == {$category.id}} selected{/if}>{$category.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="form-group custom-control custom-switch">
                                <input id="inputOnlySubCategories" name="only_subcategories" type="checkbox" class="custom-control-input"{if $ONLY_SUBCATEGORIES_VALUE eq 1} checked{/if} />
                                <label class="custom-control-label" for="inputOnlySubCategories">{$ONLY_SUBCATEGORIES}</label>
                            </div>
                            <div class="form-group custom-control custom-switch">
                                <input id="inputHidden" name="hidden" type="checkbox" class="custom-control-input"{if $HIDE_CATEGORY_VALUE eq 1} checked{/if} />
                                <label class="custom-control-label" for="inputHidden">{$HIDE_CATEGORY}</label>
                            </div>
                            <div class="form-group custom-control custom-switch">
                                <input id="inputDisabled" name="disabled" type="checkbox" class="custom-control-input"{if $DISABLE_CATEGORY_VALUE eq 1} checked{/if} />
                                <label class="custom-control-label" for="inputDisabled">{$DISABLE_CATEGORY}</label>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
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

</body>
</html>