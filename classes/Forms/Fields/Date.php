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
class Date extends String{

	protected $htmlType = 'date';
	protected $type = 'date';
	protected $dateType = 'date';

	/**
	 * Déclaration d'un champ de saisie Date
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
	 * @param string  $dateType     Type de date saisie (`date`, `dateTime`, `fullDateTime`)
	 */
	public function __construct($name, $category, $value = null, $userValue = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $dateType = 'date'){
		parent::__construct($name, $category, $value, $userValue, $label, $placeholder, $help, $pattern, $important, $ACLLevel, $class, $disabled, false);
		Front::setJsFooter('<script src="js/moment-fr.js"></script>');
		Front::setJsFooter('<script src="js/bootstrap-datetimepicker.min.js"></script>');
		Front::setJsFooter('<script src="js/bootstrap-datetimepicker.fr.js"></script>');
		$this->dateType = (in_array($dateType, array('date', 'dateTime', 'fullDateTime'))) ? $this->dateType : 'date';
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Afficher la valeur utilisateur au lieu de la valeur globale
	 */
	public function display($enabled = true, $userValue = false){
		$attrs = 'data-dateType = "'.$this->dateType.'"';
		parent::display($enabled, $userValue, $attrs);
	}
} 