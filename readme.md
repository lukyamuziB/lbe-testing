# Lenken Server
Server side for the Lenken mentorship matching application.

## Documentation
Below are the steps required to successfully install and start the lenken-server on a local system:

## Technologies Used
- Php 7
- TypeScript
- Angular V2
- Composer
- Lumen
- Postgres SQL
- Redis
- Peridot

## Getting Started
_*Manual Installation*_
- Install [PHP 7](http://php.net/manual/en/install.php)
- Install [Redis](https://redis.io/download)
- Install [Postresql ](https://www.postgresql.org/download/)
- Install [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- Run ```Composer install``` in your terminal to install dependencies 
- Copy the .env.sample file and rename it to .env
- Fill in the required settings values for the .env settings
- Run ```php artisan migrate``` to create the tables
- Run ```php artisan db:seed``` to seed the tables
- Start the server with ```php -S <hostname>:<port>```




## How to Install/Upgrade Php Mac
Run the following commands.
- ```brew update```
- ```brew install homebrew/php/php70```
- ```export PATH="$(brew --prefix homebrew/php/php70)/bin:$PATH"```

If you encounter errors during install, run the below commands
- ```brew untap josegonzalez/php```
Then re-run the commands above.

## Testing
- [Peridot](http://peridot-php.github.io/)
  - Test files are located at `~/tests`
  - Run ```composer test``` to run tests

## Contribute

[Engineering Playbook](https://github.com/andela/engineering-playbook/)
