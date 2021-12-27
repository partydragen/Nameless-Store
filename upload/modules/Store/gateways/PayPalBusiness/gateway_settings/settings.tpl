                            <form action="" method="post">
                                <div class="card shadow border-left-primary">
                                    <div class="card-body">
                                        <h5><i class="icon fa fa-info-circle"></i> Info</h5>
                                        The values of these fields are hidden for security reasons.<br />If you are updating these settings, please enter both the client ID and the client secret together.
                                    </div>
                                </div>
                                
                                </br>
                                
                                <div class="form-group">
                                    <label for="inputPaypalId">PayPal Client ID</label>
                                    <input class="form-control" type="text" id="inputPaypalId" name="client_id" placeholder="The values of these fields are hidden for security reasons.">
                                </div>

                                <div class="form-group">
                                    <label for="inputPaypalSecret">PayPal Client Secret</label>
                                    <input class="form-control" type="text" id="inputPaypalSecret" name="client_secret" placeholder="The values of these fields are hidden for security reasons.">
                                </div>
                                
                                <div class="form-group">
                                    <label for="InputCaptcha">Enable Payment Method</label>
                                    <input id="inputCaptcha" name="enable" type="checkbox" class="js-switch"{if $ENABLE_VALUE eq 1} checked{/if} />
                                </div>

                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
                                </div>
                            </form>