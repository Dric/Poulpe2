<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 12/06/14
 * Time: 08:39
 */

namespace Modules\UsersTraces;


class Log {
	protected $server = null;
	protected $dateTime = 0;
	protected $nickName = null;
	protected $name = null;
	protected $client = null;

	public function __construct($server, $dateTime, $nickName, $name, $client = null){
		$this->server = strtoupper($server);
		$this->dateTime = (int)$dateTime;
		$this->nickName = $nickName;
		$this->name     = $name;
		$this->client   = $client;
	}

	public function __get($prop){
		return (isset($this->$prop)) ? $this->$prop : false;
	}

	/**
	 * @return int
	 */
	public function getDateTime() {
		return $this->dateTime;
	}

} 