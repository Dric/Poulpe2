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
use Front;

/**
 * Champ de saisie de date
 *
 * Ce champ est similaire au type text et a été introduit en html5.
 * Il permet sur les navigateurs qui le supportent de proposer une saisie de date.
 * Si le navigateur ne le supporte pas, il affiche un champ texte.
 *
 * Ce champ charge les scripts javascript qui prennent en charge l'écran de saisie du champ date
 *
 * @package Forms\Fields
 */
class Date extends StringField{

	/** @var string Type de champ HTML */
	protected $htmlType = 'date';
	/** @var string Type de champ (pour sauvegarde) */
	protected $type = 'date';
	/** @var string Type de date */
	protected $dateType = 'date';
	/** @var string Icône associée */
	protected $associatedIcon = 'calendar';

	/**
	 * Déclaration d'un champ de saisie Date
	 *
	 * @param string  $name         Nom du champ
	 * @param string  $value        Valeur du champ (accepte un timestamp ou une date française)
	 * @param string  $label        Intitulé du champ (facultatif)
	 * @param string  $placeholder  Indicateur de saisie du champ (facultatif)
	 * @param string  $help         Message d'aide affiché en infobulle (facultatif)
	 * @param Pattern $pattern      Paramètres de validation (facultatif)
	 * @param bool    $important    Le champ est marqué comme étant important (facultatif)
	 * @param string  $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string  $class        Classe CSS à ajouter au champ (facultatif)
	 * @param bool    $disabled     Champ désactivé (facultatif)
	 * @param string  $dateType     Type de date saisie (`date`, `dateTime`, `fullDateTime`)
	 */
	public function __construct($name, $value = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $dateType = 'date'){
		$value = \Sanitize::date($value, 'intlDate');
		parent::__construct($name, $value, $label, $placeholder, $help, $pattern, $important, $ACLLevel, $class, $disabled, false);
		$this->dateType = (in_array($dateType, array('date', 'dateTime', 'fullDateTime'))) ? $this->dateType : 'date';
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Afficher la valeur utilisateur au lieu de la valeur globale
	 */
	public function display($enabled = true, $userValue = false){
		$attrs = ' data-datetype="'.$this->dateType.'"';
		parent::display($enabled, $userValue, $attrs);
	}
} 