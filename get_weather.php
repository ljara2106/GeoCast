<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['API_KEY'];

header('Content-Type: application/json');

if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $latitude = $_GET['lat'];
    $longitude = $_GET['lon'];

    // Fetch weather data using OpenWeatherMap API
    $weatherResponse = file_get_contents("https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&units=imperial&appid={$apiKey}");
    $weatherData = json_decode($weatherResponse, true);

    // Fetch forecast data using OpenWeatherMap API
    $forecastResponse = file_get_contents("https://api.openweathermap.org/data/2.5/forecast?lat={$latitude}&lon={$longitude}&units=imperial&appid={$apiKey}");
    $forecastData = json_decode($forecastResponse, true);

    // Extract necessary data
    $location = "{$weatherData['name']}, {$weatherData['sys']['country']}";
    $temperature = round($weatherData['main']['temp']);
    $feelsLike = round($weatherData['main']['feels_like']);
    $weatherDescription = $weatherData['weather'][0]['description'];
    $weatherIcon = $weatherData['weather'][0]['icon'];

    // Process forecast data
    $forecastHTML = '<strong>Next 5 Days Average Forecast:</strong><ul class="forecast-list">';
    $dailyAverages = [];

    foreach ($forecastData['list'] as $day) {
        $date = new DateTime('@' . $day['dt']);
        $dayKey = $date->format('l, F j, Y');

        if (!isset($dailyAverages[$dayKey])) {
            $dailyAverages[$dayKey] = [
                'temperatureSum' => 0,
                'count' => 0,
                'weatherDescriptions' => [],
            ];
        }

        $dailyAverages[$dayKey]['temperatureSum'] += $day['main']['temp'];
        $dailyAverages[$dayKey]['count'] += 1;
        $dailyAverages[$dayKey]['weatherDescriptions'][] = $day['weather'][0]['description'];
    }

    foreach ($dailyAverages as $key => $average) {
        $averageTemp = $average['temperatureSum'] / $average['count'];
        $weatherDescriptions = implode(', ', array_unique($average['weatherDescriptions']));

        $forecastHTML .= "<li>{$key}: Average Temp " . round($averageTemp) . "&deg;F, Weather: $weatherDescriptions</li></br>";
    }

    $forecastHTML .= "</ul>";

    // Return JSON response
    echo json_encode([
        'location' => $location,
        'temperature' => $temperature,
        'feelsLike' => $feelsLike,
        'weatherDescription' => $weatherDescription,
        'weatherIcon' => $weatherIcon,
        'forecastHTML' => $forecastHTML
    ]);
} else {
    echo json_encode(['error' => 'Invalid parameters']);
}
?>