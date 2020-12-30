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
                    <h1 class="h3 mb-0 text-gray-800">{$PACKAGES}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$PACKAGES}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                    
							<span class="float-md-right">
								<a href="/panel/store/categories/?action=new" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_CATEGORY}</a>
								<a href="/panel/store/packages/?action=new" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_PACKAGE}</a>
							</span>
							
							</br>
							</br>
							
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

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

                <!-- End Page Content -->
            </div>

            <!-- End Main Content -->
        </div>

        {include file='footer.tpl'}

        <!-- End Content Wrapper -->
    </div>

    <!-- End Wrapper -->
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