<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 02/07/14
 * Time: 11:15
 */

namespace Modules\Portal;

/**
 * Class widget
 *
 * @package Modules\Portal
 */
class widget {
	protected $id = 0;
	protected $name = '';
	protected $enabled = true;
	protected $desc = null;
	protected $link = '';
	protected $badgeLabel = null;
	protected $badgeType = null;
	protected $shared = true;
	protected $author = 0;
	protected $group = null;
	protected $favicon = null;

	/**
	 * Création d'un objet Widget
	 *
	 * @param string  $name         Nom du widget
	 * @param string  $link         Lien du widget
	 * @param int     $author       ID de l'utilisateur créateur du widget
	 * @param bool    $shared       Widget partagé
	 * @param int     $group        ID du groupe de widgets
	 * @param bool    $enabled      Widget affiché
	 * @param string  $desc         Description courte du lien
	 * @param int     $id           ID du widget
	 * @param string  $badgeLabel   Contenu du badge optionnel
	 * @param string  $badgeType    Couleur du badge optionnel
	 */
	public function __construct($name, $link, $author, $shared = true, $group = null, $enabled = true, $desc = null, $id = 0, $badgeLabel = null, $badgeType = null){
		$this->name       = $name;
		$this->link       = $link;
		$this->author     = (int) $author;
		$this->shared     = (bool) $shared;
		$this->group      = (int) $group;
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
	 * @return string
	 */
	public function getLink() {
		return $this->link;
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

	/**
	 * @return null
	 */
	public function getGroup() {
		return $this->group;
	}

	/**
	 * @return null
	 */
	public function getFavicon() {
		return $this->favicon;
	}
} 