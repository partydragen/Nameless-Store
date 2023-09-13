<form action="" method="post">
    <div class="card shadow border-left-primary">
        <div class="card-body">
            <h5><i class="icon fa fa-info-circle"></i> How you can give your users store credits</h5>
            - You can manage users credits from StaffCP -> User Management -> Users -> Find your User -> Store.</br>
            - You can reward users with credits when they buy something from your store.</br>
            - You can use the <a href="https://www.spigotmc.org/resources/nameless-plugin-for-v2.59032/" target="_blank">NamelessMC Plugin</a> on your server to get commands to manage users credits, example reward your users by completing games, achievements, voting, etc</br>
            - Other NamelessMC modules such as <a href="https://partydragen.com/resources/resource/13/" target="_blank">Trophies</a> and <a href="https://partydragen.com/resources/resource/11" target="_blank">Referrals</a> module have ability to reward users with credits.
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