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
            'X509Certificate' => 'MIIC7jCCEdagAwIBAgIIJAl3m9GVBfEwDQYJKoZIhvcNAQELBQAwNzE1MDMGA1UEAwwsU0FNTF9DZXJ0X3BybzItZGV2LnNhYmFuY2l1bml2LmVkdS9zaGFkb3dpbmcwHhcNMjUxMTIwMTE1OTAwWhcNMjgxMTIwMTIwOTAwWjA3MTUwMwYDVQQDDCxTQU1MX0NlcnRfcHJvMi1kZXYuc2FiYW5jaXVuaXYuZWR1L3NoYWRvd2luZzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAIlh2Md/B6RnE2YyTRL29Rh24bZIucUXZcxt3kuzm7gNVIyar0P7fGSymPnYmAoKEuUwqHwp1jMvd1vTsRUZiTdrZ0AtKIsEkWVDXT563ObnxKUn0XFFl+bGKvcRw3lwzuU27dr/Dz0WkGvaJ0J4VIw5+aNiosUqiSQGPWKo56+M07CENUCbmZ9nBIZG31XO/KZM8BTXXiS42TzUvLxHLHsMpOVfts+OHDnQpip7WwqSBq5rzFxVMcJXqiJa0puf6BN3TI3Qy6jHcsXjaULs+Zs34/qUowKG2jR4knE48rCoDJaCknC/DZhyKxra5FwkgxrXr3qJJQS60smE2yoLCYMCAwEAATANBgkqhkiG9w0BAQsFAAOCAQEAQXM5o90ZRHdUBDtbGqJ3ro/H30IQbhN4+ueWUp6NzvZSJvGYcAZ6SIqTaLVRiG0/Dyo9C4nSJAnK6WAIp/nr8fHT7fOK5dTxd8YWSJNftVBh7+19pucJyiBEWB65isDjiA/1wnnu3k8PgFxyIEbMv41vmWnb7Se4LhKZzHydgySdNS3oroHSkw0WjNJFPSjiSXdCGh0uB7rGJ7YoX10chzUhMYkBPzIJReETUbwpwisa1rAoKTf6Za/uOk1bkKwob/uORalIMfJRqlKP+tQZ8wT7JmsSRGSmXGBxvw9T91vNhVSgST4H/EOyqXFrWZddlU5lw5nVlAAr7hnGLf8q3A==',
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