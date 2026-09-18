const fs = require('fs');
const path = require('path');
const https = require('https');
const http = require('http');

// Create vendor directories
const vendorDir = path.join(__dirname, 'assets', 'vendor');
const cssDir = path.join(vendorDir, 'css');
const jsDir = path.join(vendorDir, 'js');
const fontsDir = path.join(vendorDir, 'fonts');

[cssDir, jsDir, fontsDir].forEach(dir => {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
});

// Assets to download
const assets = [
  // Bootstrap CSS
  {
    url: 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css',
    dest: path.join(cssDir, 'bootstrap.min.css')
  },
  // Bootstrap JS
  {
    url: 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js',
    dest: path.join(jsDir, 'bootstrap.bundle.min.js')
  },
  // Chart.js
  {
    url: 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
    dest: path.join(jsDir, 'chart.umd.min.js')
  },
  // Google Fonts - Fraunces
  {
    url: 'https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&display=swap',
    dest: path.join(cssDir, 'fraunces.css')
  },
  // Google Fonts - Inter
  {
    url: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    dest: path.join(cssDir, 'inter.css')
  }
];

function downloadFile(url, dest) {
  return new Promise((resolve, reject) => {
    const protocol = url.startsWith('https') ? https : http;
    const file = fs.createWriteStream(dest);
    
    protocol.get(url, (response) => {
      if (response.statusCode === 302 || response.statusCode === 301) {
        // Handle redirects
        downloadFile(response.headers.location, dest)
          .then(resolve)
          .catch(reject);
        return;
      }
      
      if (response.statusCode !== 200) {
        reject(new Error(`Failed to download ${url}: ${response.statusCode}`));
        return;
      }
      
      response.pipe(file);
      
      file.on('finish', () => {
        file.close();
        console.log(`✓ Downloaded: ${path.basename(dest)}`);
        resolve();
      });
    }).on('error', (err) => {
      fs.unlink(dest, () => {});
      reject(err);
    });
  });
}

async function downloadAll() {
  console.log('Downloading external dependencies for local use...\n');
  
  for (const asset of assets) {
    try {
      await downloadFile(asset.url, asset.dest);
    } catch (error) {
      console.error(`✗ Failed to download ${asset.url}:`, error.message);
    }
  }
  
  console.log('\nDownload complete! External assets are now cached locally.');
  console.log('You can now run the server with: npm start');
}

downloadAll().catch(console.error);