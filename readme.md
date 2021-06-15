## Requirements
- [Docker](https://docs.docker.com/install)
- [Docker Compose](https://docs.docker.com/compose/install)

## Setup
1. Clone the repository.
1. Start the containers by running `docker-compose up -d` in the project root.
1. Install the composer packages by running `docker-compose exec laravel composer install`.
1. Connect to the database by running `mysql -h 127.0.0.1 -u laravel -psecret -P 3307`.
1. Create the database `laravel` by running `CREATE DATABASE laravel;` then `exit`.
1. Migrate the tables by running `docker-compose exec laravel php artisan migrate --force`.
1. Access the Laravel instance on `http://localhost:8080` (If there is a "Permission denied" error, run `docker-compose exec laravel chown -R www-data storage public`).

Note that the changes you make to local files will be automatically reflected in the container.

## Persistent database
If you want to make sure that the data in the database persists even if the database container is deleted, add a file named `docker-compose.override.yml` in the project root with the following contents.
```
version: "3.7"

services:
  mysql:
    volumes:
    - mysql:/var/lib/mysql

volumes:
  mysql:
```
Then run the following.
```
docker-compose stop \
  && docker-compose rm -f mysql \
  && docker-compose up -d
```
## Usage

Use these credentials to sign-in:

* email: `admin@yaraku.com`
* password: `a2IWUUT5Ld`

You can see an administration of:

* Books - this is the standard CRUD
    * Add a book to the list.
    * Delete a book from the list.
    * Change an authors name and book title
    * Search for a book by title or author
    * Export the the following in CSV and XML
        * A list with Title and Author
        * A list with only Titles
        * A list with only Authors

## Documentation

You can find full documentation of architecture scaffolding at
[Craftable](https://docs.getcraftable.com/#/craftable).

