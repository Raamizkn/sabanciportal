# Sabanci University Internship Portal

A complete internship management platform connecting students with companies for internship opportunities.

## 🎯 Project Status

✅ **Backend**: Fully functional with database integration
✅ **Authentication**: Complete role-based access control
✅ **Database**: MySQL integration with remote hosting
✅ **API**: RESTful API with security
⚠️ **Frontend**: HTML pages ready for API integration

## 🏗️ Architecture

### Backend (`/backend`)
- **Language**: PHP 8.4+
- **Pattern**: Front Controller
- **Database**: MySQL (Remote: pro2-dev.sabanciuniv.edu)
- **Authentication**: Session-based with role verification
- **API**: RESTful endpoints

### Frontend (`/internship-portal`)
- **Framework**: Bootstrap 5
- **Pages**: Admin, Company, Student dashboards
- **Status**: Ready for API integration
- **JS Service**: `js/api.js` available

## 🚀 Quick Start

### Prerequisites
- PHP 8.4+ (built-in server)
- MySQL access to `pro2-dev.sabanciuniv.edu`
- Browser with JavaScript enabled

### 1. Database Setup

```bash
cd backend
php config/create_tables.php
```

This will:
- Create all tables (students, companies, internships, applications, etc.)
- Insert sample data for testing

### 2. Start Backend Server

```bash
cd backend
php -S localhost:8001
```

### 3. Access Frontend

Open `internship-portal/index.html` in your browser

## 👥 User Roles

### 🔐 Admin
- Create/manage company accounts
- Create/manage student accounts
- Manage academic terms
- System oversight

**Test Credentials:**
- Email: `admin@example.com`
- Password: `password123`

### 🏢 Company
- Create internship postings
- Review applications
- Offer positions
- Manage company profile

**Test Credentials:**
- Email: `company@example.com`
- Password: `password123`

### 🎓 Student
- Browse internships
- Apply for positions
- Track application status
- Confirm offers

**Test Credentials:**
- Email: `student@example.com`
- Password: `password123`

## 📡 API Endpoints

### Authentication
```bash
POST /index.php?entity=auth&action=login
POST /index.php?entity=auth&action=logout
GET  /index.php?entity=auth&action=check
```

### Internships
```bash
GET  /index.php?entity=internships
GET  /index.php?entity=internships&id={id}
POST /index.php?entity=internships&action=create    # Requires: Company
POST /index.php?entity=internships&id={id}&action=update    # Requires: Company
POST /index.php?entity=internships&id={id}&action=delete    # Requires: Company
```

### Applications
```bash
GET  /index.php?entity=applications&student_id={id}
POST /index.php?entity=applications&action=apply            # Requires: Student
POST /index.php?entity=applications&id={id}&action=withdraw # Requires: Student
POST /index.php?entity=applications&id={id}&action=confirm_offer # Requires: Student
POST /index.php?entity=applications&id={id}&action=update_status_company # Requires: Company
```

### Admin
```bash
GET  /index.php?entity=admin&resource=companies
POST /index.php?entity=admin&resource=companies&action=add # Requires: Admin
POST /index.php?entity=admin&resource=students&action=add  # Requires: Admin
POST /index.php?entity=admin&resource=terms&action=add     # Requires: Admin
```

**See `api_documentation.md` for complete API reference**

## 🔒 Security Features

✅ **Authentication Required**: All POST operations require login
✅ **Role-Based Access**: Admin, Company, Student roles enforced
✅ **Ownership Verification**: Users can only modify their own data
✅ **Session Management**: Secure session handling
✅ **Password Hashing**: bcrypt password hashing
✅ **SQL Injection Protection**: Prepared statements

## 📊 Database

**Host**: `pro2-dev.sabanciuniv.edu`
**Database**: `shadowing`
**Tables**: 8 tables
- `admin_users`
- `students`
- `companies`
- `internships`
- `applications`
- `documents`
- `terms`
- `evaluations`

**See `database.md` for complete schema**

## 📁 Project Structure

```
sabanciportal-1/
├── backend/                  # PHP Backend
│   ├── auth/                # Authentication module
│   ├── config/              # Database config
│   ├── data/               # Data loading
│   ├── handlers/           # API handlers
│   └── index.php           # Main router
├── internship-portal/      # Frontend
│   ├── admin/              # Admin pages
│   ├── company/            # Company pages
│   ├── student/            # Student pages
│   └── js/                 # JavaScript
│       └── api.js          # API service
├── *.md                    # Documentation
└── database.md             # Database docs
```

## 🧪 Testing

Test credentials are set up with password `password123`:
- Admin: `admin@example.com`
- Company: `company@example.com`
- Student: `student@example.com`

**See `API_TEST_RESULTS.md` for complete test results**

## 📚 Documentation

- `api_documentation.md` - Complete API reference
- `database.md` - Database schema and connection
- `AUTHENTICATION.md` - Authentication system
- `WORKFLOW_TEST.md` - Complete workflow testing
- `COMPLETE_WORKFLOW_SUMMARY.md` - Implementation summary
- `API_TEST_RESULTS.md` - Test results

## 🔄 Complete Workflow

1. **Admin** creates company account → Database
2. **Company** logs in and creates internship → Database
3. **Student** logs in and applies → Database
4. **Company** reviews and offers position → Database update
5. **Student** confirms offer → Database update
6. **Company** finalizes → Complete

**See `WORKFLOW_TEST.md` for step-by-step instructions**

## 🛠️ Development

### Adding New Endpoints

1. Create handler function in appropriate `handlers/*.php`
2. Add routing in `backend/index.php`
3. Add authentication checks
4. Update `api_documentation.md`

### Frontend Integration

Use `internship-portal/js/api.js`:

```javascript
// Login
await api.login(email, password, role);

// Create internship
await api.createInternship(companyId, data);

// Apply
await api.applyForInternship(studentId, internshipId, coverLetter);
```

## 📝 License

Internal project for Sabanci University

## 👥 Contributors

Development Team

---

**Last Updated**: October 26, 2025  
**Status**: Backend Complete, Frontend Integration Ready

