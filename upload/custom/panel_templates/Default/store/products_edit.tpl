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
                            <h5 style="display:inline">{$EDITING_PRODUCT}</h5>
                            <div class="float-md-right">
                                <a href="/panel/store/products" class="btn btn-primary">{$BACK}</a>
                            </div>
                            <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
							
                            <form action="" method="post">
								<div class="form-group">
									<label for="InputName">{$PRODUCT_NAME}</label>
									<input type="text" name="name" class="form-control" id="InputName" value="{$PRODUCT_NAME_VALUE}" placeholder="{$PRODUCT_NAME}">
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
												<input type="number" name="price" class="form-control" id="inputPrice" step="0.01" min="0.01" value="{$PRODUCT_PRICE_VALUE}" placeholder="{$PRICE}">
												<div class="input-group-append">
													<span class="input-group-text">{$CURRENCY}</span>
												</div>
											</div>
										</div>
                                        <div class="col-md-6">
											<label for="inputCategory">{$CATEGORY}</label>
											<select name="category" class="form-control" id="inputCategory">
												{foreach from=$CATEGORY_LIST item=category}
												<option value="{$category.id}" {if $PRODUCT_CATEGORY_VALUE == {$category.id}} selected{/if}>{$category.name}</option>
												{/foreach}
											</select>
										</div>
                                    </div>
								</div>
                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                                </div>
                            </form>
							
							</br>
							
							<h5 style="display:inline">{$COMMANDS}</h5>
							<div class="float-md-right">
								<a href="/panel/store/packages/?action=new_command&id={$ID}" class="btn btn-primary">{$NEW_COMMAND}</a>
							</div>
							
							{if count($COMMAND_LIST)}
							<div class="table-responsive">
                                <table class="table">
									<thead>
										<tr>
											<th>Trigger On</th>
											<th>Require the player to be online</th>
											<th>Command (Without /)</th>
											<th></th>
										</tr>
									</thead>
                                    <tbody id="sortable">
									{foreach from=$COMMAND_LIST item=command}
										<tr data-id="{$command.id}">
											<td>{$command.type}</td>
											<td>{$command.requirePlayer}</td>
											<td>{$command.command}</td>
											<td>
												<div class="float-md-right">
													<a class="btn btn-warning btn-sm" href="/panel/store/packages/?action=edit_command&id={$ID}&command={$command.id}"><i class="fas fa-edit fa-fw"></i></a>
													<a class="btn btn-danger btn-sm" href="/panel/store/packages/?action=delete_command&id={$ID}&command={$command.id}"><i class="fas fa-trash fa-fw"></i></a>
												</div>
											</td>
										</tr>
									{/foreach}
									</tbody>
                                </table>
                            </div>
							{else}
							<hr>
							There are no commands yet.
							</br></br>
							{/if}

                            <!--<form action="" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <strong>{$PRODUCT_IMAGE}</strong><br />
                                    {if $PRODUCT_IMAGE_VALUE}
                                        <img src="{$PRODUCT_IMAGE_VALUE}" alt="{$PRODUCT_NAME}" style="max-height:200px;max-width:200px;"><br />
                                    {/if}
                                    <strong>{$UPLOAD_NEW_IMAGE}</strong><br />
                                    <label class="btn btn-secondary">
                                        {$BROWSE} <input type="file" name="store_image" hidden/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="hidden" name="type" value="image">
                                    <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
                                </div>
                            </form>-->

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