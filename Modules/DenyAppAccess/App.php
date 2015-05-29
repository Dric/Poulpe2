<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/05/14
 * Time: 14:52
 */

namespace Modules\DenyAppAccess;


use Sanitize;

/**
 * Class App Application dont on doit gérer l'accès à l'aide du module DenyAppAccess
 *
 * @see DenyAppAccess
 * @package Modules\DenyAppAccess
 */
class App {

	/**
	 * Nom formaté de l'application
	 * @var string
	 */
	protected $name = '';

	/**
	 * Titre affiché de l'application
	 * @var string
	 */
	protected $title = '';

	/**
	 * Fichier du script gérant l'accès à l'application
	 * @var string
	 */
	protected $file = '';

	/**
	 * Emplacement du script
	 * @var string
	 */
	protected $path = '';

	/**
	 * Type de script (vbs, powershell...)
	 * @var string
	 */
	protected $language = 'powershell';

	/**
	 * Etat de la maintenance de l'application
	 * @var bool
	 */
	protected $maintenance = false;

	/**
	 * Message diffusé aux utilisateurs lorsque l'application est en maintenance
	 * @var string
	 */
	protected $maintenanceMessage = '';

	/**
	 * Message d'information activable ou non
	 * @var bool
	 */
	protected $infoEnabled = false;
	/**
	 * Etat du message d'information lors du lancement de l'application
	 * @var bool
	 */
	protected $info = false;

	/**
	 * Message diffusé aux utilisateurs lors du lancement de l'application
	 * @var string
	 */
	protected $infoMessage = '';

	/**
	 * Objet d'une application
	 *
	 * @param string $title              Titre de l'application
	 * @param string $file               Fichier VBS
	 * @param string $path               Emplacement du script
	 * @param bool   $maintenance        Status de la maintenance
	 * @param string $maintenanceMessage Message diffusé aux utilisateurs en cas de maintenance
	 * @param bool   $infoEnabled        Message d'information activable ou non
	 * @param bool   $info               Status de l'information
	 * @param string $infoMessage        Message d'information aux utilsiateurs lors du lancement
	 */
	public function __construct($title = null, $file = null, $path = null, $maintenance = false, $maintenanceMessage = null, $infoEnabled = false, $info = false, $infoMessage = null){
		if (!empty($title)){
			$this->name = Sanitize::sanitizeFilename($title);
			$this->title = $title;
		}
		if (!empty($file))    $this->file     = $file;
		if (!empty($path))    $this->path     = $path;
		if (!empty($maintenanceMessage)) $this->maintenanceMessage  = $maintenanceMessage;
		$this->maintenance = $maintenance;
		$this->infoEnabled = $infoEnabled;
		if (!empty($infoMessage)) $this->infoMessage  = $infoMessage;
		$this->info = $info;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->name = Sanitize::sanitizeFilename($title);
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * @param string $file
	 */
	public function setFile($file) {
		$this->file = $file;
	}

	/**
	 * @return boolean
	 */
	public function getMaintenance() {
		return $this->maintenance;
	}

	/**
	 * @param boolean $maintenance
	 */
	public function setMaintenance($maintenance) {
		$this->maintenance = (bool)$maintenance;
	}

	/**
	 * @return string
	 */
	public function getMaintenanceMessage() {
		return $this->maintenanceMessage;
	}

	/**
	 * @param string $maintenanceMessage
	 */
	public function setMaintenanceMessage($maintenanceMessage) {
		$this->maintenanceMessage = $maintenanceMessage;
	}

	/**
	 * @return boolean
	 */
	public function getInfo() {
		return $this->info;
	}

	/**
	 * @param boolean $info
	 */
	public function setInfo($info) {
		$this->info = $info;
	}

	/**
	 * @return string
	 */
	public function getInfoMessage() {
		return $this->infoMessage;
	}

	/**
	 * @param string $infoMessage
	 */
	public function setInfoMessage($infoMessage) {
		$this->infoMessage = $infoMessage;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param string $language
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}

	/**
	 * @return boolean
	 */
	public function isInfoEnabled() {
		return $this->infoEnabled;
	}

	/**
	 * @param boolean $infoEnabled
	 */
	public function setInfoEnabled($infoEnabled) {
		$this->infoEnabled = $infoEnabled;
	}
}