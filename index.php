<?php
// prevent cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$apiKey = 'a27bba4b8eb0638c659cc4f1e1bb4f52';


// Function to get the visitor's IP address
function getVisitorIP()
{
    // If you're testing on localhost, return a placeholder or handle it as needed
    if ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
        return 'localhost';
    }

    // Try to get the visitor's IP address from the X-Forwarded-For header
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim(end($ipAddresses));
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    return '';
}

// Fetch visitor's IP address
$visitorIP = getVisitorIP();

// Log the IP address along with the current date and time
$logData = date('Y-m-d H:i:s') . " - IP: $visitorIP\n";
$logFile = 'ip_log.txt';

// Check if the log file exists, and create it if not
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0644); // Set appropriate permissions
}

file_put_contents($logFile, $logData, FILE_APPEND);


// If testing on localhost, use a placeholder IP or handle it as needed
if ($visitorIP == 'localhost') {
    // Handle localhost case (e.g., use a default public IP for testing)
    $visitorIP = '8.8.8.8';
}

// Fetch IP information using an external service (ipinfo.io in this case)
$ipInfoResponse = file_get_contents("https://ipinfo.io/{$visitorIP}/json");
$ipInfoData = json_decode($ipInfoResponse, true);

// Ensure the IP address is available
$ipAddress = isset($ipInfoData['ip']) ? $ipInfoData['ip'] : '';

// Extract latitude and longitude from loc
$loc = explode(',', $ipInfoData['loc'] ?? '');
$latitude = $loc[0] ?? '';
$longitude = $loc[1] ?? '';

// Validate latitude and longitude
if (!is_numeric($latitude) || !is_numeric($longitude)) {
    // Handle the case where latitude or longitude is not valid
    $errorMessage = 'Unable to fetch valid location data.';
} else {
    // Fetch weather data using OpenWeatherMap API based on visitor's IP
    $weatherResponse = file_get_contents("https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&units=imperial&appid={$apiKey}");
    $weatherData = json_decode($weatherResponse, true);

    // Fetch forecast data using OpenWeatherMap API based on visitor's IP
    $forecastResponse = file_get_contents("https://api.openweathermap.org/data/2.5/forecast?lat={$latitude}&lon={$longitude}&units=imperial&appid={$apiKey}");
    $forecastData = json_decode($forecastResponse, true);

    $timezone = $ipInfoData['timezone'] ?? 'GMT';

    // Get the current date and time based on the timezone
    $currentDate = new DateTime();
    $currentDate->setTimeZone(new DateTimeZone($timezone));

    $options = [
        'weekday' => 'long',
        'year' => 'numeric',
        'month' => 'long',
        'day' => 'numeric',
        'hour' => 'numeric',
        'minute' => 'numeric',
        'second' => 'numeric',
        'hour12' => true,
        'timeZone' => $timezone,
    ];

    $currentDateTimeString = $currentDate->format('l, F j, Y g:i:s A');

    // Display weather
    $location = "{$ipInfoData['city']}, {$ipInfoData['region']}, {$ipInfoData['country']}";
    $temperature = round($weatherData['main']['temp']);
    $highestTemp = round($weatherData['main']['temp_max']);
    $lowestTemp = round($weatherData['main']['temp_min']);
    $feelsLike = round($weatherData['main']['feels_like']);
    $weatherDescription = $weatherData['weather'][0]['description'];
    $errorMessage = "";
    $dateTime = " $currentDateTimeString";

    // Display average forecast data for the next 5 days, skipping today's date
    if (isset($forecastData['list']) && count($forecastData['list']) > 0) {
        $forecastHTML = '<strong>Next 5 Days Average Forecast:</strong><ul class="forecast-list">';
        $dailyAverages = [];

        foreach ($forecastData['list'] as $day) {
            $date = new DateTime('@' . $day['dt']);
            $date->setTimeZone(new DateTimeZone($timezone));

            if ($date->format('Y-m-d') !== $currentDate->format('Y-m-d')) {
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
        }

        foreach ($dailyAverages as $key => $average) {
            $averageTemp = $average['temperatureSum'] / $average['count'];
            $weatherDescriptions = implode(', ', array_unique($average['weatherDescriptions']));

            $forecastHTML .= "<li>{$key}: Average Temp " . round($averageTemp) . "&deg;F, Weather: $weatherDescriptions</li></br>";
        }

        $forecastHTML .= "</ul>";
    } else {
        $forecastHTML = "No forecast data available.";
    }
}

// Function to get weather data based on latitude and longitude
function getWeatherData($latitude, $longitude, $apiKey) {
    $weatherResponse = file_get_contents("https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&units=imperial&appid={$apiKey}");
    $weatherData = json_decode($weatherResponse, true);

    $forecastResponse = file_get_contents("https://api.openweathermap.org/data/2.5/forecast?lat={$latitude}&lon={$longitude}&units=imperial&appid={$apiKey}");
    $forecastData = json_decode($forecastResponse, true);

    return [$weatherData, $forecastData];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Weather Forecast based on your IP address location. Check the current weather and the next 5 days' average forecast.">
    <meta name="keywords" content="weather, forecast, current weather, 5-day forecast">
    <meta name="author" content="Luis Jara">

    <title>Weather Forecast - Check Current Weather and 5-Day Forecast</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            /* Use Quicksand font */
            text-align: center;
            margin: 20px;
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        #weather-icon {
            vertical-align: middle;
            margin-left: 1px;
            width: 30px;
            height: 30px;
        }

        #weather-info,
        #forecast,
        #datetime {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        #error-message {
            color: #e74c3c;
            font-weight: bold;
            margin-top: 10px;
        }

        #datetime strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            color: #3498db;
        }

        h1 {
            color: #3498db;
            margin-bottom: 10px;
        }

        h2 {
            color: #2ecc71;
            margin-bottom: 10px;
        }

        @media (max-width: 600px) {

            #weather-info,
            #forecast,
            #datetime {
                max-width: 100%;
            }
        }

        /* Hide the JavaScript note if JavaScript is enabled */
        .js-enabled #js-note {
            display: none;
            color: #e74c3c;
        }
    </style>

</head>

<body>
    <!-- Note for JavaScript -->
    <div id="js-note">
        This page requires JavaScript to display its content. Please enable JavaScript in your browser.
    </div>

    <div id="ip-info" style="display: none;">
        <strong>Your IP: </strong> <span id="ip-address"><?php echo $ipInfoData['ip']; ?></span>
    </div>
    <br>

    <div id="datetime" class="weather-data">
        <strong>Current Date and Time:</strong> <span id="current-datetime"><?php echo $dateTime; ?></span>
    </div>

    <div id="weather-info" class="weather-data">
        <h1 id="location">Loading location...</h1>
        <br>
        <div><strong>Temperature:</strong> <span id="temperature">--</span>&deg;F</div>
        <br>
        <div><strong>Feels Like:</strong> <span id="feels-like">--</span>&deg;F</div>
        <br>
        <div>
            <strong>Weather:</strong> <span id="weather-description">Loading...</span>
            <img id="weather-icon" src="" alt="Weather Icon" style="display: none;">
        </div>
    </div>

    <div id="forecast" class="weather-data">
        <h2>Next 5 Days Average Forecast</h2>
        <div id="forecast-content">Loading forecast...</div>
    </div>

    <div id="error-message"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.body.classList.add("js-enabled");

            function updateWeatherDisplay(data, isIpBased) {
                document.getElementById('location').textContent = data.location;
                document.getElementById('temperature').textContent = data.temperature;
                document.getElementById('feels-like').textContent = data.feelsLike;
                document.getElementById('weather-description').textContent = data.weatherDescription;
                
                const weatherIcon = document.getElementById('weather-icon');
                weatherIcon.src = `http://openweathermap.org/img/w/${data.weatherIcon}.png`;
                weatherIcon.style.display = 'inline';

                document.getElementById('forecast-content').innerHTML = data.forecastHTML;

                // Show IP info only if using IP-based data
                document.getElementById('ip-info').style.display = isIpBased ? 'block' : 'none';
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        fetch(`get_weather.php?lat=${latitude}&lon=${longitude}`)
                            .then(response => response.json())
                            .then(data => {
                                updateWeatherDisplay(data, false);
                            })
                            .catch(error => {
                                console.error('Error fetching weather data:', error);
                                // Fall back to IP-based location data
                                updateWeatherDisplay(<?php echo json_encode([
                                    'location' => $location,
                                    'temperature' => $temperature,
                                    'feelsLike' => $feelsLike,
                                    'weatherDescription' => $weatherDescription,
                                    'weatherIcon' => $weatherData['weather'][0]['icon'] ?? '',
                                    'forecastHTML' => $forecastHTML
                                ]); ?>, true);
                            });
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        // Fall back to IP-based location data
                        updateWeatherDisplay(<?php echo json_encode([
                            'location' => $location,
                            'temperature' => $temperature,
                            'feelsLike' => $feelsLike,
                            'weatherDescription' => $weatherDescription,
                            'weatherIcon' => $weatherData['weather'][0]['icon'] ?? '',
                            'forecastHTML' => $forecastHTML
                        ]); ?>, true);
                    }
                );
            } else {
                console.error('Geolocation is not supported by this browser.');
                // Use IP-based location data
                updateWeatherDisplay(<?php echo json_encode([
                    'location' => $location,
                    'temperature' => $temperature,
                    'feelsLike' => $feelsLike,
                    'weatherDescription' => $weatherDescription,
                    'weatherIcon' => $weatherData['weather'][0]['icon'] ?? '',
                    'forecastHTML' => $forecastHTML
                ]); ?>, true);
            }
        });
    </script>
</body>

</html>