# 🚀 Quick Start Guide: SAML SSO Integration

## ✅ What's Done

All SAML SSO integration is **complete**! Your login page now has a beautiful SSO button:

```
┌─────────────────────────────────────┐
│   🏛️ Sabancı University Logo        │
│                                     │
│  Login to your account              │
│  Enter your credentials below       │
│                                     │
│  📧 Email                           │
│  ┌─────────────────────────┐       │
│  │ john@example.com        │       │
│  └─────────────────────────┘       │
│                                     │
│  🔒 Password                        │
│  ┌─────────────────────────┐       │
│  │ •••••••••••             │       │
│  └─────────────────────────┘       │
│                                     │
│  ☐ Remember   Forgot password?     │
│                                     │
│  ┌─────────────────────────┐       │
│  │      Sign in            │       │
│  └─────────────────────────┘       │
│                                     │
│  ─────────── OR ───────────        │
│                                     │
│  ┌─────────────────────────┐       │
│  │ 🔐 Sign in with         │       │
│  │    Sabancı SSO          │       │
│  └─────────────────────────┘       │
│                                     │
│  Register as a new user             │
└─────────────────────────────────────┘
```

## 🔧 Before You Can Use It

### 1️⃣ Configure Your Environment (5 minutes)

Edit this file:
```
simplesamlphp-2.4.1/config/config-local.php
```

Update these 3 key values:

```php
// 1. Your application URL + /saml
$config['baseurlpath'] = 'http://localhost:8000/saml';

// 2. Generate a secret (run this command):
// openssl rand -base64 32
$config['secretsalt'] = 'paste-generated-secret-here';

// 3. Cache directory (create this folder first!)
$config['cachedir'] = '/tmp/saml-cache';
```

### 2️⃣ Register with Azure (contact IT)

Send this info to **Suat Yurdagün**:

```
Application: Sabancı Internship Portal
Reply URL: http://localhost:8000/saml/module.php/saml/sp/saml2-acs.php/default-sp
```

*(Change localhost:8000 to your actual server URL)*

### 3️⃣ Test It! (2 minutes)

1. Visit: `http://localhost:8000/saml`
   - Should show SimpleSAMLPHP status page
   
2. Click "Sign in with Sabancı SSO" on login page
   - Should redirect to Azure login
   - After login, should create/update your account
   - Should redirect to your dashboard

---

## 🎯 How Users Will Use It

### Students
1. Go to internship portal
2. Click **"Sign in with Sabancı SSO"**
3. Login with Sabancı credentials  
4. Automatically redirected to student dashboard
5. Account created automatically if first time

### Admins
1. Same process, but admin must exist in database first
2. If not in database: error message shown
3. Contact system admin to create account

---

## 📂 Project Structure

```
sabanciportal-3/
├── .htaccess                          ← NEW: Routes /saml requests
├── .gitignore                         ← NEW: Protects sensitive files
├── simplesamlphp-2.4.1/              ← NEW: SAML library
│   ├── config/
│   │   ├── config.php                 ← Copied from sample
│   │   ├── config-local.php           ← CONFIGURE THIS!
│   │   └── authsources.php            ← Copied from sample
│   └── metadata/
│       ├── saml20-idp-remote.php      ← Copied from sample
│       └── saml20-idp-remote-local.php ← Azure config
├── backend/
│   └── auth/
│       ├── auth.php                   ← Existing
│       ├── saml_auth.php              ← NEW: SAML logic
│       └── saml_login_endpoint.php    ← NEW: Login endpoint
└── internship-portal/
    └── index.html                     ← UPDATED: Added SSO button
```

---

## ⚙️ Technical Details

### Authentication Flow

```
User clicks "Sign in with Sabancı SSO"
    ↓
backend/auth/saml_login_endpoint.php
    ↓
Redirects to Azure Entra ID
    ↓
User authenticates with Sabancı credentials
    ↓
Azure redirects back with SAML token
    ↓
/saml/module.php/saml/sp/saml2-acs.php/default-sp
    ↓
SimpleSAMLPHP validates token
    ↓
backend/auth/saml_auth.php
    ├─→ Extract user info from SAML
    ├─→ Detect role (student/admin)
    ├─→ Create/update user in database
    └─→ Create session
    ↓
Redirect to appropriate dashboard
```

### User Provisioning

**Students:**
- Automatically created on first login
- Name, email, university ID stored
- Updated on subsequent logins

**Admins:**
- Must be manually created in database first
- Only session is created on login
- Not auto-provisioned for security

### Role Detection

Based on email domain:
- `*@sabanciuniv.edu` → Student
- `admin@*` or `staff@*` → Admin

---

## 🔍 Testing Commands

### Test SimpleSAMLPHP Installation
```bash
# 1. Check if SimpleSAMLPHP is accessible
curl http://localhost:8000/saml

# 2. Should see SimpleSAMLPHP status page
# Not: SimpleSAMLPHP is installed and configured
```

### Check Configuration
```bash
# View config-local.php
cat simplesamlphp-2.4.1/config/config-local.php

# Check if cache directory exists
ls -la /tmp/saml-cache

# Create cache directory if needed
mkdir -p /tmp/saml-cache
chmod 777 /tmp/saml-cache
```

### Monitor Logs
```bash
# SimpleSAMLPHP logs
tail -f simplesamlphp-2.4.1/log/simplesamlphp.log

# PHP error logs (location varies)
tail -f /var/log/apache2/error.log
# or
tail -f /usr/local/var/log/php-fpm.log
```

---

## ❓ FAQ

### Q: Can users still use email/password login?
**A:** Yes! Both methods work. Users can choose their preferred method.

### Q: What happens if SSO user doesn't exist in database?
**A:** 
- **Students:** Automatically created
- **Admins:** Error shown, must be created manually first

### Q: How do I create an admin user manually?
**A:** Insert into `admin_users` table or use existing admin management interface.

### Q: Can I disable email/password login?
**A:** Yes, just remove/hide the email/password form fields in `index.html`.

### Q: Is this secure?
**A:** Yes! Uses industry-standard SAML 2.0 protocol. Sensitive config files are excluded from git.

### Q: What if SAML authentication fails?
**A:** Users see a friendly error page with "Back to Login" link. Error is logged for debugging.

---

## 📞 Need Help?

### Configuration Issues
1. Check `SAML_SETUP_INSTRUCTIONS.md` for detailed config guide
2. Check `SAML_INTEGRATION_COMPLETE.md` for complete documentation

### Azure Registration
- Contact: **Suat Yurdagün**
- Needed: Reply URL registration

### Database Issues
- Check: `backend/config/database.php`
- Verify: Tables exist and are accessible

---

## 🎉 You're Ready!

Once you've completed steps 1 & 2 above, your portal will have enterprise-grade SSO authentication! 

**Next Steps:**
1. Configure `config-local.php` ← Start here!
2. Contact Suat Yurdagün for Azure registration
3. Test with a real Sabancı account
4. Deploy and enjoy! 🚀

