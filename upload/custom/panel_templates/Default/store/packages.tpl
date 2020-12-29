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
                        <h1 class="m-0 text-dark">{$PACKAGES}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$STORE}</li>
                            <li class="breadcrumb-item active">{$PACKAGES}</li>
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
							<span class="float-md-right">
								<a href="/panel/store/categories/?action=new" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_CATEGORY}</a>
								<a href="/panel/store/packages/?action=new" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_PACKAGE}</a>
							</span>
							
							</br>
							</br>
							
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

                            {if isset($NO_PACKAGES)}
                            <p>{$NO_PACKAGES}</p>
                            {else}
                            {foreach from=$ALL_CATEGORIES item=category}
                            <div class="card card-default">
								<div class="card-header">
									<strong>{$category.name}</strong>
									<span class="float-md-right">
                                        <a class="btn btn-warning btn-sm" href="{$category.edit_link}"><i class="fas fa-pencil-alt"></i></a>
                                        <button class="btn btn-danger btn-sm" type="button" onclick="showDeleteCategoryModal('{$category.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>
                                    </span>
								</div>
								<div class="card-body">
								{if count($category.packages)}
                                {foreach from=$category.packages item=package name=package_loop}
									<div class="row">
										<div class="col-md-4">
											{$package.name} <small>{$package.id_x}</small>
										</div>
										<div class="col-md-4">
											<center>{$package.price}</center>
										</div>
										<div class="col-md-4">
											{if isset($package.edit_link)}
											<span class="float-md-right">
												<a class="btn btn-warning btn-sm" href="{$package.edit_link}"><i class="fas fa-pencil-alt"></i></a>
												<button class="btn btn-danger btn-sm" type="button" onclick="showDeletePackageModal('{$package.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>
											</span>
											{/if}
										</div>
									</div>
                                    {if !$smarty.foreach.package_loop.last}<hr />{/if}
                                {/foreach}
								{/if}
								</div>
                            </div>
                            {/foreach}
                            {/if}

                        </div>
                    </div>

                    <!-- Spacing -->
                    <div style="height:1rem;"></div>

                </div>
        </section>
    </div>
	
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$CONFIRM_DELETE_CATEGORY}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                    <a href="#" id="deleteCategoryLink" class="btn btn-primary">{$YES}</a>
                </div>
            </div>
        </div>
    </div>
	
    <div class="modal fade" id="deletePackageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$CONFIRM_DELETE_PACKAGE}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                    <a href="#" id="deletePackageLink" class="btn btn-primary">{$YES}</a>
                </div>
            </div>
        </div>
    </div>

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

<script type="text/javascript">
    function showDeleteCategoryModal(id){
        $('#deleteCategoryLink').attr('href', id);
        $('#deleteCategoryModal').modal().show();
    }
    function showDeletePackageModal(id){
        $('#deletePackageLink').attr('href', id);
        $('#deletePackageModal').modal().show();
    }
</script>

</body>
</html>