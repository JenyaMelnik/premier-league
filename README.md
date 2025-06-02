# Premier League Project

A Drupal 10 based project for Premier League website.

## Prerequisites

Before you begin, ensure you have the following installed:
- [Git](https://git-scm.com/)
- [Composer](https://getcomposer.org/) (PHP package manager)
- [Lando](https://lando.dev/) (Local development environment)
- PHP 8.1 or higher
- MySQL 5.7.8 or higher

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd premier-league
```

2. Install PHP dependencies:
```bash
composer install
```

3. Start the Lando environment:
```bash
lando start
```

This will set up:
- A Drupal 10 site at http://premier-league.lndo.site
- PHP 8.1
- MySQL database
- Drush

4. Install Drupal using Drush:
```bash
lando drush site:install --db-url=mysql://drupal10:drupal10@database/drupal10 -y
```

5. Create Team Content Types:
   - Log in to your Drupal admin panel
   - Go to Structure > Content types > Add content type
   - Create a new content type called "Team"
   - Add the following fields:
     - Name (Text field - already provided by default title)
     - Team Strength (Number field - integer)
   - Save the content type
   - Create at least 4 teams with their respective names and strength ratings

## Configuration

### Local Settings

1. Copy the example settings file:
```bash
cp web/sites/example.settings.local.php web/sites/default/settings.local.php
```

2. Add the following to `web/sites/default/settings.php`:
```php
// Include local development settings
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

// Set configuration sync directory
$settings['config_sync_directory'] = '../config/sync';
```

The `settings.local.php` inclusion allows you to have environment-specific settings for local development that won't be committed to the repository. This is where you can put development-friendly settings like:
- Error display settings
- Debug mode configurations
- Local database credentials
- Development-specific modules

The `config_sync_directory` setting is crucial for configuration management. It tells Drupal where to store and read configuration files, keeping them:
- Outside the web root for better security
- In a version-controlled location
- Accessible for configuration import/export operations

## Development

### Useful Lando Commands

- `lando start` - Start the environment
- `lando stop` - Stop the environment
- `lando drush` - Run Drush commands
- `lando composer` - Run Composer commands
- `lando mysql` - Access the database

### Project Structure

- `/web` - Drupal web root
- `/web/modules/custom` - Custom modules
- `/web/themes/custom` - Custom themes
- `/config` - Configuration management directory

## Troubleshooting

### Common Issues

1. If you get permission errors:
```bash
lando drush cr
```

2. If the site is not accessible:
- Check if Lando is running: `lando list`
- Verify the URL: http://premier-league.lndo.site
- Clear Drupal cache: `lando drush cr`

### Support

For issues and support, please create an issue in the GitHub repository.

## Contributing

1. Create a new branch for your feature
2. Make your changes
3. Submit a pull request
