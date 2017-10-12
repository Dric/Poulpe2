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
 * Champ de chargement de fichier
 *
 * Ce champ est le seul qui ne soit pas récupéré par {@link PostedData::get()}
 *
 * @see \FileSystem\Upload::file()
 *
 * @package Forms\Fields
 */
class File extends StringField{

	/** @var string Type de champ HTML */
	protected $htmlType = 'file';
	/** @var string Icône associée */
	protected $associatedIcon = 'paperclip';

}