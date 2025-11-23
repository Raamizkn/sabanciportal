# SAML SSO Setup Instructions

## ✅ Completed Steps

1. ✅ Downloaded and installed SimpleSAMLPHP v2.4.1
2. ✅ Copied config files from sample repo
3. ✅ Created .htaccess for routing
4. ✅ Config files ready for customization

## 📝 Configuration Required

### Step 1: Update `simplesamlphp-2.4.1/config/config-local.php`

You need to configure these values for your environment:

```php
$config['baseurlpath'] = 'YOUR_APP_URL/saml';
// Examples:
// Local: 'http://localhost:8000/saml'
// Test: 'https://apps-test.sabanciuniv.edu/internship-portal/saml'
// Prod: 'https://apps.sabanciuniv.edu/internship-portal/saml'

$config['cachedir'] = '/path/to/cache';  
// Keep it outside webroot for security

$config['secretsalt'] = 'GENERATE_UNIQUE_RANDOM_STRING';
// Generate with: openssl rand -base64 32

$config['session.cookie.path'] = '/internship-portal';  
// Or '/' for root

$config['session.phpsession.cookiename'] = 'InternshipssoSAML';
$config['session.authtoken.cookiename'] = 'InternshipssoSAMLAuthToken';
```

### Step 2: Register Reply URL with Azure Entra ID

Your SAML Reply URL (Redirect URI) must be registered in Azure Entra ID:

**Reply URL Format:**
```
{YOUR_APP_URL}/saml/module.php/saml/sp/saml2-acs.php/default-sp
```

**Examples:**
- Local: `http://localhost:8000/saml/module.php/saml/sp/saml2-acs.php/default-sp`
- Test: `https://apps-test.sabanciuniv.edu/internship-portal/saml/module.php/saml/sp/saml2-acs.php/default-sp`

**Contact Suat Yurdagün to register your Reply URL.**

### Step 3: Test SimpleSAMLPHP Installation

After configuring, visit:
```
http://your-app-url/saml
```

You should see the SimpleSAMLPHP welcome page.

### Step 4: Add to .gitignore

Add these lines to your `.gitignore`:

```
simplesamlphp-2.4.1/config/config-local.php
simplesamlphp-2.4.1/metadata/saml20-idp-remote-local.php
simplesamlphp-2.4.1/cache/
```

## 🔐 SAML Attributes Available

After successful authentication, you'll have access to:

- `samaccountname[0]` - Username
- `mail[0]` - Email
- `givenname[0]` - First name  
- `sn[0]` - Last name
- `universityID[0]` - University ID (if available)
- `ou[0]` - Organizational Unit (if available)

Contact Suat Yurdagün if you need additional attributes.

## 🚀 Next Steps

Once configuration is complete, I'll integrate SAML authentication into your internship portal:

1. Create SAML authentication handler
2. Update login page to use SAML
3. Auto-provision users from SAML attributes
4. Handle role detection (student/admin/company)

