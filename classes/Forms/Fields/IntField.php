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
 * Champ de formulaire pour nombres entiers
 *
 * Ce champ est de type `number`, qui a été introduit en html5
 * Sur les navigateurs le supportant, Des flèches permettant d'augmenter ou réduire la valeur sont affichées
 *
 * Une validation est également faite sur la saisie afin de vérifier qu'il s'agit d'un nombre
 *
 * @package Forms\Fields
 */
class IntField extends StringField{

	/** @var string Type de champ (pour sauvegarde) */
	protected $type = 'int';
	/** @var string Type de champ HTML */
	protected $htmlType = 'number';
	/**
	 * Pas d'incrémentation des valeurs
	 * @var float
	 */
	protected $step = 1;

	/**
	 * Déclaration d'un champ de saisie de nombre
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
	 * @param float   $step         Pas d'incrémentation (facultatif)
	 */
	public function __construct($name, $value = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $step = null){
		if (!empty($step)) $this->step = (float)$step;
		parent::__construct($name, $value, $label, $placeholder, $help, $pattern, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool    $enabled    Champ modifiable
	 * @param bool    $userValue  Afficher la valeur utilisateur au lieu de la valeur globale
	 * @param string  $attrs      Attributs html à ajouter au champ de saisie
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
		$attrs .= ($this->step != 1) ? ' step="'.$this->step.'"' : null;
		parent::display($enabled, $userValue, $attrs);
	}
}