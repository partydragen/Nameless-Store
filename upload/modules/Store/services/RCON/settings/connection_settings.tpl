<form role="form" action="" method="post">
    <div class="form-group">
        <label for="InputName">Connection {$NAME}</label>
        <input type="text" name="name" class="form-control" id="InputName" placeholder="{$NAME}" value="{$NAME_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputRconAddress">RCON Address</label>
        <input type="text" class="form-control" name="rcon_address" value="{$RCON_HOST_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputRconPort">RCON Port</label>
        <input type="number" class="form-control" name="rcon_port" value="{$RCON_PORT_VALUE}">
    </div>
    <div class="form-group">
        <label for="inputRconPassword">RCON Password</label>
        <span class="badge badge-info"><i class="fa fa-question-circle"
            data-container="body" data-toggle="popover"
            data-placement="top" title="Info"
            data-content="The password is not shown for security reasons."></i></span>
        <input type="password" class="form-control" name="rcon_password">
    </div>
    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
    </div>
</form>