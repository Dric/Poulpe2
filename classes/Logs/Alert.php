<?php
/**
 * Classe de gestion des alertes
 *
 * User: cedric.gallard
 * Date: 21/03/14
 * Time: 09:26
 *
 */

namespace Logs;

/**
 * Classe de gestion des alertes
 *
 * @package Alerts
 */
class Alert {

	/**
	 * Titre facultatif
	 * @var string
	 */
	protected $title = null;

	/**
	 * Type d'alerte
	 * @var string
	 */
	protected $type = '';

	/**
	 * Contenu HTML de l'alerte
	 * @var string
	 */
	protected $content = '';


	/**
	 * Nouvelle alerte
	 *
	 * @param string $type Type de l'alerte
	 * @param string $content Contenu HTML de l'alerte
	 * @param string $title Titre (facultatif)
	 */
	public function __construct($type, $content, $title = ''){
		if (in_array($type, AlertsManager::getAllowedTypes())){
			$this->type = ($type == 'error') ? 'danger' : $type;
			$this->content = $content;
			$this->title = (!empty($title)) ? $title : null;
			AlertsManager::addToAlerts($this);
		}
	}

	/**
	 * Destruction de l'alerte
	 */
	public function __destruct(){
		AlertsManager::removeAlert($this);
	}


	/**
	 * retourne le titre de l'alerte
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Retourne le type d'alerte
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Retourne le contenu de l'alerte
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}
}