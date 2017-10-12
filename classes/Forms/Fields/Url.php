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
 * Champ de saisie d'url (adresse internet)
 *
 * Ce champ est similaire au type text et a été introduit en html5.
 * Il permet sur les navigateurs qui le supportent de vérifier que la saisie est une url valide
 *
 * Si Javascript est activé, la validation est effectuée par Poulpe2
 *
 * @package Forms\Fields
 */
class Url extends StringField{

	/** @var string Type de champ HTML */
	protected $htmlType = 'url';
	/** @var string Icône associée */
	protected $associatedIcon = 'link';

} 