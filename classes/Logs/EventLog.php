<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 07/05/14
 * Time: 15:11
 */

namespace Logs;


use Sanitize;

class EventLog {

	protected $user = 0;

	protected $component = null;

	protected $type = null;

	protected $data = null;

	protected $time = 0;

	public function __construct($type, $component = null, $data = null){
		global $cUser;
		$this->type = htmlspecialchars($type);
		$this->component = htmlspecialchars($component);
		$this->data = htmlspecialchars($data);
		$this->user = $cUser->getId();
		$this->time = time();
		EventsManager::addToLogs($this);
	}

	/**
	 * @return int
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getComponent() {
		return htmlspecialchars_decode($this->component);
	}

	/**
	 * @return null
	 */
	public function getType() {
		return htmlspecialchars_decode($this->type);
	}

	/**
	 * @return null
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Retourne l'horodatage de l'événement
	 * @param bool $humanReadable Retourne l'horodatage formaté si true, ou un timestamp Unix si false
	 *
	 * @return int|string
	 */
	public function getTime($humanReadable = false) {
		return ($humanReadable) ? Sanitize::date($this->time, 'dateTime') : $this->time;
	}
} 