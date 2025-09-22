# QR Code Generator

### Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```

3. Import the database schema:
   ```bash
   mysql -u somedbuser -p < database.sql
   ```

4. Set up environment:
   ```bash
   php setup.php
   ```

5. Configure your environment by editing `.env` file:
   - Update database credentials
   - Set your BASE_URL and SHORT_DOMAIN
   - Configure other settings as needed


### Features

- Generate QR codes from URLs
- Custom URL shortener with redirect page
- Logo support (preset and custom upload)
- Customizable QR code colors
- Advertisement page before redirect
- Environment-based configuration

### URL Structure

- Main page: `http://your-domain/`
- Short URLs: `http://your-domain/r/ABC123`
- Direct access: `http://your-domain/r.php?code=ABC123`

### Configuration

Edit `.env` file to configure:
- Database connection
- Base URLs and domains
- Advertisement display time
- Other application settings