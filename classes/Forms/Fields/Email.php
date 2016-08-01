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
 * Champ de saisie d'adresse email
 *
 * Ce champ est similaire au type text et a été introduit en html5.
 * Il permet sur les navigateurs qui le supportent de vérifier que la saisie est une adresse email valide
 *
 * Si Javascript est activé, la validation est effectuée par Poulpe2
 *
 * @package Forms\Fields
 */
class Email extends StringField{

	protected $htmlType = 'email';
	protected $associatedIcon = 'envelope';

}