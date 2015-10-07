<?php
namespace Scion\Curl;

/**
 * This class is an object-oriented wrapper of the PHP cURL extension.
 * @link    https://github.com/php-curl-class/php-curl-class
 * @link    http://stefangabos.ro/php-libraries/zebra-curl/
 */
class Client {

	/** Properties */
	protected $curl;
	protected $headers          = [];
	protected $successCallback  = null;
	protected $errorCallback    = null;
	protected $completeCallback = null;
	protected $url              = null;
	protected $response         = null;
	protected $options          = [];

	/**
	 * Main constructor,
	 * check cURL library is loaded
	 * @throws \RuntimeException
	 */
	public function __construct() {
		if (!extension_loaded('curl')) {
			throw new \RuntimeException('cURL library is not loaded');
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
	 * @return null
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Set url
	 * @param null $url
	 * @return Client
	 */
	public function setUrl($url) {
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
	public function setOption($option, $value = null) {
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

		return curl_setopt_array($this->curl, $this->options);
	}

	/**
	 * Get options
	 * @return array
	 */
	public function getOptions() {
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
	 * @param mixed $curl
	 * @return Client
	 */
	public function setCurl($curl) {
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
	 * @return mixed
	 */
	public function perform() {
		$this->setResponse(curl_exec($this->getCurl()));
		if ($this->getOptions()[CURLOPT_HEADER] === 1) {
			$this->setResponse(substr($this->getResponse(), $this->getInfo(CURLINFO_HEADER_SIZE)));
		}

		$this->setUrl(isset($this->getOptions()[CURLOPT_URL]) ? $this->getOptions()[CURLOPT_URL] : null);

		$this->_call($this->completeCallback, $this);
		$this->_call($this->successCallback, $this);
		$this->_call($this->errorCallback, $this);

		return $this->getResponse();
	}

	/**
	 * Call callable function
	 * @param callable $function
	 * @param mixed    $params
	 */
	private function _call($function, ...$params) {
		if (is_callable($function)) {
			call_user_func_array($function, $params);
		}
	}

	/**
	 * Set header
	 * @param string|null $key
	 * @param string|null $value
	 * @return $this
	 */
	public function setHeader($key, $value) {
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
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Success callback method
	 * @param callable $callback
	 * @return $this
	 */
	public function success(callable $callback) {
		$this->successCallback = $callback;

		return $this;
	}

	/**
	 * Error callback method
	 * @param callable $callback
	 * @return $this
	 */
	public function error(callable $callback) {
		$this->errorCallback = $callback;

		return $this;
	}

	/**
	 * Complete callback method
	 * @param callable $callback
	 * @return $this
	 */
	public function complete(callable $callback) {
		$this->completeCallback = $callback;

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
	 */
	public function close() {
		curl_close($this->curl);
	}
}