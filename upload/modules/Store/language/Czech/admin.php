<?php
/*
 *  Made by Partydragen, Translated by Fjuro
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
    'unable_to_create_image_directory' => 'Unable to create the <strong>/uploads/store</strong> directory to store images.',
    'enable_player_login' => 'Require customer to enter minecraft username when visiting store (Disabling will force user login)',
    'user_dont_exist' => 'User does not exists',

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
    'details' => 'Podrobnosti',
    
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
    'parent_category' => 'Nadřazená kategorie',
    'no_parent' => 'Žádná nadřazená',
    'hide_category_from_menu' => 'Nezobrazovat tuto kategorii v menu.',
    'hide_category_from_dropdown_menu' => 'Nezobrazovat tuto kategori v rozbalovací nabídce podkategorie.',
    'disable_category' => 'Zakázat tuto kategorii a odebrat jí z obchodu.',
    
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
    'hide_product_from_store' => 'Nezobrazovat tento produkt v obchodě.',
    'disable_product' => 'Zakázat tento produkt a odebrat jej z obchodu.',
    
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
    'fields' => 'Pole',
    'field' => 'Pole',
    'new_field' => 'Nové pole',
    'identifier' => 'Identifikátor',
    'description' => 'Popis',
    'field_created_successfully' => 'Pole úspěšně vytvořeno',
    'field_updated_successfully' => 'Pole úspěšně aktualizováno',
    'field_deleted_successfully' => 'Pole úspěšně odstraněno',
    'creating_field' => 'Vytváření nového pole',
    'editing_field_x' => 'Úprava pole {x}',
    'none_fields_defined' => 'Zatím zde nejsou žádná pole.',
    'confirm_delete_field' => 'Opravdu chcete odstranit toto pole?',
    'options' => 'Možnosti',
    'options_help' => 'Každá možnost na nový řádek; může být ponecháno prázdné (pouze možnosti)',
    'field_order' => 'Pořadí pole',
    'delete_field' => 'Opravdu chcete odstranit toto pole?',
    'number' => 'Číslo',
    'radio' => 'Výběr z možností',
    'checkbox' => 'Zaškrtávací pole',
    'minimum_characters' => 'Minimum znaků (0 pro zakázání)',
    'maximum_characters' => 'Maximum znaků (0 pro zakázání)',
    'fields_info' => 'Pole vám umožňují vytvářet přizpůsobitelné produkty, například umožnění uživatelům vybrat si barvu jejich jména po zakoupení.',
    
    'field_identifier_required' => 'Je vyžadován identifikátor pole.',
    'field_description_required' => 'Je vyžadován popis pole.',
    'field_identifier_minimum' => 'Identifikátor pole musí obsahovat alespoň 2 znaky.',
    'field_identifier_maximum' => 'Identifikátor pole může obsahovat maximálně 32 znaků.',
    'field_description_minimum' => 'Popis pole musí obsahovat alespoň 2 znaky.',
    'field_description_maximum' => 'Popis pole může obsahovat maximálně 255 znaků.',
    
    // Payment
    'confirm_payment_deletion' => 'Opravdu chcete odstranit platbu?',
    
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
