# Deployment Configuration Guide

## Production Environment Setup

Before deploying to production, configure the following environment variables in `.env.production` or hosting platform's environment settings:

### Required (Security Critical)
- **APP_ENV=production** - Disables debug mode
- **APP_DEBUG=false** - Prevents stack traces from exposing to users
- **APP_KEY=base64:xxxxx** - Application encryption key (run `php artisan key:generate` to create)
- **DB_CONNECTION=mysql** - Database driver
- **DB_HOST** - Database server IP/hostname
- **DB_PORT=3306** - MySQL port
- **DB_DATABASE=hitung_gamis** - Production database name
- **DB_USERNAME** - Database user (non-root, limited permissions)
- **DB_PASSWORD** - Secure database password (never commit to repo)

### Recommended (Performance & Security)
- **HTTPS=on** or **FORCE_HTTPS=true** - Force HTTPS at web server level
- **SESSION_SECURE_COOKIES=true** - Only send cookies over HTTPS
- **SESSION_HTTP_ONLY=true** - Prevent JavaScript access to session cookies
- **CACHE_DRIVER=redis** (if available) - Use Redis for sessions/cache
- **QUEUE_CONNECTION=redis** (if available) - Async job processing

### Web Server Configuration (nginx/Apache)
```nginx
# Force HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS with security headers
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    root /var/www/kasiran/public;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Database Preparation
1. Create MySQL user with limited permissions:
   ```sql
   CREATE USER 'kasiran_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON hitung_gamis.* TO 'kasiran_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. Run migrations on production DB:
   ```bash
   php artisan migrate --env=production --database=mysql
   ```

### Important: Never Commit Secrets
- `.env` and `.env.production` must be in `.gitignore`
- Never commit database passwords, API keys, or APP_KEY
- Use environment-specific .env files locally
- Pass production secrets via hosting platform's environment variables

### Verification Before Going Live
1. Verify APP_DEBUG=false (no debug page accessible)
2. Confirm HTTPS is enforced
3. Test login/admin access without exposing errors
4. Check Laravel logs show no sensitive info
5. Verify database connection works with limited user permissions
