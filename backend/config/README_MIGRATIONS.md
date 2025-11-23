# Database Migrations Guide

## Application Status Migration

### Overview
The `applications` table ENUM already has all required status values. This migration only normalizes existing data to match our finalized workflow. **No ALTER privileges required.**

### ⚠️ Permission Issue?
If you get an error like `ALTER command denied`, use the **no-alter** version of the migration scripts instead. They only update data, not the schema.

### Status Workflow
Our finalized application workflow uses these statuses:

1. **Pending Review** (Database: `Pending`) - Initial status when student applies
2. **Accepted** (Database: `Accepted`) - Company accepts the application
3. **Confirmed** (Database: `Confirmed_By_Student`) - Student confirms acceptance
4. **Finalized** (Database: `Approved_By_Company`) - Company finalizes the placement
5. **Rejected** (Database: `Rejected`) - Company rejects the application
6. **Withdrawn** (Database: `Withdrawn`) - Student withdraws the application

### Running the Migration

#### Option 1: Using SQL Script (phpMyAdmin or MySQL CLI) - **No ALTER Required**

1. Connect to your database (`shadowing`)
2. Run the SQL script:
   ```sql
   -- Copy and paste the contents of migrate_application_statuses_no_alter.sql
   ```
   This version only updates data, no schema changes needed.

#### Option 2: Using PHP Script (Recommended) - **No ALTER Required**

```bash
cd backend/config
php migrate_application_statuses_no_alter.php
```

#### Option 3: Full Migration (Requires ALTER Privileges)

If you have ALTER privileges and want to ensure the ENUM is exactly as specified:
```bash
php migrate_application_statuses.php
```
Or use `migrate_application_statuses.sql` in phpMyAdmin.

### What the Migration Does

1. **Checks current status values** - Shows what statuses exist in your database
2. **Updates ENUM definition** - Ensures all required status values are available
3. **Normalizes existing data** - Maps any inconsistent status values to standard ones
4. **Verifies changes** - Shows final status distribution

### Status Mapping

| Database Value | Display Name | Description |
|---------------|--------------|-------------|
| `Pending` | Pending Review | Initial status when student applies |
| `Accepted` | Accepted | Company accepts the application |
| `Confirmed_By_Student` | Confirmed | Student confirms acceptance |
| `Approved_By_Company` | Finalized | Company finalizes the placement |
| `Rejected` | Rejected | Company rejects the application |
| `Withdrawn` | Withdrawn | Student withdraws the application |

### Important Notes

- **Backup First**: Always backup your database before running migrations
- **Test Environment**: Test migrations on a copy of production data first
- **No Data Loss**: This migration only modifies the ENUM definition and normalizes values - it doesn't delete any data
- **Status Normalization**: The frontend code handles mapping database values to display names

### Verifying the Migration

After running the migration, verify:

```sql
-- Check status distribution
SELECT status, COUNT(*) as count 
FROM applications 
GROUP BY status 
ORDER BY count DESC;

-- Check ENUM definition
SHOW COLUMNS FROM applications LIKE 'status';
```

### Troubleshooting

**Error: "Data truncated for column 'status'"**
- This means you have a status value that's not in the ENUM
- Check what values exist: `SELECT DISTINCT status FROM applications;`
- Add missing values to the ENUM or update the data first

**Error: "Cannot modify ENUM"**
- MySQL requires the full ENUM definition when modifying
- Make sure you include ALL values you want to keep
- Consider using a temporary VARCHAR column if needed

### Rollback

If you need to rollback, you can restore from backup or manually update the ENUM:

```sql
ALTER TABLE applications 
MODIFY COLUMN status ENUM(
    'Pending',
    'Under Review',
    'Shortlisted',
    'Interview Scheduled',
    'Offered',
    'Rejected',
    'Accepted',
    'Declined',
    'Withdrawn',
    'Approved_By_Company',
    'Confirmed_By_Student',
    'Rejected_By_Company'
) DEFAULT 'Pending';
```

