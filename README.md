# Premier League Project

A Drupal 10 based project for the Premier League website.

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
git clone git@github.com:JenyaMelnik/premier-league.git
cd premier-league
```

2. Install PHP dependencies:

```bash
composer install
```

3. In the root of the site create a `.lando.yml` file. You can configure it yourself, or you can copy the settings below:

```yaml
name: premier-league
recipe: drupal10
config:
  webroot: web
proxy:
  appserver:
    - premier-league.lndo.site
services:
  appserver:
    build_as_root:
      - composer global require drush/drush
      - apt-get update -y
      - apt-get install -y curl unzip
      - curl -sS https://getcomposer.org/installer | php
      - mv composer.phar /usr/local/bin/composer
      - chmod +x /usr/local/bin/composer
```

4. Start the Lando environment:

```bash
lando start
```

5. Open the site in your browser, if you copied Lando's settings, it should be: https://premier-league.lndo.site/

6. Setting up the site. At the 'set up database' step:

- **Database name**: `drupal10`
- **Database username**: `drupal10`
- **Database password**: `drupal10`
- Go to **Advanced options → Host** and set it to `database`

7. In `web/sites/default/settings.php` file, change the path to the configuration directory:

```php
$settings['config_sync_directory'] = '../config/sync';
```

8. Synchronize the UUID:

```bash
lando drush cset system.site uuid bb2391fc-c312-460f-8284-c9a3a919bebf
```

9. Import configuration:

```bash
lando drush cim
```

If an error related to the `shortcut` or `shortcut_set` entities appears, run:

```bash
lando drush ev "\Drupal::entityTypeManager()->getStorage('shortcut')->delete(\Drupal::entityTypeManager()->getStorage('shortcut')->loadMultiple());"
lando drush ev "\Drupal::entityTypeManager()->getStorage('shortcut_set')->delete(\Drupal::entityTypeManager()->getStorage('shortcut_set')->loadMultiple());"
```

Then import configuration again:

```bash
lando drush cim
```

Clear the cache:

```bash
lando drush cr
```

Update the database:

```bash
lando drush updatedb
```

10. Now you can use the site.

### Useful Lando Commands

- `lando start` - Start the environment
- `lando stop` - Stop the environment
- `lando drush` - Run Drush commands
- `lando mysql` - Access the database

### Project Structure

- `/web` - Drupal web root
- `/web/modules/custom` - Custom modules
- `/web/themes/custom` - Custom themes
- `/config` - Configuration management directory

## Support

For issues and support, please create an issue in the GitHub repository.

## Contributing

1. Create a new branch for your feature
2. Make your changes
3. Submit a pull request
