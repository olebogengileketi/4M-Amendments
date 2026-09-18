# 19th Episcopal District YPD — Quadrennial Amendment Voting

A check-in → ballot → live results web app for delegates voting Yes / No /
Abstain on proposed amendments to the Quadrennium.

Designed & built for the 19th Episcopal District YPD by **Olebogeng Leketi**,
Historiographer/Statistician.

## 🚀 Quick Start

**For immediate server setup and device connection, see [QUICK_START.md](QUICK_START.md)**

```bash
npm start
```

Then access:
- **Local**: `http://localhost:3000`
- **Network**: `http://YOUR_IP:3000` (shown when server starts)

## Stack
PHP 8 (PDO) · MySQL · Node.js (Express) · Vanilla JS · Bootstrap 5 · Chart.js

## Pages
- `index.php` — delegate check-in (Name, Local Church, Area)
- `ballot.php` — the ballot: one ledger-style card per amendment, Yes/No/Abstain
- `confirmation.php` — post-submission confirmation
- `results.php` — public live results, auto-refreshes every 8s, filterable by Article
- `admin/index.php` — manage amendments (add/edit/reorder/hide/delete),
  toggle voting open/closed and results public/private, live turnout snapshot

## 🔧 Server Features

- **Modern UI**: Card-based design with integrated QR code for mobile access
- **Local Network**: Serves to all devices on same WiFi network
- **Health Monitoring**: Built-in API endpoints for server status
- **PHP Execution**: Fixed - files render properly instead of downloading

## 📖 Documentation

- **[QUICK_START.md](QUICK_START.md)** - Server startup and device connection
- **[xampp-commands.md](xampp-commands.md)** - XAMPP specific commands

## 🛠️ Setup Requirements

1. **Node.js** (v14+) - for the server
2. **PHP** (v8+) - for application logic
3. **MySQL/MariaDB** - for database
4. **Local Network** - WiFi router/modem for device connection

## 🚀 CLI Commands

```bash
# Start the server
npm start

# Stop the server
npm stop

# Check server status
npm run status

# Download external assets
npm run download-assets

# Health check
npm run health
```

Or use the helper scripts:
- `./start.sh` - Automated startup
- `./stop-server.sh` - Stop server on any port
- `./stop-port.sh 3000` - Stop specific port
- `./status.sh` - Check server status
