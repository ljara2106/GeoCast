## Project Description

This project provides a weather forecast application that fetches and displays the current weather and a 5-day forecast based on the user's location. The application uses the OpenWeatherMap API to retrieve weather data and displays it in a user-friendly format.

### Files Overview

#### `get_weather.php`

This PHP script is responsible for fetching weather data based on the latitude and longitude provided as query parameters. It performs the following tasks:
- Retrieves the latitude and longitude from the query parameters.
- Uses the OpenWeatherMap API to fetch the current weather and 5-day forecast data.
- Processes the forecast data to calculate daily averages.
- Returns the weather data and forecast in JSON format.

#### `index.php`

This PHP file serves as the main entry point for the application. It performs the following tasks:
- Prevents caching of the page to ensure fresh data is always fetched.
- Retrieves the visitor's IP address and logs it to a file (`ip_log.txt`).
- Uses the IP address to fetch location information (latitude, longitude, and timezone) from an external service (ipinfo.io).
- Uses the latitude and longitude to fetch the current weather and 5-day forecast data from the OpenWeatherMap API.
- Processes the forecast data to calculate daily averages and formats it for display.
- Contains the HTML structure and JavaScript to display the weather data on the webpage.
- Uses geolocation to fetch weather data based on the user's current location if available, otherwise falls back to IP-based location data.

### How to Use

1. Clone the repository to your local machine.
2. Ensure you have PHP installed on your server.
3. Require `vlucas/phpdotenv` using Composer:
    ```sh
    composer require vlucas/phpdotenv
    ```
4. Create a `.env` file in the root directory of the project and add your OpenWeatherMap API key:
    ```env
    API_KEY=your_openweathermap_api_key
    ```         
5. Open `index.php` in your web browser to view the weather forecast application.

### Dependencies

- PHP
- Composer (composer require vlucas/phpdotenv)
- OpenWeatherMap API
- ipinfo.io API (for IP-based location data)

