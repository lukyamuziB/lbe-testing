[![Run in Postman](https://run.pstmn.io/button.svg)](https://app.getpostman.com/run-collection/c83190839f8203b0a76b)

# Lenken Server
Server side for the Lenken mentorship matching application.

## Documentation
Checkout [Apiary](http://docs.lenken.apiary.io/) for Leken API documentation.
Below are the steps required to successfully install and start the lenken-server on a local system:

## Technologies Used
- Php 7
- Composer
- Lumen
- Postgres SQL
- Redis
- Peridot


## Getting Started
_*Manual Installation*_(for mac)
* Clone the application:

      $ git clone https://github.com/andela/lenken-server.git

- Install [PHP 7](http://php.net/manual/en/install.php)

  Run the following commands to Install/Upgrade Php Mac.
    - ```brew update```
    - ```brew install homebrew/php/php70```
    - ```export PATH="$(brew --prefix homebrew/php/php70)/bin:$PATH"```

  If you encounter errors during install, run the below commands
    - ```brew untap josegonzalez/php```

  Then re-run the commands above.
- Install [Redis](https://redis.io/download)
- Install [Postresql ](https://www.postgresql.org/download/)
- Install [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- Install [PHP CodeSniffer](https://github.com/andela/lenken-server/wiki/Installing-PHP-Code-Sniffer-with-Composer)
- Install [PHP Mess Detector](https://github.com/andela/lenken-server/wiki/Installing-PHP-Mess-Detector-with-Composer)
- Run ```Composer install``` in your terminal to install dependencies
- Copy the .env.example file and rename it to .env
- Fill in the required settings values for the .env settings
- Run ```php artisan migrate``` to create the tables
  - When running this command, you can possibly run into a ```cannot find driver``` error
    You can fix that by running ```brew install php70-pdo-pgsql```
- Run ```php artisan db:seed``` to seed the tables
- Run ```cd public``` to navigate to the entry point, `index.php`
- Start the server with ```php -S <localhost>:<port>```

## Testing
- [PHPUnit](https://phpunit.de/)
  - Test files are located at `~/tests`
  - Run ```composer test``` to run tests

## CodeStyle
- To contribute, you MUST adhere to the style guide as provided by:
  - [phpcs](https://github.com/andela/lenken-server/wiki/Installing-PHP-Code-Sniffer-with-Composer)
  - [phpmd](https://github.com/andela/lenken-server/wiki/Installing-PHP-Mess-Detector-with-Composer)

## Contribute

- [Engineering Playbook](https://github.com/andela/engineering-playbook/)
