<?php

interface ConnectionsBase {

    /**
     * Called when connection settings page is loaded
     */
    public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields);

}