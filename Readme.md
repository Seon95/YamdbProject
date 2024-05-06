# Symfony API Project

This is a Symfony API project built with DDEV for local development using MariaDB.

## Getting Started

To get started with this project, follow these steps:

1. **Clone the repository:** `git clone <repository-url> && cd <project-directory>`
2. **Start the DDEV environment:** `ddev start` (If there's a port conflict, change `router_https_ports` in `config.yaml`)
3. **Install dependencies using Composer:** `composer install`
4. **Use the provided .env file**
5. **Create the Symfony database:** `php bin/console doctrine:database:create` and `php bin/console make:migration`
6. **Apply migrations:** `php bin/console doctrine:migrations:migrate`
7. **Importing movies to the database:** Run `php MoviesWithDirector.php` or `php MoviesWithoutDirector.php` (this one is faster)
8. **Launch the project:** `ddev launch`
9. **Change the Database_URL in .env:** Uncomment the `database_url` with `127.0.0.1:50000` and comment out the one with `3306`
10. **Check the movies:** Visit `/movies` in your browser to view the list of movies.
11. **For logging:** Check `var/dev/log` (there is also some console logging)
