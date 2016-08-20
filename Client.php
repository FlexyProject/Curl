<?php
namespace FlexyProject\Curl;
use RuntimeException;

/**
 * This class is an object-oriented wrapper of the PHP cURL extension.
 * @link    https://github.com/php-curl-class/php-curl-class
 * @link    http://stefangabos.ro/php-libraries/zebra-curl/
 */
class Client {

	/** Properties */
	protected $curl;
	protected $headers         = [];
	protected $successCallback = null;
	protected $errorCallback   = null;
	protected $url             = '';
	protected $response        = null;
	protected $options         = [];

	/**
	 * Main constructor,
	 * check cURL library is loaded
	 * @throws RuntimeException
	 */
	public function __construct() {
		if (!extension_loaded('curl')) {
			throw new RuntimeException('cURL library is not loaded');
		}

		$this->setCurl(curl_init());
		$this->setOption([
			CURLINFO_HEADER_OUT    => true,
			CURLOPT_HEADER         => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) {
				$array = explode(': ', $headerLine);
				if (!empty($array[0]) && !empty($array[1])) {
					$this->setHeader(trim($array[0]), isset($array[1]) ? trim($array[1]) : null);
				}
				else if (!empty(trim($array[0])) && empty($array[1])) {
					$this->setHeader(null, trim($array[0]));
				}

				return strlen($headerLine);
			}
		]);
	}

	/**
	 * Get url
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * Set url
	 * @param string $url
	 * @return Client
	 */
	public function setUrl(string $url): Client {
		$this->url = $url;

		return $this;
	}

	/**
	 * Get response
	 * @return null
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Set response
	 * @param null $response
	 * @return Client
	 */
	public function setResponse($response) {
		$this->response = $response;

		return $this;
	}

	/**
	 * Set cURL option(s)
	 * @param int|array  $option
	 * @param mixed|null $value
	 * @return bool
	 */
	public function setOption($option, $value = null): bool {
		if (is_array($option)) {
			foreach ($option as $name => $value) {
				if (is_null($value)) {
					unset($this->options[$name]);
				}
				else {
					$this->options[$name] = $value;
				}
			}
		}
		else if (is_null($value)) {
			unset($this->options[$option]);
		}
		else {
			$this->options[$option] = $value;
		}

		if (isset($this->getOptions()[CURLOPT_URL]) && !empty($this->getOptions()[CURLOPT_URL])) {
			$this->setUrl($this->getOptions()[CURLOPT_URL]);
		}

		return curl_setopt_array($this->curl, $this->options);
	}
	
	/**
	 * Add cURL options without overwriting key/value
	 * @param array $options
	 * @return bool
	 */
	public function addOptions(array $options): bool {
		foreach ($options as $name => $value) {
			if (isset($this->options[$name]) && is_array($this->options[$name])) {
				$this->options[$name] = array_merge($this->options[$name], $value);
			}
			else if (is_null($value)) {
				unset($this->options[$name]);
			}
			else {
				$this->options[$name] = $value;
			}
		}
		
		if (isset($this->getOptions()[CURLOPT_URL]) && !empty($this->getOptions()[CURLOPT_URL])) {
			$this->setUrl($this->getOptions()[CURLOPT_URL]);
		}
	
		return curl_setopt_array($this->curl, $this->options);
	}

	/**
	 * Get options
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * Get information regarding a specific transfer
	 * @param null|int $opt
	 * @return array|string
	 */
	public function getInfo($opt = null) {
		if (null === $opt) {
			return curl_getinfo($this->getCurl());
		}

		return curl_getinfo($this->getCurl(), $opt);
	}

	/**
	 * Set curl
	 * @param resource $curl
	 * @return Client
	 */
	public function setCurl($curl): Client {
		$this->curl = $curl;

		return $this;
	}

	/**
	 * Get cURL resource
	 * @return resource
	 */
	public function getCurl() {
		return $this->curl;
	}

	/**
	 * Perform a cURL session
	 * @return Client
	 */
	public function perform(): Client {
		if (!empty($this->getUrl())){
			$this->setOption(CURLOPT_URL, $this->getUrl());
		}

		if ($this->getOptions()[CURLOPT_HEADER] === 1) {
			$this->setResponse(substr($this->getResponse(), $this->getInfo(CURLINFO_HEADER_SIZE)));
		}

		$this->setResponse(curl_exec($this->getCurl()));

		/**
		 * Check returned HTTP code between 100 and 399.
		 * See issue #1
		 */
		if (filter_var(
			$this->getInfo(CURLINFO_HTTP_CODE),
			FILTER_VALIDATE_INT,
			['options' => ['min_range' => 100, 'max_range' => 399]])
		) {
			$this->_call($this->successCallback ?? function(Client $instance){}, $this);
		}
		else {
			$this->_call($this->errorCallback ?? function(Client $instance){}, $this);
		}

		return $this;
	}

	/**
	 * Call callable function
	 * @param callable $function
	 * @param mixed    $params
	 * @return void
	 */
	private function _call(callable $function, ...$params) {
		if (is_callable($function)) {
			call_user_func_array($function, $params);
		}
	}

	/**
	 * Set header
	 * @param string|null $key
	 * @param string|null $value
	 * @return Client
	 */
	public function setHeader($key, $value): Client {
		if (null === $key) {
			$this->headers[] = $value;
		}
		else {
			$this->headers[$key] = $value;
		}

		return $this;
	}

	/**
	 * Get headers
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * Success callback method
	 * @param callable $callback
	 * @return Client
	 */
	public function success(callable $callback): Client {
		$this->successCallback = $callback;

		return $this;
	}

	/**
	 * Error callback method
	 * @param callable $callback
	 * @return Client
	 */
	public function error(callable $callback): Client {
		$this->errorCallback = $callback;

		return $this;
	}

	/**
	 * Destructor, closing cURL connection
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * Close a cURL session
	 * @return void
	 */
	public function close() {
		curl_close($this->curl);
	}
}
