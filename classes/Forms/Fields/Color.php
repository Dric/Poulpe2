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
 * Champ color (similaire au champ texte, introduit en html5)
 *
 * Ce champ est similaire au type text et a été introduit en html5.
 * Il permet sur les navigateurs qui le supportent de proposer une saisie de couleur.
 * Si le navigateur ne le supporte pas, il affiche un champ texte.
 *
 * @package Forms\Fields
 */
class Color extends String{

	protected $htmlType = 'color';

}