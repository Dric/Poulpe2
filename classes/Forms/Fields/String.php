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

class String extends Field{

	protected $type = 'string';
	protected $htmlType = 'text';
	protected $autoComplete = true;

	/**
	 * Déclaration d'un champ de saisie Texte
	 *
	 * @param string  $name         Nom du champ
	 * @param string  $category     Catégorie du champ (global ou user)
	 * @param string  $value        Valeur du champ
	 * @param string  $userValue    Valeur utilisateur du champ (facultatif)
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
	public function __construct($name, $category, $value = null, $userValue = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $autoComplete = true){

		$data = (!$autoComplete) ? array('autoComplete' => false) : null;
		parent::__construct($name, $this->type, $category, $value, $label, $placeholder, $data, $help, $pattern, $userValue, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue
	 */
	public function display($enabled = true, $userValue = false){
		$attrs = (!$this->autoComplete) ? 'autocomplete="off"' : null;
		parent::display($enabled, $userValue, $attrs);
	}
} 