<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 02/07/14
 * Time: 11:15
 */

namespace Modules\Portal;

/**
 * Class Group
 *
 * @package Modules\Portal
 */
class Group {
	protected $id = 0;
	protected $name = '';
	protected $enabled = true;
	protected $desc = null;
	protected $badgeLabel = null;
	protected $badgeType = null;
	protected $shared = true;
	protected $author = 0;

	/**
	 * Création d'un objet Groupe de widgets
	 *
	 * @param string  $name         Nom du groupe
	 * @param int     $author       ID de l'utilisateur créateur du widget
	 * @param bool    $shared       groupe partagé
	 * @param bool    $enabled      groupe affiché
	 * @param string  $desc         Description courte du lien
	 * @param int     $id           ID du groupe
	 * @param string  $badgeLabel   Contenu du badge optionnel
	 * @param string  $badgeType    Couleur du badge optionnel
	 */
	public function __construct($name, $author, $shared = true, $enabled = true, $desc = null, $id = 0, $badgeLabel = null, $badgeType = null){
		$this->name       = $name;
		$this->author     = (int) $author;
		$this->shared     = (bool) $shared;
		$this->enabled    = (bool) $enabled;
		$this->desc       = $desc;
		$this->id         = (int) $id;
		$this->badgeLabel = $badgeLabel;
		$this->badgeType  = (array_key_exists($badgeType, Portal::$badgesTypes)) ? $badgeType : 'danger';
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return boolean
	 */
	public function getEnabled() {
		return $this->enabled;
	}

	/**
	 * @return null
	 */
	public function getDesc() {
		return $this->desc;
	}

	/**
	 * @return null
	 */
	public function getBadgeLabel() {
		return $this->badgeLabel;
	}

	/**
	 * @return null
	 */
	public function getBadgeType() {
		return $this->badgeType;
	}

	/**
	 * @return boolean
	 */
	public function getShared() {
		return $this->shared;
	}

	/**
	 * @return int
	 */
	public function getAuthor() {
		return $this->author;
	}

} 