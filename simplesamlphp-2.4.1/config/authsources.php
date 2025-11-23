<?php

$config = [

    // An authentication source which can authenticate against SAML 2.0 IdPs.
    'default-sp' => [
        'saml:SP',

        // The entity ID of this SP.
        'entityID' => 'https://apps-local.sabanciuniv.edu/', // CONFIGURE-ME: EntraID tarafında oluşturulmış olan uygulamnın entityID bilgisi

        // The entity ID of the IdP this SP should contact.
        // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
        'idp' => 'https://sts.windows.net/f1a26096-6ac1-45ab-86a3-938aa985bdf5/',

    ],


];
