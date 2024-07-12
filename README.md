# Reddit Clone

A Dockerized clone of Reddit, split into Laravel Backend and Vue.js Frontend.

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

4. Once the server is successfully configured, you can test the API endpoints by using swagger documentation [https://localhost/api/documentation](https://localhost/api/documentation).

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

## Frontend Startup

### Docker Setup

1. Build the Docker image:

   ```sh
   docker build . -t reddit-clone-client
   ```

2. Run the Docker container:

   ```sh
   docker run -d -p 8080:80 --name reddit-clone-frontend reddit-clone-client
   ```

### Local Development Server

For development purposes, compile and hot-reload using:

   ```sh
   npm run dev
   ```

## Tech Stack

| Technology   |  Version   |
|:-------------|:----------:|
| Laravel      | v11.9      |
| PHP          |  v8.2.12   |
| Postgres     | v16-alpine |
| Vue          |  v3.4.15   |
| Vite         |  v5.0.11   |

## Team

| Who                                          | What     |
|:-------------------------------------------- |:--------:|
| [@Jakub F](https://github.com/km385)        | Frontend |
| [@Mateusz C](https://github.com/MateuszCzz) | Backend  |

---
