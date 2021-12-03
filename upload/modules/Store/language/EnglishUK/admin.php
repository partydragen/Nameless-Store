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
    'create_payment' => 'Create Payment',
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
    'command' => 'Command',
    
    // Gateways
    'gateways' => 'Gateways',
    'editing_gateway_x' => 'Editing gateway {x}', // Don't replace {x}
    'config_not_writable' => 'Your <strong>modules/Store/config.php</strong> file is not writable. Please check file permissions.',
    'unavailable_generate_config' => 'Unavailable to generate config.php file in directory <strong>modules/Store</strong>, directory is not writable. Please check file permissions.',
	
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
    'parent_category' => 'Parent Category',
    'no_parent' => 'No parent',
	
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
	
	// Actions
	'actions' => 'Actions',
	'new_action' => 'New Action',
	'new_action_for_x' => 'New action for {x}',
	'editing_action_for_x' => 'Editing action for {x}',
	'action_created_successfully' => 'Action created successfully.',
	'action_updated_successfully' => 'Action updated successfully.',
	'action_deleted_successfully' => 'Action deleted successfully.',
    
    // Connections
    'connections' => 'Connections',
    'connections_info' => 'Connect your servers to your store to execute specific actions',
    'connection' => 'Connection',
    'new_connection' => 'New Connection',
    'connection_id' => 'Connection ID',
    'no_connections' => 'No connections have been made yet.',
    'creating_new_connection' => 'Creating new connection',
    'editing_connection_x' => 'Editing connection {x}', // Don't replace {x}
	'connection_created_successfully' => 'Connection created successfully.',
	'connection_updated_successfully' => 'Connection updated successfully.',
	'connection_deleted_successfully' => 'Connection deleted successfully.',
    'confirm_delete_connection' => 'Are you sure you want to delete this connection?',
    
    // Fields
    'fields' => 'Fields',
    'field' => 'Field',
    'new_field' => 'New Field',
	'identifier' => 'Identifier',
    'description' => 'Description',
	'field_created_successfully' => 'Field created successfully',
	'field_updated_successfully' => 'Field updated successfully',
	'field_deleted_successfully' => 'Field deleted successfully',
	'creating_field' => 'Creating new field',
	'editing_field_x' => 'Editing field {x}',
	'none_fields_defined' => 'There are no fields yet.',
	'confirm_delete_field' => 'Are you sure you want to delete this field?',
	'options' => 'Options',
	'options_help' => 'Each option on a new line; can be left empty (options only)',
	'field_order' => 'Field Order',
	'delete_field' => 'Are you sure you want to delete this field?',
    'number' => 'Number',
    'radio' => 'Radio',
    'checkbox' => 'Checkbox',
    'minimum_characters' => 'Minimum Characters (0 to disable)',
    'maximum_characters' => 'Maximum Characters (0 to disable)',
    'fields_info' => 'Fields enable you to create customisable products such as allowing users to select the colour of their name upon purchase.',
    
	'field_identifier_required' => 'The identifier field is required.',
    'field_description_required' => 'The description field is required.',
	'field_identifier_minimum' => 'The field identifier must be a minimum of 2 characters.',
	'field_identifier_maximum' => 'The field identifier must be a maximum of 32 characters.',
	'field_description_minimum' => 'The field description must be a minimum of 2 characters.',
	'field_description_maximum' => 'The field description must be a maximum of 255 characters.',
	
    /*
     *  Admin Errors
     */
	'invalid_price' => 'Invalid price.',
	'invalid_category' => 'Invalid category.',
    'name_maximum_x' => 'The name must be no more than {x} characters',
    'name_required' => 'Please input a name.',

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