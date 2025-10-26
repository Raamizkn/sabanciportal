# Path Resolution System

## Overview

This system automatically handles asset path resolution for both localhost and server environments, where the application is not at the root path on the server.

## Problem

- **Localhost**: Assets work with relative paths like `bs5/...` or `../bs5/...`
- **Server**: The app is deployed at `/shadowing/internship-portal/` (not at root)
- Using absolute paths (`/bs5/...`) would break on server
- Relative paths need to work correctly in both environments

## Solution

The `path-resolver.js` script automatically detects the environment and ensures all asset paths resolve correctly.

### How It Works

1. **Detection**: The script checks if the URL contains `/shadowing/internship-portal/`
2. **Base Path**: If on server, extracts `/shadowing/internship-portal/` as the base path
3. **Path Resolution**: Uses browser's native URL resolution to handle relative paths properly
4. **DOM Updates**: Automatically updates all `<link>`, `<script>`, and `<img>` elements

### Example Behavior

**On Localhost:**
```
URL: http://localhost:8000/index.html
Links: <link href="bs5/template/...">
Result: Links work as-is (relative paths)
```

**On Server:**
```
URL: http://pro2-dev.sabanciuniv.edu/shadowing/internship-portal/index.html
Links: <link href="bs5/template/...">
Result: Resolved to /shadowing/internship-portal/bs5/template/...
```

**Nested Pages (Admin/Student/Company):**
```
URL: http://pro2-dev.sabanciuniv.edu/shadowing/internship-portal/admin/admin-dashboard.html
Links: <link href="../bs5/template/...">
Result: Resolved to /shadowing/internship-portal/bs5/template/...
```

## Implementation

### File Structure

```
internship-portal/
├── js/
│   └── path-resolver.js  # Core path resolution logic
├── index.html            # Root page (uses js/path-resolver.js)
├── admin/
│   └── *.html           # Uses ../js/path-resolver.js
├── student/
│   └── *.html           # Uses ../js/path-resolver.js
└── company/
    └── *.html           # Uses ../js/path-resolver.js
```

### Usage

All HTML files include the path resolver script in the `<head>` section, **before** any other assets:

```html
<head>
    <meta charset="utf-8">
    <title>...</title>
    
    <!-- Path Resolver - MUST be loaded first -->
    <script src="js/path-resolver.js"></script>
    <!-- or for nested pages: -->
    <script src="../js/path-resolver.js"></script>
    
    <!-- Other assets below -->
    <link href="bs5/template/..." rel="stylesheet">
    ...
</head>
```

### API

The script exposes these utilities:

- `window.BASE_PATH`: The detected base path (empty string on localhost)
- `window.resolvePath(path)`: Function to manually resolve a path

## Benefits

✅ **Zero Configuration**: Works automatically based on URL
✅ **No Build Step**: Pure JavaScript, no preprocessing needed
✅ **Dynamic**: Handles new elements added to the DOM
✅ **Flexible**: Works with any relative path structure
✅ **Maintainable**: Single source of truth for path resolution

## Testing

To test the system:

1. **Localhost**: 
   ```bash
   cd internship-portal
   python3 -m http.server 8000
   # Visit http://localhost:8000
   ```

2. **Server**: 
   - Deploy to `/shadowing/internship-portal/`
   - Visit http://pro2-dev.sabanciuniv.edu/shadowing/internship-portal/
   - Open browser console to see "Path Resolver initialized" message

## Troubleshooting

**Issue**: Assets not loading on server
- Check browser console for "Path Resolver initialized" message
- Verify base path is correct: `/shadowing/internship-portal/`
- Check network tab to see actual request URLs

**Issue**: Double slashes in paths
- The script handles this automatically
- If persisted, check URL detection logic

**Issue**: Path resolver not loading
- Ensure script is in `<head>` before other assets
- Check file path is correct (`js/path-resolver.js` or `../js/path-resolver.js`)
- Verify file exists at expected location

