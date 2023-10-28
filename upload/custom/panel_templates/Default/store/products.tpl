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
                    <h1 class="h3 mb-0 text-gray-800">{$PRODUCTS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$PRODUCTS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <span class="float-md-right">
                            <a href="{$NEW_CATEGORY_LINK}" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_CATEGORY}</a>
                            <a href="{$NEW_PRODUCT_LINK}" class="btn btn-primary"><i class="fa fa-plus-circle"></i> {$NEW_PRODUCT}</a>
                        </span>

                        </br>
                        </br>

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if isset($NO_PRODUCTS)}
                            <p>{$NO_PRODUCTS}</p>
                        {else}
                            <div class="sortableCategories">
                            {foreach from=$ALL_CATEGORIES item=category}
                                <div class="card card-default" data-id="{$category.id}">
                                    <div class="card-header">
                                        <strong>{$category.name}</strong>
                                        <div class="float-md-right">
                                            <div class="btn btn-secondary btn-sm"><i class="fas fa-arrows-alt"></i></div>
                                            <a class="btn btn-warning btn-sm" href="{$category.edit_link}"><i class="fas fa-pencil-alt"></i></a>
                                            <button class="btn btn-danger btn-sm" type="button" onclick="showDeleteCategoryModal('{$category.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody class="sortableProducts" data-id="{$category.id}">
                                            {if count($category.products)}
                                                {foreach from=$category.products item=product name=product_loop}
                                                    <tr data-id="{$product.id}">
                                                        <td width="45%" style="padding-left: 35px">{$product.name} <small>{$product.id_x}</small></td>
                                                        <td width="15%"><center>{$product.price_format}</center></td>
                                                        <td width="40%" style="padding-right: 1.25rem">
                                                            {if isset($product.edit_link)}
                                                                <div class="float-md-right">
                                                                    <div class="btn btn-secondary btn-sm"><i class="fas fa-arrows-alt"></i></div>
                                                                    <a class="btn btn-warning btn-sm" href="{$product.edit_link}"><i class="fas fa-pencil-alt"></i></a>
                                                                    <button class="btn btn-danger btn-sm" type="button" onclick="showDeleteProductModal('{$product.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>
                                                                </div>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                {/foreach}
                                            {/if}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            {/foreach}
                            </div>
                        {/if}

                        <center>
                            <p>Store Module by <a href="https://partydragen.com/" target="_blank">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a></br>
                                <a class="ml-1" href="https://partydragen.com/suggestions/" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="You can submit suggestions here"><i class="fa-solid fa-thumbs-up text-warning"></i></a>
                                <a class="ml-1" href="https://discord.gg/TtH6tpp" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="Discord"><i class="fab fa-discord fa-fw text-discord"></i></a>
                                <a class="ml-1" href="https://partydragen.com/" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="Website"><i class="fas fa-globe fa-fw text-primary"></i></a>
                                <a class="ml-1" href="https://www.patreon.com/partydragen" target="_blank" data-toggle="tooltip"
                                   data-placement="top" title="Support the development on Patreon"><i class="fas fa-heart fa-fw text-danger"></i></a>
                            </p>
                        </center>
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

<div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {$CONFIRM_DELETE_CATEGORY}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                <a href="#" id="deleteCategoryLink" class="btn btn-primary">{$YES}</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {$CONFIRM_DELETE_PRODUCT}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                <a href="#" id="deleteProductLink" class="btn btn-primary">{$YES}</a>
            </div>
        </div>
    </div>
</div>

{include file='scripts.tpl'}

<script type="text/javascript">
    function showDeleteCategoryModal(id){
        $('#deleteCategoryLink').attr('href', id);
        $('#deleteCategoryModal').modal().show();
    }
    function showDeleteProductModal(id){
        $('#deleteProductLink').attr('href', id);
        $('#deleteProductModal').modal().show();
    }

    // Draggable functionality
    $(document).ready(function () {
        $('.sortableCategories').each(function() {
            $(this).sortable({
                start: function(event, ui) {
                    const start_pos = ui.item.index();
                    ui.item.data('startPos', start_pos);
                },
                update: function(event, ui) {
                    const categories = $(event.target).children();
                    const toSubmit = [];
                    categories.each(function() {
                        toSubmit.push($(this).data().id);
                    });

                    const data = new URLSearchParams();
                    data.append("token", "{$TOKEN}");
                    data.append("categories", JSON.stringify(toSubmit));

                    fetch("{$REORDER_CATEGORY_URL}", {
                        method: 'POST',
                        body: data
                    }).catch((err) => console.log(err));
                }
            })
        })

        $('.sortableProducts').each(function() {
            $(this).sortable({
                start: function(event, ui) {
                    const start_pos = ui.item.index();
                    ui.item.data('startPos', start_pos);
                },
                update: function(event, ui) {
                    const products = $(event.target).children();
                    const toSubmit = [];
                    products.each(function() {
                        toSubmit.push($(this).data().id);
                    });

                    const data = new URLSearchParams();
                    data.append("token", "{$TOKEN}");
                    data.append("products", JSON.stringify(toSubmit));

                    fetch('{$REORDER_PRODUCTS_URL}', {
                        method: 'POST',
                        body: data
                    }).catch((err) => console.log(err));
                }
            })
        })
    })
</script>

</body>
</html>
