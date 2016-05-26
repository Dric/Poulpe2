<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 08:58
 */

namespace Forms\Fields;


use Forms\Field;
use Forms\Pattern;

/**
 * Champ de saisie de texte
 *
 * @package Forms\Fields
 */
class String extends Field{

	protected $type = 'string';
	protected $htmlType = 'text';
	/**
	 * Activer l'auto-completion par le navigateur
	 * @var bool
	 */
	protected $autoComplete = true;

	/**
	 * Déclaration d'un champ de saisie Texte
	 *
	 * @param string  $name         Nom du champ
	 * @param string  $value        Valeur du champ
	 * @param string  $label        Intitulé du champ (facultatif)
	 * @param string  $placeholder  Indicateur de saisie du champ (facultatif)
	 * @param string  $help         Message d'aide affiché en infobulle (facultatif)
	 * @param Pattern $pattern      Paramètres de validation (facultatif)
	 * @param bool    $important    Le champ est marqué comme étant important (facultatif)
	 * @param string  $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string  $class        Classe CSS à ajouter au champ (facultatif)
	 * @param bool    $disabled     Champ désactivé (facultatif)
	 * @param bool    $autoComplete Activer l'auto-complétion (facultatif)
	 */
	public function __construct($name, $value = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $autoComplete = true){
		$this->autoComplete = (bool)$autoComplete;
		parent::__construct($name, $this->type, $value, $label, $placeholder, $help, $pattern, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool    $enabled    Champ modifiable
	 * @param bool    $userValue  Afficher la valeur utilisateur au lieu de la valeur globale
	 * @param string  $attrs      Attributs html à ajouter au champ de saisie
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
		$attrs .= (!$this->autoComplete) ? ' autocomplete="off"' : null;
		parent::display($enabled, $userValue, $attrs);
	}
} 