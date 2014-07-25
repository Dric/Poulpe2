<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 07/05/14
 * Time: 15:11
 */

namespace Logs;


use Sanitize;

/**
 * Objet événement de log
 *
 * @package Logs
 */
class EventLog {

	/**
	 * ID de l'utilisateur ayant généré l'événement
	 * @var int
	 */
	protected $user = 0;

	/**
	 * Composant au sein duquel a été généré l'alerte (module, site, connexion, etc.)
	 * @var string
	 */
	protected $component = null;

	/**
	 * Type d'événement
	 * @var string
	 */
	protected $type = null;

	/**
	 * Données de l'événement
	 * @var mixed
	 */
	protected $data = null;

	/**
	 * Horodatage au format timestamp de l'événement
	 * @var int
	 */
	protected $time = 0;

	/**
	 * Contexte de l'événement
	 *
	 * @param string $type      Type d'événement
	 * @param string $component Composant au sein duquel a été généré l'événement (facultatif)
	 * @param string $data      Données de l'événement (facultatif)
	 */
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
	 * Retourne l'ID de l'utilisateur ayant généré l'événement
	 * @return int
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Retourne le composant au sein duquel a été généré l'événement
	 * @return string
	 */
	public function getComponent() {
		return htmlspecialchars_decode($this->component);
	}

	/**
	 * Retourne le type d'événement
	 * @return string
	 */
	public function getType() {
		return htmlspecialchars_decode($this->type);
	}

	/**
	 * Retourne les données de l'événement
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Retourne l'horodatage de l'événement
	 *
	 * @param bool $humanReadable Retourne l'horodatage formaté si true, ou un timestamp Unix si false
	 *
	 * @return int|string
	 */
	public function getTime($humanReadable = false) {
		return ($humanReadable) ? Sanitize::date($this->time, 'dateTime') : $this->time;
	}
} 