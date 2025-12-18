# XAMPP Installation Guide

## Prerequisites
- XAMPP with PHP 8.0 or higher
- Windows OS

## Installation Steps

### 1. Extract Files
1. Extract `arrissa-data-api-xampp.zip` to `C:\xampp\htdocs\`
2. The folder should be: `C:\xampp\htdocs\arrissa-data-api\`

### 2. Configure Apache (Optional - for clean URLs)
If you want to use the root URL without `/public`:

1. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Add this virtual host:

```apache
<VirtualHost *:80>
    ServerName arrissa.local
    DocumentRoot "C:/xampp/htdocs/arrissa-data-api"
    <Directory "C:/xampp/htdocs/arrissa-data-api">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Edit `C:\Windows\System32\drivers\etc\hosts` (Run as Administrator)
4. Add: `127.0.0.1 arrissa.local`
5. Restart Apache

### 3. Set Permissions
Ensure these folders are writable:
- `database/` (for SQLite database)
- `market-data-api-v1/queue/` (for API queue files)

On Windows, right-click folders → Properties → Security → Edit → Add write permissions

### 4. Update Base URL
1. Start XAMPP Apache
2. Access: `http://localhost/arrissa-data-api` or `http://arrissa.local`
3. Login with default credentials:
   - Username: `admin`
   - Password: `password`
4. Go to Settings
5. Update "App Base URL" to match your XAMPP setup:
   - `http://localhost/arrissa-data-api` (if using default)
   - `http://arrissa.local` (if using virtual host)

### 5. API Configuration
The API is located at:
- `http://localhost/arrissa-data-api/market-data-api-v1/market-data-api.php`
- Or `http://arrissa.local/market-data-api-v1/market-data-api.php`

Update your MT5 EA to point to this URL.

### 6. Queue Folder
Make sure the queue folder exists and is writable:
```
C:\xampp\htdocs\arrissa-data-api\market-data-api-v1\queue\
```

If it doesn't exist, create it manually.

## Default Login Credentials
- **Username:** admin
- **Password:** password

**Important:** Change the password immediately after first login!

## Troubleshooting

### Database Errors
- Check if `database/app.db` exists
- Ensure `database/` folder has write permissions
- Run `database/init.php` if database is missing

### API Not Responding
- Verify `market-data-api-v1/queue/` folder exists
- Check folder has write permissions
- Check PHP error logs in `C:\xampp\php\logs\`

### Queue Files
- Old queue files are automatically cleaned up after 60 seconds
- If queue folder doesn't exist, create it manually

## URLs to Remember

### Dashboard
- `http://localhost/arrissa-data-api/`
- `http://arrissa.local/` (with vhost)

### API Endpoint
- `http://localhost/arrissa-data-api/market-data-api-v1/market-data-api.php`
- `http://arrissa.local/market-data-api-v1/market-data-api.php` (with vhost)

### Settings Page
- Update API key
- Update base URL
- Change password

## Features
- ✅ SQLite database (no MySQL needed)
- ✅ Session-based authentication
- ✅ Dark/Light theme toggle
- ✅ API key management
- ✅ Market Data API with queueing
- ✅ MT5 integration ready
- ✅ Comprehensive API documentation

## Support
For issues, check:
1. PHP error logs
2. Apache error logs
3. Browser console for JavaScript errors
