# Database Setup

## Install MariaDB

### macOS (using Homebrew):
```bash
brew install mariadb
brew services start mariadb
```

### Set root password (if needed):
```bash
sudo mysql -u root
```

Then run:
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_password';
FLUSH PRIVILEGES;
exit;
```

## Create Database and Tables

Run the SQL schema:
```bash
mysql -u root -p < database/schema.sql
```

Or manually:
```bash
mysql -u root -p
```

Then copy and paste the contents of `database/schema.sql`

## Configure Database Connection

Update `.env` file with your database credentials:
```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=arrissa_data_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

## Test Connection

Visit: http://localhost:8000/settings

You should see the settings page with all configuration options!
