<?php
/*
 *  Made by Partydragen
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
     
    'settings' => 'Einstellungen',
    'payments' => 'Zahlungen',
    'store_content_max' => 'Der Inhalt des Shopindex darf maximal 1.000.000 Zeichen umfassen.',
    'store_path' => 'Shoppfad',
    'store_index_content' => 'Inhalt des Store-Index',
    'checkout_complete_content' => 'Mit gesamten Inhalt zur Kasse',
    'allow_guests' => 'Gästen erlauben, Einkäufe zu tätigen?',
    'updated_successfully' => 'Erfolgreich aktualisiert.',
    'no_payments' => 'Es wurden keine Zahlungen gefunden!',
    'no_subcategories' => 'Keine Unterkategorien.',
    'upload_new_image' => 'Neues Bild hochladen',
    'description_max_100000' => 'Die Beschreibung darf maximal 100000 Zeichen lang sein.',
    'description_updated_successfully' => 'Beschreibung erfolgreich aktualisiert.',
    'image_updated_successfully' => 'Bild erfolgreich aktualisiert.',
    'unable_to_create_image_directory' => 'Unable to create the <strong>/uploads/store</strong> directory to store images.',
    'unable_to_upload_image' => 'Bild {x} kann nicht hochgeladen werden!', // Don't replace {x} (error message)
    'enable_player_login' => 'Require customer to enter minecraft username when visiting store (Disabling will force user login)',
    'user_dont_exist' => 'User does not exists',

    'user' => 'Benutzer',
    'amount' => 'Menge',
    'status' => 'Status',
    'date' => 'Datum',
    'view' => 'Ansehen',
    'viewing_payments_for_user_x' => 'Zahlungen für Nutzer {x} ansehen', // Don't replace {x}
    'no_payments_for_user' => 'Für diesen Benutzer wurden keine Zahlungen gefunden.',
    'ign' => 'Ingame Username',
    'uuid' => 'UUID',
    'please_enter_valid_ign_product' => 'Bitte gib einen gültigen Ingame-Benutzernamen ein und wähle ein Produkt aus.',
    'price' => 'preis',
    'please_enter_valid_price' => 'Gebe bitte einen gültigen Preis ein.',
    'create_payment' => 'Zahlung erstellen',
    'payment_created_successfully' => 'Payment created successfully.',
    'viewing_payment' => 'Zahlung {x} einsehen', // Don't replace {x}
    'pending_commands' => 'Austehende Befehle',
    'no_pending_commands' => 'Keine ausstehenden Befehle.',
    'processed_commands' => 'Processed commands',
    'no_processed_commands' => 'No processed commands.',
    'email' => 'E-Mail',
    'must_enter_uuid' => 'Du musst eine UUID angeben!',
    'optional' => 'Optional',
    'invalid_expire_date' => 'Ungültiges Ablaufdatum.',
    'invalid_start_date' => 'Ungültiges Startdatum.',
    'effective_on' => 'Wirksam ein',
    'cart' => 'Wagen',
    'category' => 'Kategorie',
    'select_multiple_with_ctrl' => '(Wähle mehrere aus, indem Du Strg gedrückt hälst (Cmd auf einem Mac)))',
    'value' => 'Wert',
    'percentage' => 'Prozentsatz',
    'unlimited_usage' => 'Unbegrenzte Verwendungen',
    'uses' => 'Verwendungen',
    'never_expire' => 'Läuft niemals ab',
    'never' => 'Niemals',
    'expiry_date' => 'Ablaufdatum (yyyy-mm-dd)',
    'start_date' => 'Startdatum (yyyy-mm-dd)',
    'expiry_date_table' => 'Ablaufdatum', // expiry_date without (yyyy-mm-dd)
    'basket_type' => 'Korbtyp',
    'all_purchases' => 'Alle Käufe',
    'one_off_purchases' => 'Einmalige Käufe',
    'subscriptions' => 'Abonnements',
    'id_x' => 'ID: {x}', // Don't replace {x}
    'transaction' => 'Transaction ID',
    'payment_method' => 'Zahlungsmethode ',
    'currency' => 'Währung',
    'currency_symbol' => 'Währungssymbol',
    'command' => 'Befehl',
    'details' => 'Details',
    'add_credits' => 'Add Credits',
    'remove_credits' => 'Remove Credits',
    'enter_amount_to_add' => 'Enter amount of credits to add',
    'enter_amount_to_remove' => 'Enter amount of credits to remove',
    'successfully_added_credits' => 'Successfully added {amount} credits to user',
    'successfully_removed_credits' => 'Successfully removed {amount} credits from user',
    
    // Gateways
    'gateways' => 'Gateways',
    'editing_gateway_x' => 'Gateway {x} bearbeiten', // Don't replace {x}
    'config_not_writable' => 'Deine <strong>modules/Store/config.php</strong> Datei ist nicht beschreibbar. Bitte Dateiberechtigungen prüfen.',
    'unavailable_generate_config' => 'config.php Datei kann nicht im Verzeichnis <strong>modules/Store</strong> Verzeichnis ist nicht beschreibbar. Bitte Dateiberechtigungen prüfen.',
    
    // Category
    'categories' => 'Kategorien',
    'new_category' => 'Neue Kategorie',
    'category_created_successfully' => 'Kategorie erfolgreich erstellt.',
    'category_updated_successfully' => 'Kategorie erfolgreich aktualisiert.',
    'category_deleted_successfully' => 'Kategorie erfolgreich gelöscht.',
    'category_confirm_delete' => 'Möchtest Du diese Kategorie wirklich löschen?</br>Achtung: Dadurch werden alle Produkte in dieser Kategorie gelöscht',
    'editing_category_x' => 'Kategroie {x} bearbeiten', // Don't replace {x}
    'category_name' => 'Kategorie Name',
    'category_description' => 'Kategoriebeschreibung',
    'category_image' => 'Kategoriebild',
    'parent_category' => 'Eltern-Kategorie',
    'no_parent' => 'Keine Eltern',
    'hide_category_from_menu' => 'Dieses Kategoriemenü nicht anzeigen.',
    'hide_category_from_dropdown_menu' => 'Diese Kategorie nicht im Dropdown-Menü der Unterkategorie anzeigen.',
    'disable_category' => 'Diese Kategorie deaktivieren und aus dem Shop entfernen.',
    
    // Product
    'products' => 'Produkte',
    'new_product' => 'Neues Produkt',
    'product' => 'Produkt',
    'product_created_successfully' => 'Produkt erfolgreich erstellt.',
    'product_updated_successfully' => 'Produkt erfolgreich aktualisiert.',
    'product_deleted_successfully' => 'Produkt erfolgreich gelöscht.',
    'product_confirm_delete' => 'Möchtest Du dieses Produkt wirklich löschen?',
    'editing_product_x' => 'Produkt {x} bearbeiten', // Don't replace {x}
    'product_name' => 'Prdoukt Name',
    'product_description' => 'Produktbeschreibung',
    'product_image' => 'Produktbild',
    'hide_product_from_store' => 'Dieses Produkt nicht im Shop anzeigen.',
    'disable_product' => 'Dieses Produkt deaktivieren und aus dem Shop entfernen.',
    
    // Actions
    'actions' => 'Aktionen',
    'new_action' => 'Neue Aktion',
    'new_action_for_x' => 'Neue Aktion für {x}',
    'editing_action_for_x' => 'Editing action for {x}',
    'action_created_successfully' => 'Aktion erfolgreich erstellt.',
    'action_updated_successfully' => 'Aktion erfolgreich aktualisiert.',
    'action_deleted_successfully' => 'Aktion erfolgreich gelöscht.',
    
    // Connections
    'connections' => 'Verbindungen',
    'connections_info' => 'Verbinde Deine Server mit Deinem Shop, um bestimmte Aktionen auszuführen',
    'connection' => 'Verbindung',
    'new_connection' => 'Neue Verbindung',
    'connection_id' => 'Verbindungs-ID',
    'no_connections' => 'Es wurden noch keine Verbindungen hergestellt.',
    'creating_new_connection' => 'Neue Verbindung erstellen',
    'editing_connection_x' => 'Verbindung {x} bearbeiten', // Don't replace {x}
    'connection_created_successfully' => 'Verbindung erfolgreich erstellt.',
    'connection_updated_successfully' => 'Verbindung erfolgreich aktualisiert.',
    'connection_deleted_successfully' => 'Verbindung erfolgreich gelöscht.',
    'confirm_delete_connection' => 'Möchtest Du diese Verbindung wirklich löschen?',
    
    // Fields
    'fields' => 'Felder',
    'field' => 'Feld',
    'new_field' => 'Neues Feld',
    'identifier' => 'Kennung',
    'description' => 'Beschreibung',
    'field_created_successfully' => 'Feld erfolgreich erstellt',
    'field_updated_successfully' => 'Feld erfolgreich aktualisiert',
    'field_deleted_successfully' => 'Feld erfolgreich gelöscht',
    'creating_field' => 'Neues Feld erstellen',
    'editing_field_x' => 'Feld {x} bearbeiten',
    'none_fields_defined' => 'Es sind noch keine Felder vorhanden.',
    'confirm_delete_field' => 'Möchtest Du dieses Feld wirklich löschen?',
    'options' => 'Options',
    'options_help' => 'Jede Option in einer neuen Zeile; kann leer bleiben (nur Optionen)',
    'field_order' => 'Feldsortierung',
    'delete_field' => 'Möchtest Du dieses Feld wirklich löschen?',
    'number' => 'Nummer',
    'radio' => 'Radio',
    'checkbox' => 'Checkbox',
    'minimum_characters' => 'Zeichen minimal (0 zum Deaktivieren)',
    'maximum_characters' => 'Zeichen maximal (0 zum Deaktivieren)',
    'fields_info' => 'Mithilfe von Feldern kannst Du anpassbare Produkte erstellen, z. B. können Benutzer die Farbe ihres Namens beim Kauf auswählen.',
    
    'field_identifier_required' => 'Das Identifier-Feld ist erforderlich.',
    'field_description_required' => 'Das Beschreibungsfeld ist erforderlich.',
    'field_identifier_minimum' => 'Die Feldkennung muss mindestens 2 Zeichen lang sein.',
    'field_identifier_maximum' => 'Die Feldkennung darf maximal 32 Zeichen lang sein.',
    'field_description_minimum' => 'Die Feldbeschreibung muss mindestens 2 Zeichen lang sein.',
    'field_description_maximum' => 'Die Feldbeschreibung darf maximal 255 Zeichen lang sein.',
    
    // Payment
    'confirm_payment_deletion' => 'Möchtest Du die Zahlung wirklich löschen?',
    'payment_deleted_successfully' => 'Zahlung erfolgreich gelöscht',
    'payment_pending' => 'Zahlung ausstehend',
    'payment_completed' => 'Zahlung abgeschlossen',
    'payment_refunded' => 'Zahlung zurückerstattet',
    'payment_reversed' => 'Zahlung storniert',
    'payment_denied' => 'Zahlung verweigert',
    
    /*
     *  Admin Errors
     */
    'invalid_price' => 'Ungültiger Preis.',
    'invalid_category' => 'Ungültige Kategorie.',
    'name_maximum_x' => 'Der Name muss mehr als {x} Zeichen haben',
    'name_required' => 'Gib bitte einen namen ein.',

    /*
     *  Hooks
     */
    'purchase_hook_info' => 'Neuer Shopkauf',
    
    /*
     *  Update Alert
     */
    'new_update_available_x' => 'Es ist für das Modul {x} ein neues Update verfügbar',
    'new_urgent_update_available_x' => 'There is a new urgent update available for the module {x}. Please update as soon as possible!',
    'current_version_x' => 'Aktuelle Module Version: {x}',
    'new_version_x' => 'Neue Modul Version: {x}',
    'view_resource' => 'Ressource einsehen',

);