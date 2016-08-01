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
 * Champ de saisie d'heure
 *
 * Ce champ est un champ texte qui propose en plus une saisie d'heure.
 * Le format récupéré est un nombre de secondes écoulées depuis minuit (pour avoir un fonctionnement similaire au timestamp Unix)
 *
 * Ce champ charge les scripts javascript qui prennent en charge l'écran de saisie du champ
 *
 * @package Forms\Fields
 */
class Time extends StringField{

	protected $htmlType = 'time';
	protected $type = 'time';
	protected $dateType = 'time';
	protected $associatedIcon = 'time';

	/**
	 * Déclaration d'un champ de saisie Time
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
	 * @param string  $dateType     Type de date saisie (`time`, `fullTime`)
	 */
	public function __construct($name, $value = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $dateType = 'time'){
		parent::__construct($name, $value, $label, $placeholder, $help, $pattern, $important, $ACLLevel, $class, $disabled, false);
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/moment-fr.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/bootstrap-datetimepicker.min.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/bootstrap-datetimepicker.fr.js"></script>');
		Front::setJsFooter('<script>dateTimePick();</script>');
		$this->dateType = (in_array($dateType, array('time', 'fullTime'))) ? $this->dateType : 'time';
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