<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - English Launguage
 */

$language_version = '2.0.0-pr12';

$language = array(
    /*
     *  Admin terms
     */
	 
    'settings' => 'Settings',
    'payments' => 'Payments',
    'store_content_max' => 'The store index content must be a maximum of 1,000,000 characters.',
    'store_path' => 'Store Path',
    'store_index_content' => 'Store Index Content',
	'checkout_complete_content' => 'Checkout Complete Content',
    'allow_guests' => 'Allow guests to make purchases?',
    'updated_successfully' => 'Updated successfully.',
    'no_payments' => 'No payments have been found!',
    'no_subcategories' => 'No subcategories.',
    'upload_new_image' => 'Upload New Image',
    'description_max_100000' => 'The description must be a maximum of 100000 characters.',
    'description_updated_successfully' => 'Description updated successfully.',
    'image_updated_successfully' => 'Image updated successfully.',
    'unable_to_upload_image' => 'Unable to upload image: {x}', // Don't replace {x} (error message)

    'user' => 'User',
    'amount' => 'Amount',
    'status' => 'Status',
    'date' => 'Date',
    'view' => 'View',
    'viewing_payments_for_user_x' => 'Viewing payments for user {x}', // Don't replace {x}
    'no_payments_for_user' => 'No payments were found for that user.',
    'ign' => 'Ingame Username',
    'uuid' => 'UUID',
    'please_enter_valid_ign_product' => 'Please enter a valid ingame username and select a product.',
    'price' => 'Price',
    'please_enter_valid_price' => 'Please enter a valid price.',
    'payment_created_successfully' => 'Payment created successfully.',
    'viewing_payment' => 'Viewing payment {x}', // Don't replace {x}
	'pending_commands' => 'Pending commands',
    'no_pending_commands' => 'No pending commands.',
	'processed_commands' => 'Processed commands',
    'no_processed_commands' => 'No processed commands.',
    'email' => 'Email',
    'must_enter_uuid' => 'You must enter a UUID!',
    'optional' => 'Optional',
    'invalid_expire_date' => 'Invalid expiry date.',
    'invalid_start_date' => 'Invalid start date.',
    'effective_on' => 'Effective On',
    'cart' => 'Cart',
    'category' => 'Category',
    'select_multiple_with_ctrl' => '(select multiple by holding Ctrl (Cmd on a Mac))',
    'value' => 'Value',
    'percentage' => 'Percentage',
    'unlimited_usage' => 'Unlimited Usage',
    'uses' => 'Uses',
    'never_expire' => 'Never Expire',
    'never' => 'Never',
    'expiry_date' => 'Expiry Date (yyyy-mm-dd)',
    'start_date' => 'Start Date (yyyy-mm-dd)',
    'expiry_date_table' => 'Expiry Date', // expiry_date without (yyyy-mm-dd)
    'basket_type' => 'Basket Type',
    'all_purchases' => 'All purchases',
    'one_off_purchases' => 'One-off purchases',
    'subscriptions' => 'Subscriptions',
    'id_x' => 'ID: {x}', // Don't replace {x}
    'transaction' => 'Transaction ID',
    'payment_method' => 'Payment Method ',
    'currency' => 'Currency',
    'currency_symbol' => 'Currency Symbol',
    
    // Gateways
    'gateways' => 'Gateways',
    'editing_gateway_x' => 'Editing gateway {x}', // Don't replace {x}
    'config_not_writable' => 'Your <strong>modules/Store/config.php</strong> file is not writable. Please check file permissions.',
	
	// Category
	'categories' => 'Categories',
	'new_category' => 'New Category',
	'category_created_successfully' => 'Category created successfully.',
	'category_updated_successfully' => 'Category updated successfully.',
	'category_deleted_successfully' => 'Category deleted successfully.',
	'category_confirm_delete' => 'Are you sure you want to delete this category?</br>Warning: This will delete all products in this category',
	'editing_category_x' => 'Editing category {x}', // Don't replace {x}
	'category_name' => 'Category Name',
    'category_description' => 'Category Description',
    'category_image' => 'Category Image',
	
	// Product
	'products' => 'Products',
	'new_product' => 'New Product',
	'product' => 'Product',
	'product_created_successfully' => 'Product created successfully.',
	'product_updated_successfully' => 'Product updated successfully.',
	'product_deleted_successfully' => 'Product deleted successfully.',
	'product_confirm_delete' => 'Are you sure you want to delete this product?',
	'editing_product_x' => 'Editing product {x}', // Don't replace {x}
	'product_name' => 'Product Name',
    'product_description' => 'Product Description',
    'product_image' => 'Product Image',
	
	// Commands
	'commands' => 'Commands',
	'new_command' => 'New Command',
	'new_command_for_x' => 'New command for {x}',
	'editing_command_for_x' => 'Editing command for {x}',
	'command_created_successfully' => 'Command created successfully.',
	'command_updated_successfully' => 'Command updated successfully.',
	'command_deleted_successfully' => 'Command deleted successfully.',
	
    /*
     *  Admin Errors
     */
	'invalid_price' => 'Invalid price.',
	'invalid_category' => 'Invalid category.',

	/*
	 *  Hooks
	 */
	'purchase_hook_info' => 'New store purchase',
    
	/*
	 *  Update Alert
	 */
	'new_update_available_x' => 'There is a new update available for the module {x}',
	'new_urgent_update_available_x' => 'There is a new urgent update available for the module {x}. Please update as soon as possible!',
	'current_version_x' => 'Current module version: {x}',
	'new_version_x' => 'New module version: {x}',
	'view_resource' => 'View Resource',

);