# A PHP7 Curl Client
This is a PHP7 object-oriented wrapper of the [cURL extension](http://php.net/curl).

## Installation
```shell
composer require flexyproject/curl
```

## Usage
```php
require 'vendor/autoload.php';

use \FlexyProject\Curl\Client;

// Create Client object
$curl = new Client();

// Set Url
$curl->setUrl('https://api.github.com/user');

// Set options (here authentication options)
$curl->setOption([
	CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
	CURLOPT_USERPWD  => sprintf('%s:%s', 'user', 'pass')
]);

// Success callback
$curl->success(function (Client $instance) {
	$instance->getHeaders(); // Get headers info
	$instance->getResponse(); // Get response body
});
// Error callback
$curl->error(function (Client $instance) {
	$instance->getHeaders(); // Get headers info
	$instance->getResponse(); // Get response body
});

// Perform request
$curl->perform();
```
