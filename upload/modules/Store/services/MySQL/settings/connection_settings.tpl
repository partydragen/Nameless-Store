<form role="form" action="" method="post">
    <div class="form-group">
        <label for="InputName">{$NAME}</label>
        <input type="text" name="name" class="form-control" id="InputName" placeholder="{$NAME}" value="{$NAME_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputDBAddress">MySQL Host</label>
        <input type="text" class="form-control" name="db_address" value="{$DB_HOST_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputDBPort">MySQL Port</label>
        <input type="text" class="form-control" name="db_port" value="{$DB_PORT_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputDBName">MySQL Database</label>
        <input type="text" class="form-control" name="db_name" value="{$DB_DATABASE_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputDBUsername">MySQL User</label>
        <input type="text" class="form-control" name="db_username" value="{$DB_USERNAME_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputDBPassword">MySQL Password</label>
        <span class="badge badge-info"><i class="fa fa-question-circle"
            data-container="body" data-toggle="popover"
            data-placement="top" title="Info"
            data-content="The password is not shown for security reasons."></i></span>
        <input type="password" class="form-control" name="db_password">
    </div>
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>