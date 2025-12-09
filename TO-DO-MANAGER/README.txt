=== To Do Manager (Advanced) ===

How to Run:

1. Database Setup:
   - Ensure you have run 'upgrade_features.php' if upgrading from previous version.
   - If starting fresh, import SQL structure.

2. File Permissions:
   - Ensure the folder 'uploads/attachments/' exists and is writable by the server.
   - Example: chmod 777 uploads/attachments (on Linux) or ensure modify access on Windows.

3. Configuration:
   - Check 'db.php' settings.

4. Features:
   - Filtering: Use top bar to search and filter tasks.
   - Priority/Status: Edit task to change priority or use quick action button to cycle status.
   - Attachments: Upload files in Create/Edit. Max 5MB.
   - Logs: View history at 'logs.php'.
   - Overdue: Tasks past due date are highlighted red.

5. AJAX:
   - Status toggle and Deletion happen without page reload.
