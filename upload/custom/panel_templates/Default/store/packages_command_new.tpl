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
                    <div class="card">
                        <div class="card-body">
                            <h5 style="display:inline">{$NEW_COMMAND}</h5>
                            <div class="float-md-right">
                                <a href="/panel/store/packages/?action=edit&id={$ID}" class="btn btn-primary">{$BACK}</a>
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
								<div class="row">
									<div class="col-md-6">
										<label for="inputTrigger">Trigger On</label>
										<select name="trigger" class="form-control" id="inputTrigger">
											<option value="1">Purchase</option>
											<option value="2">Refund</option>
											<option value="3">Changeback</option>
										</select>
									</div>
									<div class="col-md-6">
										<label for="inputRequirePlayer">Require the player to be online</label>
										<select name="requirePlayer" class="form-control" id="inputRequirePlayer">
											<option value="1">Yes</option>
											<option value="0">No</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label for="InputName">Command (Without /)</label></br>
                                    {literal}
                                    <label for="InputName">Placeholders: {username} {uuid}</label>
									<input type="text" name="command" class="form-control" id="InputName" value="" 	placeholder="say Thanks {username} for your donation">
									{/literal}
								</div>
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

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

</body>
</html>