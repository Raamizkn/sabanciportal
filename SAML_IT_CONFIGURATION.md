# SAML Configuration from IT Department

## 📋 Application Information

**Received from IT:** November 22, 2024

### Application Details
```
Application Name: pro2-dev.sabanciuniv.edu/shadowing
Entity ID:       https://pro2-dev.sabanciuniv.edu/shadowing
Reply URL:       https://pro2-dev.sabanciuniv.edu/shadowing/saml/module.php/saml/sp/saml2-acs.php/default-sp
Metadata URL:    https://login.microsoftonline.com/f1a26096-6ac1-45ab-86a3-938aa985bdf5/FederationMetadata/2007-06/FederationMetadata.xml?appid=ed26c438-e8b5-4597-a655-2651b3d24882
```

## 🔐 Certificate Information

**Certificate for:** SAML_Cert_pro2-dev.sabanciuniv.edu/shadowing  
**Valid From:** 2025-11-20 11:59:00  
**Valid To:** 2028-11-20 12:09:00

**Certificate Content:**
```
MIIC7jCCAdagAwIBAgIIJAl3m9GVBfEwDQYJKoZIhvcNAQELBQAwNzE1MDMGA1UEAwwsU0FNTF9DZXJ0X3BybzItZGV2LnNhYmFuY2l1bml2LmVkdS9zaGFkb3dpbmcwHhcNMjUxMTIwMTE1OTAwWhcNMjgxMTIwMTIwOTAwWjA3MTUwMwYDVQQDDCxTQU1MX0NlcnRfcHJvMi1kZXYuc2FiYW5jaXVuaXYuZWR1L3NoYWRvd2luZzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAIlh2Md/B6RnE2YyTRL29Rh24bZIucUXZcxt3kuzm7gNVIyar0P7fGSymPnYmAoKEuUwqHwp1jMvd1vTsRUZiTdrZ0AtKIsEkWVDXT563ObnxKUn0XFFl+bGKvcRw3lwzuU27dr/Dz0WkGvaJ0J4VIw5+aNiosUqiSQGPWKo56+M07CENUCbmZ9nBIZG31XO/KZM8BTXXiS42TzUvLxHLHsMpOVfts+OHDnQpip7WwqSBq5rzFxVMcJXqiJa0puf6BN3TI3Qy6jHcsXjaULs+Zs34/qUowKG2jR4knE48rCoDJaCknC/DZhyKxra5FwkgxrXr3qJJQS60smE2yoLCYMCAwEAATANBgkqhkiG9w0BAQsFAAOCAQEAQXM5o90ZRHdUBDtbGqJ3ro/H30IQbhN4+ueWUp6NzvZSJvGYcAZ6SIqTaLVRiG0/Dyo9C4nSJAnK6WAIp/nr8fHT7fOK5dTxd8YWSJNftVBh7+19pucJyiBEWB65isDjiA/1wnnu3k8PgFxyIEbMv41vmWnb7Se4LhKZzHydgySdNS3oroHSkw0WjNJFPSjiSXdCGh0uB7rGJ7YoX10chzUhMYkBPzIJReETUbwpwisa1rAoKTf6Za/uOk1bkKwob/uORalIMfJRqlKP+tQZ8wT7JmsSRGSmXGBxvw9T91vNhVSgST4H/EOyqXFrWZddlU5lw5nVlAAr7hnGLf8q3A==
```

## 📊 Available SAML Attributes

### User Attributes
| Attribute | Description | Example |
|-----------|-------------|---------|
| `prefFirstName` | Preferred First Name | "Ahmet" |
| `prefLastName` | Preferred Last Name | "Yılmaz" |
| `universityID` | University ID Number | "123456" |
| `mail` | Email Address | "ahmet.yilmaz@sabanciuniv.edu" |
| `samaccountname` | Username | "ahmet.yilmaz" |
| `eduPersonPrimaryAffiliation` | Primary Affiliation/Role | "student" |

### eduPersonPrimaryAffiliation Values

**🎓 Role Detection Mapping:**

| Value | Turkish | English | Portal Role |
|-------|---------|---------|-------------|
| `student` | Öğrenci | Student | STUDENT |
| `faculty` | Akademik | Faculty/Academic | ADMIN |
| `staff` | İdari | Administrative Staff | ADMIN |
| `affiliate` | Outsource Akademik | External Academic | ADMIN |
| `alum` | Mezun | Alumni | STUDENT* |

*Alumni can be changed to a different role if needed

## ✅ What Was Updated

### 1. Configuration File: `config-local.php`
```php
$config['baseurlpath'] = 'https://pro2-dev.sabanciuniv.edu/shadowing/saml';
$config['session.cookie.path'] = '/shadowing';
```

### 2. Certificate File: `saml20-idp-remote-local.php`
- ✅ Updated with application-specific certificate
- ✅ Valid until 2028-11-20

### 3. Authentication Logic: `saml_auth.php`
- ✅ Added `prefFirstName`, `prefLastName` attributes
- ✅ Added `eduPersonPrimaryAffiliation` for role detection
- ✅ **Improved role detection** using official affiliation attribute
- ✅ Fallback to email-based detection if affiliation missing

## 🔄 Role Detection Flow (NEW)

```
User logs in via SAML
        ↓
Get eduPersonPrimaryAffiliation
        ↓
┌────────────────────────────┐
│ Affiliation value?         │
└────────────────────────────┘
         ↓
    "student"    → ROLE_STUDENT
    "faculty"    → ROLE_ADMIN
    "staff"      → ROLE_ADMIN
    "affiliate"  → ROLE_ADMIN
    "alum"       → ROLE_STUDENT
         ↓
    If no affiliation, check email
         ↓
    @sabanciuniv.edu → ROLE_STUDENT
```

## 🔒 SSL/HTTPS Requirement

**From IT:** 
> "Entra ID sadece https çalıştığı için uygulamaların çalışabilmesi için sunucuya ssl ekleyeceğiz."

**Translation:**
> "Since Entra ID only works with HTTPS, we will add SSL to the server for the applications to work."

**Status:** 🟡 Pending - IT will notify when SSL is added to server

**What this means:**
- Application MUST run on HTTPS
- Cannot test locally with HTTP
- Wait for IT to add SSL certificate to server
- IT will notify when ready

## 🚀 Next Steps

### Before Testing:
- [ ] Wait for IT to add SSL to server
- [ ] IT will send confirmation when SSL is ready

### After SSL is Added:
1. ✅ Configuration files are already updated
2. ✅ Certificate is already updated
3. ✅ Role detection is improved
4. Test SSO login:
   ```
   https://pro2-dev.sabanciuniv.edu/shadowing/internship-portal/index.html
   ```
5. Click "Sign in with Sabancı SSO"
6. Login with Sabancı credentials
7. Verify role is correctly detected
8. Verify redirect to correct dashboard

## 📞 IT Contact

**For questions or when SSL is ready:**
- Check with IT department
- They will notify when server SSL configuration is complete

---

## 🎯 Summary

✅ **What's Done:**
- Configuration updated for production server
- Certificate installed (valid until 2028)
- Role detection improved with `eduPersonPrimaryAffiliation`
- Reply URL already registered with Azure

🟡 **What's Pending:**
- SSL certificate installation on server (by IT)
- Final testing after SSL is ready

📧 **Once SSL is ready:**
- Application will be fully functional at `https://pro2-dev.sabanciuniv.edu/shadowing`
- Users can login with Sabancı SSO
- Automatic role detection and user provisioning will work

