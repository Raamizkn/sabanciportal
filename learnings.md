# Web Server Debugging Learnings

This document outlines the key learnings from the debugging session regarding asset loading issues.

## 1. The Document Root is Critical

The primary issue was a mismatch between the web server's document root and the file paths used in the HTML.

- **Problem**: The server was initially started in the project's root directory (`sabanciportal-1/`), but the HTML files used absolute paths like `/assets/css/style.css`. These paths assumed the server's root was the `internship-portal/` directory.
- **Resolution**: The server must be started from the directory that the application's paths are relative to. In this case, running the server from within the `internship-portal/` directory solved the issue.
- **Lesson**: When assets fail to load (404 errors), always verify that the server's document root is correctly configured to match the expectations of the application's file paths.

## 2. Pay Attention to User Context

The user's feedback was a crucial turning point in the debugging process.

- **Hint**: The user mentioned, "you started it differently before on just 8000 and using python."
- **Insight**: This indicated that a simpler solution had worked in the past and that my more complex approach (using PHP from the root) was likely incorrect. It prompted a re-evaluation of the server configuration.
- **Lesson**: User feedback and historical context are valuable sources of information that can significantly speed up troubleshooting.

## 3. Process Management

It's important to manage server processes effectively to avoid conflicts.

- **Problem**: Running a new server on the same port as an existing one will fail.
- **Resolution**: Ensure any existing server process is stopped (`kill <PID>`) before starting a new one on the same port. Using a different port (e.g., 8080) is also a quick way to rule out port conflicts.
- **Lesson**: Always clean up old processes to ensure a clean environment for new commands.
