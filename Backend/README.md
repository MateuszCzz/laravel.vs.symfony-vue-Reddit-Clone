# Reddit Clone Backend

Welcome to the Reddit Clone Backend project! This is a Dockerized REST API built with Laravel.

## Backend Startup

1. Prepare a `.env` file based on the provided example.
2. Build Docker images for the database and PHP server using the following command:

    ```sh
    bash vendor/bin/sail build --no-cache
    ```
   >⚠️ **Warning:** Rebuilding images can result in dangling images. Remember to clean them off after each use.
   >
   >⚠️ **Warning:** Git can break docker containers with converting line ending from windows's LF to CRLF. 
   To disable CRLF conversion globally, run `git config --global core.autocrlf false`, and clone again.

3. Start up the containers with:

    ```sh
    bash vendor/bin/sail up -d
    ```
4. Prepare database with migration:

    ```sh
    bash vendor/bin/sail artisan migrate
    ```

4. Once the server is successfully configured, you can test the API endpoints by using swagger documentation [http://127.0.0.1:8000/api/documentation](http://127.0.0.1:8000/api/documentation).

### Local PHP Server

If you prefer to use a local PHP server instead of the fully dockerized variant:

1. Install necessary dependencies with Composer:

    ```sh
    composer install
    ```
      >⚠️ **Warning:** Remember to change host in `.env` from pgsql to localhost.
 
2. Start the database docker image.
3. Start the PHP server:

    ```sh
    php artisan serve
    ```
4. Run migrations:

    ```sh
    php artisan migrate
    ```

## Managing the Database

- To log in to the PostgreSQL database:

  ```sh
  psql -U <username> -d <database_name>
  ```

- To check the database structure:

  ```sh
  \dt
  ```
  or

  ```sh
  SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' );
  ```

- To check columns of a given table:

  ```sh
  \d <table_name>
  ```

## Tech Stack

| Technology   | Version   |
| ------------ | --------- |
| Laravel      | v11.9     |
| PHP          | v8.2.12   |
| PostgreSQL   | v15       |

## Team

| Who                                         | What      |
| ------------------------------------------- | --------- |
| [@Jakub F](https://github.com/km385)        | Frontend  |
| [@Mateusz C](https://github.com/MateuszCzz) | Backend   |

---