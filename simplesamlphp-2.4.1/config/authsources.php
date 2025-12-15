<?php

$config = [

    // An authentication source which can authenticate against SAML 2.0 IdPs.
    'default-sp' => [
        'saml:SP',

        // The entity ID of this SP.
        // Must match what's registered in Azure Entra ID
        'entityID' => 'https://pro2-dev.sabanciuniv.edu/shadowing',

        // The entity ID of the IdP this SP should contact.
        'idp' => 'https://sts.windows.net/f1a26096-6ac1-45ab-86a3-938aa985bdf5/',

    ],


];
