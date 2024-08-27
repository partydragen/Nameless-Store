<form action="" method="post">
    <div class="card shadow border-left-primary">
        <div class="card-body">
            <h5><i class="icon fa fa-info-circle"></i> Setup Instructions</h5>
            1. You need to have the <a href="https://mccommunity.net/resources/resource/14/">Minecraft Community</a> module installed and setup<br />
            2. Have your NamelessMC Site submitted to <a href="https://mccommunity.net/nameless/">https://mccommunity.net/nameless/</a>

            <br /><br /><strong>Users earn money on <a href="https://mccommunity.net/">Minecraft Community</a> that they can spend on your community if they wish</strong><br />
            - By voting on servers<br />
            - By inviting users to Minecraft Community<br />
            - By winning giveaways that goes 24/7<br />
            - By completing trophies<br />
            - Pretty much by being active on Minecraft Community<br />
        </div>
    </div>

    <br />

    <div class="form-group custom-control custom-switch">
        <input id="inputEnabled" name="enable" type="checkbox" class="custom-control-input"{if $ENABLE_VALUE eq 1} checked{/if} />
        <label class="custom-control-label" for="inputEnabled">Enable Payment Method</label>
    </div>

    <div class="form-group">
        <input type="hidden" name="token" value="{$TOKEN}">
        <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
    </div>
</form>