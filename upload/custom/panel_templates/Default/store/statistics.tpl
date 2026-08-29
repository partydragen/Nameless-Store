{include file='header.tpl'}

<body id="page-top">
<div id="wrapper">
    {include file='sidebar.tpl'}
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            {include file='navbar.tpl'}
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">{$STATISTICS}</h1>
                    <div class="d-flex align-items-center">
                        <form method="get" class="mr-3">
                            <label class="sr-only" for="statisticsRange">{$DATE_RANGE}</label>
                            <select id="statisticsRange" name="days" class="form-control form-control-sm" onchange="this.form.submit()">
                                {foreach from=$RANGE_OPTIONS item=range}
                                    <option value="{$range.value}"{if $range.selected} selected{/if}>{$range.label}</option>
                                {/foreach}
                            </select>
                        </form>
                        <ol class="breadcrumb float-sm-right mb-0">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$STORE}</li>
                            <li class="breadcrumb-item active">{$STATISTICS}</li>
                        </ol>
                    </div>
                </div>

                {include file='includes/update.tpl'}

                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">{$REVENUE}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{$REVENUE_VALUE}</div></div><div class="col-auto"><i class="fas fa-coins fa-2x text-gray-300"></i></div></div></div></div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{$COMPLETED_PAYMENTS}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{$PAYMENTS_VALUE}</div></div><div class="col-auto"><i class="fas fa-receipt fa-2x text-gray-300"></i></div></div></div></div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">{$AVERAGE_PAYMENT}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{$AVERAGE_PAYMENT_VALUE}</div></div><div class="col-auto"><i class="fas fa-calculator fa-2x text-gray-300"></i></div></div></div></div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{$UNIQUE_CUSTOMERS}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{$CUSTOMERS_VALUE}</div></div><div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div></div></div></div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">{$ACTIVE_SUBSCRIPTIONS}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{$ACTIVE_SUBSCRIPTIONS_VALUE}</div></div><div class="col-auto"><i class="fas fa-sync-alt fa-2x text-gray-300"></i></div></div></div></div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">{$REFUNDED_AMOUNT}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{$REFUNDED_VALUE}</div></div><div class="col-auto"><i class="fas fa-undo-alt fa-2x text-gray-300"></i></div></div></div></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-8 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">{$REVENUE_OVER_TIME}</h6></div>
                            <div class="card-body"><div class="chart-area"><canvas id="storeRevenueChart"></canvas></div></div>
                        </div>
                    </div>
                    <div class="col-xl-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">{$GATEWAY_BREAKDOWN}</h6></div>
                            <div class="card-body"><div class="chart-pie"><canvas id="storeGatewayChart"></canvas></div></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-7 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">{$TOP_PRODUCTS}</h6></div>
                            <div class="card-body"><div style="height:320px"><canvas id="storeProductsChart"></canvas></div></div>
                        </div>
                    </div>
                    <div class="col-xl-5 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">{$TOP_PRODUCTS}</h6></div>
                            <div class="card-body p-0">
                                {if count($TOP_PRODUCT_LIST)}
                                    <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>{$TOP_PRODUCTS}</th><th class="text-right">{$UNITS_SOLD}</th><th class="text-right">{$REVENUE}</th></tr></thead><tbody>
                                    {foreach from=$TOP_PRODUCT_LIST item=product}<tr><td>{$product.name}</td><td class="text-right">{$product.quantity}</td><td class="text-right font-weight-bold">{$product.revenue}</td></tr>{/foreach}
                                    </tbody></table></div>
                                {else}
                                    <p class="text-muted text-center p-4 mb-0">{$NO_DATA}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {include file='footer.tpl'}
    </div>
</div>

{include file='scripts.tpl'}
<script>
$(function () {
    Chart.defaults.global.defaultFontFamily = 'Nunito,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    var labels = [{foreach from=$CHART_LABELS item=label name=labels}'{$label|escape:"javascript"}'{if !$smarty.foreach.labels.last},{/if}{/foreach}];
    new Chart(document.getElementById('storeRevenueChart'), {
        type: 'line',
        data: { labels: labels, datasets: [
            { label: '{$REVENUE|escape:"javascript"}', data: [{foreach from=$CHART_REVENUE item=value name=revenue}{$value}{if !$smarty.foreach.revenue.last},{/if}{/foreach}], borderColor: '#1cc88a', backgroundColor: 'rgba(28,200,138,.08)', pointRadius: 2, lineTension: .25, yAxisID: 'revenue' },
            { label: '{$PAYMENTS|escape:"javascript"}', data: [{foreach from=$CHART_PAYMENTS item=value name=payments}{$value}{if !$smarty.foreach.payments.last},{/if}{/foreach}], borderColor: '#4e73df', backgroundColor: 'transparent', pointRadius: 2, lineTension: .25, yAxisID: 'payments' }
        ]},
        options: { maintainAspectRatio: false, legend: { position: 'bottom' }, scales: { yAxes: [
            { id: 'revenue', position: 'left', ticks: { beginAtZero: true } },
            { id: 'payments', position: 'right', gridLines: { drawOnChartArea: false }, ticks: { beginAtZero: true, precision: 0 } }
        ] } }
    });

    var gatewayLabels = [{foreach from=$GATEWAY_LABELS item=label name=gateways}'{$label|escape:"javascript"}'{if !$smarty.foreach.gateways.last},{/if}{/foreach}];
    var gatewayValues = [{foreach from=$GATEWAY_VALUES item=value name=gateway_values}{$value}{if !$smarty.foreach.gateway_values.last},{/if}{/foreach}];
    new Chart(document.getElementById('storeGatewayChart'), {
        type: 'doughnut',
        data: { labels: gatewayLabels, datasets: [{ data: gatewayValues, backgroundColor: ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#6f42c1','#fd7e14'] }] },
        options: { maintainAspectRatio: false, legend: { position: 'bottom' }, cutoutPercentage: 68 }
    });

    new Chart(document.getElementById('storeProductsChart'), {
        type: 'horizontalBar',
        data: { labels: [{foreach from=$TOP_PRODUCT_LABELS item=label name=products}'{$label|escape:"javascript"}'{if !$smarty.foreach.products.last},{/if}{/foreach}], datasets: [{ label: '{$UNITS_SOLD|escape:"javascript"}', data: [{foreach from=$TOP_PRODUCT_VALUES item=value name=product_values}{$value}{if !$smarty.foreach.product_values.last},{/if}{/foreach}], backgroundColor: '#4e73df' }] },
        options: { maintainAspectRatio: false, legend: { display: false }, scales: { xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] } }
    });
});
</script>
</body>
</html>
