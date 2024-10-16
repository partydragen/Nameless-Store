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
                    <h1 class="h3 mb-0 text-gray-800">{$SALES}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$SALES}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}
                
                <div class="alert alert-warning" role="alert">
                    This features is currently for patreon supporters, it will be available for everyone in the future with means this wont function for you
                    </br></br>
                    <a href="https://partydragen.com/patreon/" target="_blank" class="btn btn-primary">Patreon</a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 style="display:inline">{$SALE_TITLE}</h5>
                        <div class="float-md-right">
                            <a href="{$BACK_LINK}" class="btn btn-warning">{$BACK}</a>
                        </div>
                        <hr>
                        
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
                        
                        <form role="form" action="" method="post">
                            <div class="form-group">
                                <label for="InputName">{$NAME}</label>
                                <input type="text" name="name" class="form-control" id="InputName" placeholder="{$NAME}" value="{$NAME_VALUE}">
                            </div>
                            <div class="form-group">
                                <label for="inputProducts">{$PRODUCTS}</label>
                                <select name="products[]" id="inputProducts" class="form-control" multiple>
                                    {foreach from=$PRODUCTS_LIST item=product}
                                        <option value="{$product.id}"{if $product.selected} selected{/if}>{$product.id} - {$product.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputDiscountType">{$DISCOUNT_TYPE}</label>
                                    <select name="discount_type" id="InputDiscountType" class="form-control">
                                        <option value="1" {if $DISCOUNT_TYPE_VALUE == '1'} selected{/if}>{$PERCENTAGE}</option>
                                        <option value="2" {if $DISCOUNT_TYPE_VALUE == '2'} selected{/if}>{$AMOUNT}</option>
                                    </select>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputDiscountAmount">{$AMOUNT}</label>
                                    <input type="number" name="discount_amount" class="form-control" id="InputDiscountAmount" placeholder="{$AMOUNT}" value="{$AMOUNT_VALUE}" min="0">
                                </div>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputMinimum">{$START_DATE}</label>
                                    <input type="datetime-local" id="inputStart" name="start_date" value="{$START_DATE_VALUE}" min="{$START_DATE_MIN}" class="form-control" />
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group">
                                    <label for="InputMaximum">{$EXPIRE_DATE}</label>
                                    <input type="datetime-local" id="inputExpire" name="expire_date" value="{$EXPIRE_DATE_VALUE}" min="{$EXPIRE_DATE_MIN}" class="form-control" />
                                </div>
                              </div>
                            </div>

                            <br />
                            <h5>User Conditions</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="inputConditionType">Condition</label>
                                    <div class="form-group">

                                            <select name="discount_type" id="inputConditionType" class="form-control">
                                                {foreach from=$CONDITIONS item=condition}
                                                    <option value="{$condition.condition}"{if $condition.selected} selected{/if}>{$condition.condition}</option>
                                                {/foreach}
                                            </select>
                                    </div>
                                </div>

                                {foreach from=$CONDITIONS item=condition}
                                <div class="col-md" id="{$condition.condition}">
                                    <label for="inputConditionType">{$condition.condition}</label>
                                    <div class="form-group">

                                            <div class="input-group">
                                                {assign var=counter value=1}
                                                {foreach from=$condition.fields item=field}
                                                    {if $field.type eq 1}
                                                        <input class="form-control" type="text" name="{$field_key}" id="{$field_key}" value="{$field.value}"
                                                               placeholder="{$field.placeholder}" tabindex="{$counter++}" {if $field.required}
                                                        required{/if}>
                                                    {else if $field.type eq 2}
                                                        <textarea class="form-control" name="{$field_key}" id="{$field_key}" placeholder="{$field.placeholder}"
                                                                  tabindex="{$counter++}"></textarea>
                                                    {else if $field.type eq 3}
                                                        <input class="form-control" type="date" name="{$field_key}" id="{$field_key}" value="{$field.value}"
                                                               tabindex="{$counter++}">
                                                    {else if $field.type eq 4}
                                                        <input class="form-control" type="password" name="{$field_key}" id="{$field_key}" value="{$field.value}"
                                                               placeholder="{$field.placeholder}" tabindex="{$counter++}" {if $field.required}
                                                        required{/if}>
                                                    {else if $field.type eq 5}
                                                        <select class="form-control" name="{$field_key}" id="{$field_key}" {if
                                                        $field.required}required{/if}>
                                                            {foreach from=$field.options item=option}
                                                                <option value="{$option.value}" {if $option.value eq $field.value} selected{/if}>
                                                                    {$option.option}</option>
                                                            {/foreach}
                                                        </select>
                                                    {else if $field.type eq 6}
                                                        <input class="form-control" type="number" name="{$field_key}" id="{$field_key}" value="{$field.value}"
                                                               placeholder="{$field.name}" tabindex="{$counter++}" {if $field.required} required{/if}>
                                                    {else if $field.type eq 7}
                                                        <input class="form-control" type="email" name="{$field_key}" id="{$field_key}" value="{$field.value}"
                                                               placeholder="{$field.placeholder}" tabindex="{$counter++}" {if $field.required}
                                                        required{/if}>
                                                    {else if $field.type eq 8}
                                                        {foreach from=$field.options item=option}
                                                            <div class="form-group">
                                                                <div class="form-control" tabindex="{$counter++}">
                                                                    <input type="radio" name="{$field_key}" value="{$option.value}" {if $field.value eq
                                                                    $option.value}checked{/if} {if $field.required}required{/if}>
                                                                    <label>{$option.option}</label>
                                                                </div>
                                                            </div>
                                                        {/foreach}
                                                    {else if $field.type eq 9}
                                                        {foreach from=$field.options item=option}
                                                            <div class="form-group">
                                                                <div class="form-control">
                                                                    <input type="checkbox" name="{$field_key}[]" value="{$option.value}" {if
                                                                    is_array($field.value) && in_array($option.value, $field.value)}checked{/if}
                                                                           tabindex="{$counter++}">
                                                                    <label>{$option.option}</label>
                                                                </div>
                                                            </div>
                                                        {/foreach}
                                                    {/if}
                                                {/foreach}
                                            </div>
                                    </div>
                                </div>
                                {/foreach}

                            </div>

                            <br />

                            <div class="form-group">
                                <input type="hidden" name="token" value="{$TOKEN}">
                                <span data-toggle="popover" data-title="Early access" data-content="This feature is currently for patreon supporters, it will be available for everyone in the future with means this wont function for you"><input type="submit" class="btn btn-primary" value="{$SUBMIT}" disabled></span>
                            </div>
                        </form>
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

<script>

    const condition = document.getElementById("inputConditionType");
    condition.addEventListener("change", (event) => {
        console.log(event.target.value);

        myFunction();

        const x = document.getElementById(event.target.value);
        x.style.display = "block";

    });

    function myFunction() {
        Array.from(document.querySelector("#inputConditionType").options).forEach(function (option_element) {
            const x = document.getElementById(option_element.value);
            x.style.display = "none";
        });
    }

    myFunction();

</script>

<script type="text/javascript">
    $(document).ready(() => {
        $('#inputProducts').select2({ placeholder: "No products selected" });
    })
</script>

</body>
</html>