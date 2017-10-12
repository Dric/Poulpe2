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
 * Champ de saisie de valeurs séparées par des virgules, pour une sauvegarde dans un tableau séquentiel
 *
 * @package Forms\Fields
 */
class ValuesList extends StringField{

	/** @var string Type de champ HTML */
	protected $htmlType = 'text';

}