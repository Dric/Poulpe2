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
	 * Fichier VBS pour gérer l'accès à l'application
	 * @var string
	 */
	protected $file = '';

	/**
	 * Etat de la maintenance de l'application
	 * @var bool
	 */
	protected $maintenance = false;

	/**
	 * Message diffusé aux utilisateurs lorsque l'application est en maintenance
	 * @var string
	 */
	protected $message = '';

	/**
	 * Objet d'une application
	 * @param string $title Titre de l'application
	 * @param string $file Fichier VBS
	 * @param bool $maintenance Status de la maintenance
	 * @param string $message Message diffusé aux utilisateurs en cas de maintenance
	 */
	public function __construct($title = null, $file = null, $maintenance = false, $message = null){
		if (!empty($title)){
			$this->name = Sanitize::sanitizeFilename($title);
			$this->title = $title;
		}
		if (!empty($file))    $this->file     = $file;
		if (!empty($message)) $this->message  = $message;
		$this->maintenance = $maintenance;
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
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}
}