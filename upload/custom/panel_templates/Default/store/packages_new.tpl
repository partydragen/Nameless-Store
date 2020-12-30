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
                    
                            <h5 style="display:inline">{$NEW_PACKAGE}</h5>
                            <div class="float-md-right">
                                <a href="/panel/store/packages" class="btn btn-primary">{$BACK}</a>
                            </div>
                            <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
							
                            <form action="" method="post">
								<div class="form-group">
									<label for="InputName">{$PACKAGE_NAME}</label>
									<input type="text" name="name" class="form-control" id="InputName" placeholder="{$PACKAGE_NAME}">
								</div>
								<div class="form-group">
                                    <strong><label for="inputDescription">{$PACKAGE_DESCRIPTION}</label></strong>
                                    <textarea id="inputDescription" name="description">{$PACKAGE_DESCRIPTION_VALUE}</textarea>
                                </div>
								<div class="form-group">
									<div class="row">
                                        <div class="col-md-6">
											<label for="inputPrice">{$PRICE}</label>
											<div class="input-group">
												<input type="number" name="price" class="form-control" id="inputPrice" step="0.01" min="0.01" placeholder="{$PRICE}">
												<div class="input-group-append">
													<span class="input-group-text">{$CURRENCY}</span>
												</div>
											</div>
										</div>
                                        <div class="col-md-6">
											<label for="inputCategory">{$CATEGORY}</label>
											<select name="category" class="form-control" id="inputCategory">
												{foreach from=$CATEGORY_LIST item=category}
													<option value="{$category.id}">{$category.name}</option>
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

                            <!---<form action="" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <strong>{$PACKAGE_IMAGE}</strong><br />
                                    {if $PACKAGE_IMAGE_VALUE}
                                        <img src="{$PACKAGE_IMAGE_VALUE}" alt="{$PACKAGE_NAME}" style="max-height:200px;max-width:200px;"><br />
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
                            </form>--->

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