<?php

namespace App\Web;


class Lang
{

	const DEFAULT_LANG = 'en';
	const LANG_PATH = __DIR__ . '/../../resources/lang/';

	/** @var  array */
	protected $cache = [];

	/** @var  string */
	protected $path;

	/** @var  string */
	protected $lang;

	/** @var  Lang */
	protected static $instance;

	/**
	 * Lang constructor.
	 * @param $lang
	 * @param $path
	 */
	public function __construct($lang, $path)
	{
		$this->lang = $lang;
		$this->path = $path;

	}

	/**
	 * @return Lang
	 */
	public static function getInstance()
	{

		if (self::$instance === null) {
			self::$instance = self::build();
		}

		return self::$instance;
	}

	/**
	 * @param string $lang
	 * @param string $path
	 * @return Lang
	 */
	public static function build($lang = self::DEFAULT_LANG, $path = null)
	{

		if (strlen($lang) !== 2) {
			$lang = strtolower(substr($lang, 0, 2));
		}

		if (file_exists(($path ? $path : self::LANG_PATH) . $lang . '.lang.php')) {
			self::$instance = new self($lang, $path);
		} else {
			self::$instance = new self(self::DEFAULT_LANG, $path);
		}

		return self::$instance;
	}

	/**
	 * Recognize the current language from the request.
	 * @return bool|string
	 */
	public static function recognize()
	{
		return substr(@$_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	}


	/**
	 * @param $key
	 * @param array $args
	 * @return string
	 */
	public function get($key, $args = [])
	{
		return $this->getString($key, $this->lang, $args);
	}

	/**
	 * @param $key
	 * @param $lang
	 * @param $args
	 * @return string
	 */
	private function getString($key, $lang, $args)
	{

		if (array_key_exists($lang, $this->cache)) {
			$transDict = $this->cache[$lang];
		} else if (file_exists($this->path . $lang . '.lang.php')) {
			$transDict = include $this->path . $lang . '.lang.php';
			$this->cache[$lang] = $transDict;
		} else {
			$transDict = [];
		}

		if (array_key_exists($key, $transDict)) {
			return vsprintf($transDict[$key], $args);
		}

		if ($lang !== self::DEFAULT_LANG) {
			return $this->getString($key, self::DEFAULT_LANG, $args);
		}

		return $key;
	}

	/**
	 * @return string
	 */
	public function getLang(): string
	{
		return $this->lang;
	}
}