<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 04/06/14
 * Time: 10:03
 */

namespace Modules\PostIt;


use Michelf\MarkdownExtra;
use Users\UsersManagement;

/**
 * Class Post
 *
 * Objet Post-it
 *
 * @package Modules\PostIt
 */
class Post {
	protected $id = 0;
	protected $content = null;
	protected $author = 0;
	protected $shared = false;
	protected $created = 0;
	protected $modified = 0;

	/**
	 * Crée un objet Post
	 * @param object|string $content Objet base de données de post ou contenu du post-it
	 * @param int  $author ID de l'auteur
	 * @param int  $shared Post-it partagé
	 * @param null $created Timestamp de création
	 * @param null $modified Timestamp de modification
	 * @param null $id ID du post-it
	 */
	public function __construct($content, $author = 0, $shared = 0, $created = null, $modified = null, $id = null){
		if (is_object($content)){
			$tab = (array)$content;
			unset($content);
			/* @var $content */
			extract($tab);
		}
		$this->content = $content;
		$this->author = (int)$author;
		$this->shared = (bool)$shared;
		if (!empty($created)) $this->created = (int)$created;
		if (!empty($modified)) $this->modified = (int)$modified;
		if (!empty($id)) $this->id = (int)$id;
	}

	/**
	 * Retourne l'auteur du post-it
	 * @param bool $realValue Si vrai, retourne l'ID de l'auteur, sinon renvoie son nom
	 *
	 * @return int|string
	 */
	public function getAuthor($realValue = false) {
		return ($realValue) ? $this->author : UsersManagement::getUserName($this->author);
	}

	/**
	 * Retourne le contenu du post-it
	 * @param bool $realValue
	 *
	 * @return null
	 */
	public function getContent($realValue = false) {
		if (!$realValue){
			$content = MarkdownExtra::defaultTransform(\Sanitize::decryptData(htmlspecialchars_decode($this->content)));
			// Gestion des antislashes dans les balises code (les antislashes sont doublés dans ces cas-là par le système)
			$content = str_replace('\\\\', '\\', $content);
		}else{
			$content = \Sanitize::decryptData($this->content);
		}
		return $content;
	}

	/**
	 * @return boolean
	 */
	public function getShared() {
		return $this->shared;
	}

	/**
	 * @param bool $realValue Si vrai, retourne une date formatée, sinon renvoie un timestamp
	 *
	 * @return int|string
	 */
	public function getCreated($realValue = false) {
		return ($realValue) ? $this->created : \Sanitize::date($this->created, 'dateAtTime');
	}

	/**
	 * @param bool $realValue Si vrai, retourne une date formatée, sinon renvoie un timestamp
	 *
	 * @return int|string
	 */
	public function getModified($realValue = false) {
		return ($realValue) ? $this->modified : \Sanitize::date($this->modified, 'dateAtTime');
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
} 