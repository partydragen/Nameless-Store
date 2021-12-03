<?php
/*
 *	Made by Partydragen, Translated by Fjuro
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - Czech Launguage
 */

$language_version = '2.0.0-pr12';

$language = array(
    /*
     *  Admin terms
     */
	 
    'settings' => 'Nastavení',
    'payments' => 'Platby',
    'store_content_max' => 'Obsah domovské stránky obchodu může mít maximálně 1 000 000 znaků.',
    'store_path' => 'Cesta k obchodu',
    'store_index_content' => 'Obsah domovské stránky obchodu',
    'checkout_complete_content' => 'Obsah dokončení objednávky',
    'allow_guests' => 'Umožnit hostům vytvářet objednávky?',
    'updated_successfully' => 'Úspěšně aktualizováno.',
    'no_payments' => 'Nebyly nalezeny žádné platby!',
    'no_subcategories' => 'Žádné podkategorie.',
    'upload_new_image' => 'Nahrát nový obrázek',
    'description_max_100000' => 'Popis může obsahovat maximálně 100 000 znaků.',
    'description_updated_successfully' => 'Popis úspěšně aktualizován.',
    'image_updated_successfully' => 'Obrázek úspěšně aktualizován.',
    'unable_to_upload_image' => 'Nepodařilo se nahrát obrázek: {x}', // Don't replace {x} (error message)

    'user' => 'Uživatel',
    'amount' => 'Částka',
    'status' => 'Stav',
    'date' => 'Datum',
    'view' => 'Zobrazit',
    'viewing_payments_for_user_x' => 'Prohlížení plateb uživatele {x}', // Don't replace {x}
    'no_payments_for_user' => 'U tohoto uživatele nebyly nalezeny žádné platby.',
    'ign' => 'Herní jméno',
    'uuid' => 'UUID',
    'please_enter_valid_ign_product' => 'Zadejte prosím platné herní jméno a vyberte produkt.',
    'price' => 'Cena',
    'please_enter_valid_price' => 'Zadejte prosím platnou cenu.',
    'create_payment' => 'Vytvořit platbu',
    'payment_created_successfully' => 'Platba úspěšně vytvořena.',
    'viewing_payment' => 'Prohlížení platby {x}', // Don't replace {x}
    'pending_commands' => 'Čekající příkazy',
    'no_pending_commands' => 'Žádné čekající příkazy.',
    'processed_commands' => 'Zpracované příkazy',
    'no_processed_commands' => 'Žádné zpracované příkazy.',
    'email' => 'E-mail',
    'must_enter_uuid' => 'Musíte zadat UUID!',
    'optional' => 'Volitelné',
    'invalid_expire_date' => 'Neplatné datum vypršení.',
    'invalid_start_date' => 'Neplatné datum začátku.',
    'effective_on' => 'Efektivní',
    'cart' => 'Košík',
    'category' => 'Kategorie',
    'select_multiple_with_ctrl' => '(vyberte více držením Ctrl (Cmd na Macu))',
    'value' => 'Hodnota',
    'percentage' => 'Procento',
    'unlimited_usage' => 'Neomezená použití',
    'uses' => 'Použití',
    'never_expire' => 'Nikdy nevyprší',
    'never' => 'Nikdy',
    'expiry_date' => 'Datum vypršení (rrrr-mm-dd)',
    'start_date' => 'Datum začátku (rrrr-mm-dd)',
    'expiry_date_table' => 'Datum vypršení', // expiry_date without (yyyy-mm-dd)
    'basket_type' => 'Typ košíku',
    'all_purchases' => 'Všechny platby',
    'one_off_purchases' => 'Jednorázové platby',
    'subscriptions' => 'Předplatná',
    'id_x' => 'ID: {x}', // Don't replace {x}
    'transaction' => 'ID transakce',
    'payment_method' => 'Způsob platby',
    'currency' => 'Měna',
    'currency_symbol' => 'Symbol měny',
    'command' => 'Příkaz',
    'details' => 'Details',
    
    // Gateways
    'gateways' => 'Platební brány',
    'editing_gateway_x' => 'Úprava platební brány {x}', // Don't replace {x}
    'config_not_writable' => 'Váš soubor <strong>modules/Store/config.php</strong> není zapisovatelný. Zkontrolujte prosím oprávnění souboru.',
    'unavailable_generate_config' => 'Nepodařilo se vygenerovat soubor config.php v adresáři <strong>modules/Store</strong>, adresář není zapisovatelný. Zkontrolujte prosím oprávnění souborů.',
	
    // Category
    'categories' => 'Kategorie',
    'new_category' => 'Nová kategorie',
    'category_created_successfully' => 'Kategorie úspěšně vytvořena.',
    'category_updated_successfully' => 'Kategorie úspěšně upravena.',
    'category_deleted_successfully' => 'Kategorie úspěšně odstraněna.',
    'category_confirm_delete' => 'Opravdu chcete odstranit tuto kategorii?</br>Varování: toto odstraní všechny produkty v kategorii',
    'editing_category_x' => 'Úprava kategorie {x}', // Don't replace {x}
    'category_name' => 'Název kategorie',
    'category_description' => 'Popis kategorie',
    'category_image' => 'Obrázek kategorie',
    'parent_category' => 'Parent Category',
    'no_parent' => 'No parent',
    'hide_category_from_menu' => 'Do not display this category menu.',
    'hide_category_from_dropdown_menu' => 'Do not display this category in the subcategory drop down menu.',
    'disable_category' => 'Disable this category and remove it from the store.',
	
    // Product
    'products' => 'Produkty',
    'new_product' => 'Nový produkt',
    'product' => 'Produkt',
    'product_created_successfully' => 'Produkt úspěšně vytvořen.',
    'product_updated_successfully' => 'Produkt úspěšně upraven.',
    'product_deleted_successfully' => 'Produkt úspěšně odstraněn.',
    'product_confirm_delete' => 'Opravdu chcete odstranit tento produkt?',
    'editing_product_x' => 'Úprava produktu {x}', // Don't replace {x}
    'product_name' => 'Název produktu',
    'product_description' => 'Popis produktu',
    'product_image' => 'Obrázek produktu',
    'hide_product_from_store' => 'Do not display this product on store.',
    'disable_product' => 'Disable this product and remove it from the store.',
	
    // Actions
    'actions' => 'Akce',
    'new_action' => 'Nová akce',
    'new_action_for_x' => 'Nová akce pro {x}',
    'editing_action_for_x' => 'Úprava akce pro {x}',
    'action_created_successfully' => 'Akce úspěšně vytvořena.',
    'action_updated_successfully' => 'Akce úspěšně upravena.',
    'action_deleted_successfully' => 'Akce úspěšně odstraněna.',
    
    // Connections
    'connections' => 'Propojení',
    'connections_info' => 'Připojte své servery k vašemu obchodu pro vykonání určitých akcí',
    'connection' => 'Propojení',
    'new_connection' => 'Nové propojení',
    'connection_id' => 'ID propojení',
    'no_connections' => 'Zatím nebyla vytvořena žádná propojení.',
    'creating_new_connection' => 'Vytváření nového propojení',
    'editing_connection_x' => 'Úprava propojení {x}', // Don't replace {x}
    'connection_created_successfully' => 'Propojení úspěšně vytvořeno.',
    'connection_updated_successfully' => 'Propojení úspěšně upraveno.',
    'connection_deleted_successfully' => 'Propojení úspěšně odstraněno.',
    'confirm_delete_connection' => 'Opravdu chcete odstranit toto propojení?',
    
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
    
    // Payment
    'confirm_payment_deletion' => 'Are you sure you want to delete the payment?',
	
    /*
     *  Admin Errors
     */
    'invalid_price' => 'Neplatná cena.',
    'invalid_category' => 'Neplatná kategorie.',
    'name_maximum_x' => 'Název nesmí obsahovat více než {x} znaků',
    'name_required' => 'Zadejte prosím název.',

    /*
    *  Hooks
    */
    'purchase_hook_info' => 'Nová platba v obchodě',
    
    /*
    *  Update Alert
    */
    'new_update_available_x' => 'Je dostupná nová aktualizace doplňku {x}',
    'new_urgent_update_available_x' => 'Je dostupná nová závažná aktualizace doplňku {x}. Aktualizujte jak nejdříve je to možné!',
    'current_version_x' => 'Současná verze doplňku: {x}',
    'new_version_x' => 'Nová verze doplňku: {x}',
    'view_resource' => 'Zobrazit stránku doplňku',

);