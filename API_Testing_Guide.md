# 🧪 API Testing Guide

## Quick Start

### 1. Import Postman Collection
```bash
# Import the complete collection
File: Sabanci_Internship_Portal_API.postman_collection.json
```

### 2. Set Environment Variables
```json
{
    "base_url": "http://localhost:8001"
}
```

### 3. Start PHP Server
```bash
php -S localhost:8001 -t /path/to/sabanciportal-1
```

## 🎯 Test Scenarios

### Scenario 1: Company Workflow
1. **Get Company Profile** - `GET /backend/api/company.php?company_id=1&action=get_profile`
2. **Get Company Internships** - `GET /backend/api/company.php?company_id=1&action=get_internships`
3. **Create New Internship** - `POST /backend/api/company.php?company_id=1&action=create_internship`
4. **Get Applications** - `GET /backend/api/company.php?company_id=1&action=get_applications`
5. **Update Application Status** - `POST /backend/api/company.php?company_id=1&action=update_application_status&application_id=1001`

### Scenario 2: Student Workflow
1. **Get Student Profile** - `GET /backend/api/student.php?student_id=1&action=get_profile_info`
2. **Browse Internships** - `GET /backend/api/student.php?student_id=1&action=get_internships`
3. **Apply for Internship** - `POST /backend/api/student.php?student_id=1&action=apply`
4. **Check Applications** - `GET /backend/api/student.php?student_id=1&action=get_student_applications`

### Scenario 3: Admin Workflow
1. **Get System Stats** - `GET /backend/api/admin.php?action=get_system_stats`
2. **Manage Terms** - `GET /backend/api/admin.php?action=get_terms`
3. **Add Students** - `POST /backend/api/admin.php?action=add_student`
4. **View All Applications** - `GET /backend/api/admin.php?action=get_all_applications`

## 📊 Expected Responses

### Success Response
```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": { /* response data */ }
}
```

### Error Response
```json
{
    "error": "Error description",
    "details": "Additional information"
}
```

## 🔍 Troubleshooting

### Common Issues
1. **CORS Errors** - Enable CORS headers (already included)
2. **PHP Not Found** - Install PHP or add to PATH
3. **404 Errors** - Check server is running on correct port
4. **Invalid JSON** - Validate request body format

### Debug Steps
1. Test base endpoints without parameters
2. Check server logs for errors
3. Validate JSON syntax in request bodies
4. Verify required parameters are included

## 📝 Sample Requests

### Create Internship (Company)
```json
POST /backend/api/company.php?company_id=1&action=create_internship
{
    "title": "Backend Developer Intern",
    "description": "Work with our PHP/MySQL team",
    "location": "Istanbul, Turkey",
    "requirements": "PHP, MySQL, Git",
    "salary": "3800 TL/month",
    "type": "Full-time",
    "application_deadline": "2024-12-31"
}
```

### Apply for Internship (Student)
```json
POST /backend/api/student.php?student_id=1&action=apply
{
    "internship_id": 101,
    "cover_letter": "I'm very interested in this position..."
}
```

### Update Application Status (Company)
```json
POST /backend/api/company.php?company_id=1&action=update_application_status&application_id=1001
{
    "status": "Interview Scheduled"
}
```

## ✅ Validation Checklist

- [ ] All 27+ endpoints respond correctly
- [ ] Error handling works for invalid data
- [ ] JSON responses are properly formatted
- [ ] CORS headers are present
- [ ] Required parameters are validated
- [ ] Mock data relationships work correctly
- [ ] Postman collection imports successfully
- [ ] Environment variables are set correctly
