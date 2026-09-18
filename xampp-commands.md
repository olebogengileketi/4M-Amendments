# XAMPP CLI Commands (macOS)

## Start Services

```bash
# Start Apache
sudo /Applications/XAMPP/xamppfiles/xampp startapache

# Start MySQL
sudo /Applications/XAMPP/xamppfiles/xampp startmysql

# Start both Apache + MySQL together
sudo /Applications/XAMPP/xamppfiles/xampp start
```

## Stop Services

```bash
# Stop Apache
sudo /Applications/XAMPP/xamppfiles/xampp stopapache

# Stop MySQL
sudo /Applications/XAMPP/xamppfiles/xampp stopmysql

# Stop both
sudo /Applications/XAMPP/xamppfiles/xampp stop
```

## Status

```bash
# Check what's running
sudo /Applications/XAMPP/xamppfiles/xampp status
```

> **Note:** You need `sudo` because Apache listens on port 80 and MySQL on port 3306, which require root privileges. If prompted, enter your macOS user password.
