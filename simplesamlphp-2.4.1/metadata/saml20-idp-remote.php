<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */



 $metadata['https://sts.windows.net/f1a26096-6ac1-45ab-86a3-938aa985bdf5/'] = [
    'entityid' => 'https://sts.windows.net/f1a26096-6ac1-45ab-86a3-938aa985bdf5/',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://login.microsoftonline.com/f1a26096-6ac1-45ab-86a3-938aa985bdf5/saml2',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://login.microsoftonline.com/f1a26096-6ac1-45ab-86a3-938aa985bdf5/saml2',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://login.microsoftonline.com/f1a26096-6ac1-45ab-86a3-938aa985bdf5/saml2',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    ],

    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'CONFIGURE-ME in saml20-idp-remote-local.php file',
        ],
    ],

    'attributes' => [
          // Specify friendly names for these attributes:
      ],
      'attributes.required' => [
      ],
      'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
];


if(file_exists(dirname(__FILE__).'/saml20-idp-remote-local.php')){
	include(dirname(__FILE__).'/saml20-idp-remote-local.php');
}