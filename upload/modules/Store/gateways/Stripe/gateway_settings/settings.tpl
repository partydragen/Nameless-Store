<form action="" method="post">
    <div class="card shadow border-left-primary">
        <div class="card-body">
            <h5><i class="icon fa fa-info-circle"></i> Info</h5>
            The values of these fields are hidden for security reasons.<br />If you are updating these settings, please enter both the client ID and the client secret together.
            </br></br>Client keys can be made here <a href="https://dashboard.stripe.com/dashboard">https://dashboard.stripe.com/dashboard</a>
        </div>
    </div>

    <br />

    <div class="form-group">
        <label for="inputPublishableKey">Stripe Publishable Key</label>
        <input class="form-control" type="text" id="inputPublishableKey" name="publishable_key" placeholder="The values of these fields are hidden for security reasons.">
    </div>
    <div class="form-group">
        <label for="inputStripeSecretKey">Stripe Secret Key</label>
        <input class="form-control" type="text" id="inputStripeSecretKey" name="secret_key" placeholder="The values of these fields are hidden for security reasons.">
    </div>

    <div class="form-group custom-control custom-switch">
        <input id="inputEnabled" name="enable" type="checkbox" class="custom-control-input"{if $ENABLE_VALUE eq 1} checked{/if} />
        <label class="custom-control-label" for="inputEnabled">Enable Payment Method</label>
    </div>

    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
    </div>
</form>