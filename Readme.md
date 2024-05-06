# Symfony API Project

This is a Symfony API project built with DDEV for local development. It provides a starting point for building APIs using Symfony.

## Prerequisites

Before you begin, ensure you have the following installed on your local machine:

- [DDEV](https://ddev.readthedocs.io/en/stable/#installation)
- [Composer](https://getcomposer.org/download/)

## Getting Started

To get started with this project, follow these steps:

1. Clone the repository:

   git clone <repository-url> && cd <project-directory>

2. Start the DDEV environment:

   ddev start
   if there is some port conflict , change in config.yaml the router_https_ports

3. Install dependencies using Composer:

   composer install

4. Use my .env

5. Create the Symfony database:

   php bin/console doctrine:database:create

6. Apply migrations:

   php bin/console doctrine:migrations:migrate

7. Launch the project

   ddev launch

8. Importing moves to DB

   append /import/movies to the URL of your project.

9. Check the movies

   Visit /movies in your browser to view the list of movies.

10. for logger check var/dev/log (if have also some console logging)
