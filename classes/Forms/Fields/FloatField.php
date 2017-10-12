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
 * Champ de formulaire pour nombres flottants
 *
 * Ce champ est de type `number`, qui a été introduit en html5
 * Sur les navigateurs le supportant, Des flèches permettant d'augmenter ou réduire la valeur sont affichées
 *
 * Une validation est également faite sur la saisie afin de vérifier qu'il s'agit d'un nombre
 *
 * @package Forms\Fields
 */
class FloatField extends IntField{

	/** @var string Type de champ (pour sauvegarde) */
	protected $type = 'float';
	/** @var string Type de champ HTML */
	protected $htmlType = 'number';
}