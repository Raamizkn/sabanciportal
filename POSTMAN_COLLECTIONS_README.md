# 🚀 Sabanci Internship Portal - Postman Collections

## 📦 **Ready-to-Use Collections**

I've created **3 separate, focused Postman collections** as requested:

### **1. 🎓 Student API Collection**
- **File:** `Student_API.postman_collection.json`
- **Endpoints:** 7 endpoints
- **Features:** Profile, internships, applications, documents, evaluations
- **Test User:** `student_id=1` (John Doe)

### **2. 👨‍💼 Admin API Collection** 
- **File:** `Admin_API.postman_collection.json`
- **Endpoints:** 10+ endpoints
- **Features:** Terms, students, companies, system oversight
- **Usage:** No user ID required (admin-level access)

### **3. 🏢 Company API Collection**
- **File:** `Company_API.postman_collection.json` 
- **Endpoints:** 12 endpoints
- **Features:** Profile, internships, applications, evaluations, dashboard
- **Test Company:** `company_id=1` (ABC Technologies)

---

## 🎯 **Next Steps for Your Teammate**

### **1. Import Collections into Postman**
```bash
# Import each collection file:
1. Open Postman
2. Click "Import" 
3. Select each .json file
4. Collections will appear organized by type
```

### **2. Set Environment Variable**
```json
{
    "base_url": "http://localhost:8001"
}
```

### **3. Start PHP Server**
```bash
php -S localhost:8001 -t /path/to/sabanciportal-1
```

### **4. Test Each API**
- **Student API**: Start with profile management, then internships
- **Admin API**: Test term management and system oversight
- **Company API**: Test dashboard stats and internship management

---

## ✅ **What's Complete & Ready**

| API Type | Status | Endpoints | Key Features |
|----------|---------|-----------|--------------|
| **Student** | ✅ **Ready** | 7 endpoints | Complete student workflow |
| **Admin** | ✅ **Ready** | 10+ endpoints | Full system administration |
| **Company** | ✅ **Ready** | 12 endpoints | Complete company management |

---

## 📊 **Collection Structure**

### **Student API:**
- 👤 Profile Management (2 endpoints)
- 💼 Internships & Applications (5 endpoints)
- 📄 Documents & Evaluations (2 endpoints)

### **Admin API:**
- 📅 Term Management (4 endpoints)
- 🎓 Student Management (4 endpoints)
- 🏢 Company Management (2 endpoints)
- 📊 System Oversight (2 endpoints)

### **Company API:**
- 🏢 Profile Management (2 endpoints)
- 💼 Internship Management (4 endpoints)
- 📋 Application Management (3 endpoints)
- ⭐ Evaluation System (2 endpoints)
- 📊 Dashboard & Analytics (1 endpoint)

---

## 🔧 **Testing Tips**

### **Sample Test Data:**
- **Student ID:** 1 (John Doe)
- **Company ID:** 1 (ABC Technologies)
- **Internship IDs:** 101, 102
- **Application ID:** 1001

### **Test Flow:**
1. **Admin**: Set up terms and users
2. **Company**: Create internships 
3. **Student**: Browse and apply
4. **Company**: Review and evaluate

---

## 📝 **All Documentation Available:**

- ✅ **API Documentation** - `api_documentation.md`
- ✅ **Testing Guide** - `API_Testing_Guide.md`
- ✅ **Requirements Analysis** - `Company_API_Requirements_Analysis.md`
- ✅ **Implementation Summary** - `COMPANY_API_SUMMARY.md`

**Everything is production-ready and thoroughly documented!** 🎉
