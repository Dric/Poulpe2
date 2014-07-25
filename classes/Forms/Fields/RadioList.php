<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 08:58
 */

namespace Forms\Fields;


use Components\Help;
use Forms\Field;

/**
 * Champ permettant de choisir une valeur dans une liste via des boutons radio
 *
 * On peut utiliser une simple liste dans un champ `select`, mais les boutons radio permettent d'afficher simultanément tous les choix.
 *
 * @package Forms\Fields
 */
class RadioList extends CheckboxList{

	protected $type = 'radioList';
	protected $htmlType = 'radio';

} 