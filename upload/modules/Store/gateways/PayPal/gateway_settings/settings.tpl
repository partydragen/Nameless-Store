                            <form action="" method="post">
                                <div class="card shadow border-left-primary">
                                    <div class="card-body">
                                        <h5><i class="icon fa fa-info-circle"></i> Info</h5>
                                        To use the PayPal gateway for live transactions, you'll need a PayPal Business account.</br>
                                        This allows you to generate the necessary API credentials.</br>
                                        </br>
                                        Follow these steps to create a live app and obtain your Client ID and Secret:</br>
                                        1. Go to the PayPal Developer website at <a href="https://developer.paypal.com/home">https://developer.paypal.com</a> and login.</br>
                                        2. Navigate to "Apps & Credentials" in the dashboard.</br>
                                        3. Switch the mode from "Sandbox" to "Live" using the toggle (usually in the top right).</br>
                                        4. Click "Create App" and provide a name for your app.</br>
                                        5. After creation, you'll see the app details. Copy the "Client ID" and "Secret Key"</br>
                                        </br>
                                        Enter the Client ID and Secret into the fields below to configure the gateway.</br>
                                        If you're updating existing settings, always provide both the Client ID and Secret together.
                                    </div>
                                </div>
                                
                                <br />
                                
                                <div class="form-group">
                                    <label for="inputPaypalId">PayPal Client ID</label>
                                    <input class="form-control" type="text" id="inputPaypalId" name="client_id" placeholder="The values of these fields are hidden for security reasons." value="{$CLIENT_ID_VALUE}">
                                </div>

                                <div class="form-group">
                                    <label for="inputPaypalSecret">PayPal Client Secret</label>
                                    <input class="form-control" type="text" id="inputPaypalSecret" name="client_secret" placeholder="The values of these fields are hidden for security reasons.">
                                </div>
                                
                                <div class="form-group custom-control custom-switch">
                                    <input id="inputEnabled" name="enable" type="checkbox" class="custom-control-input"{if $ENABLE_VALUE eq 1} checked{/if} />
                                    <label class="custom-control-label" for="inputEnabled">Enable Payment Method</label>
                                </div>

                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="hidden" name="action" value="settings">
                                    <input type="submit" value="{$SUBMIT}" class="btn btn-primary">
                                </div>
                            </form>

                            <br />
                            <br />

                            <h5>Webhook Management</h5>
                            <hr />
                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="inputWebhookId">Webhook ID</label>
                                    <input class="form-control" type="text" id="inputWebhookId" name="webhook_id" placeholder="No webhook has been generated yet" value="{$WEBHOOK_ID_VALUE}" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="inputWebhookKey">Webhook Key</label>
                                    <input class="form-control" type="text" id="inputWebhookKey" name="webhook_key" placeholder="No webhook has been generated yet" value="{$WEBHOOK_KEY_VALUE}" readonly>
                                </div>

                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="hidden" name="action" value="update_webhook">
                                    <input type="submit" value="Generate or Update Webhook" class="btn btn-primary">
                                </div>
                            </form>