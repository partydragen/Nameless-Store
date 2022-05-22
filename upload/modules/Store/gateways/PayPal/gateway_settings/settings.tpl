                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="inputPaypalId">PayPal Email</label>
                                    <input class="form-control" type="email" id="inputPayPalEmail" name="paypal_email" value="{$PAYPAL_EMAIL_VALUE}" placeholder="PayPal Email">
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