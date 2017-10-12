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
use Forms\Pattern;

/**
 * Champ de saisie d'une suite de valeurs, pour stockage sous forme de tableau
 *
 * Visuellement, c'est un champ `textarea` qui va prendre une valeur par ligne
 *
 * @package Forms\Fields
 */
class ValuesArray extends Field{

	/** @var string Type de champ (pour sauvegarde) */
	protected $type = 'array';
	/**
	 * Permet de spécifier qu'on veut sérialiser le tableau à la sauvegarde
	 * @var bool
	 */
	protected $serialize = false;

	/**
	 * Déclaration d'un Textarea
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
	 * @param bool    $serialize    Sérialiser le tableau final
	 */
	public function __construct($name, $value, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $serialize = false){
		$this->serialize = (bool)$serialize;
		parent::__construct($name, $this->type, $value, $label, $placeholder, $help, $pattern, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Afficher la valeur utilisateur au lieu de la valeur globale
	 */
	public function display($enabled = true, $userValue = false){
		/**
		 * @var Pattern $pattern
		 */
		$pattern = $this->pattern;
		$displayPattern = null;
		if (!empty($pattern)){
			if ($pattern->getRequired())        $displayPattern .= 'required ';
			if ($pattern->getMinLength() > 0)   $displayPattern .= 'data-minlength="'.$pattern->getMinLength().'" ';
			if ($pattern->getMaxLength() > 0)   $displayPattern .= 'data-maxlength="'.$pattern->getMaxLength().'" ';
			if ($pattern->getRegExp() !== null) $displayPattern .= 'pattern="'.$pattern->getRegExp().'" ';
		}
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		if (!empty($this->placeholder) and is_array($this->placeholder)){
			$this->placeholder = implode("\n\n&nbsp;", $this->placeholder);
		}
		$helpBlock = 'Utilisation : Un item par ligne';
		?>
		<div class="form-group <?php if ($this->important) echo 'has-warning'; ?>">
			<label for="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>"><?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help != '') Help::iconHelp($this->help); ?></label>
			<textarea class="form-control<?php echo ' '.$this->class; ?>" rows="<?php echo count($this->value); ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> <?php if ($this->disabled or !$enabled) echo 'disabled'; ?> <?php echo $displayPattern; ?>><?php echo (!empty($value)) ? implode(PHP_EOL, $value) : null; ?></textarea>
			<input type="hidden" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_serialize" name="field_<?php echo $this->type ?>_<?php echo $this->name ?>_serialize" value="<?php echo $this->serialize ?>">
			<div class="help-block with-errors"></div>
			<p class="help-block"><?php echo $helpBlock; ?></p>
		</div>
		<?php
	}

	/**
	 * Affichage du champ dans un table de bdd
	 *
	 * @param string      $tableName  Nom de la table
	 * @param int|string  $rowId      ID de la ligne
	 * @param mixed       $value      Valeur du champ
	 */
	public function tableItemDisplay($tableName, $rowId, $value = null){
		/**
		 * @var Pattern $pattern
		 */
		$pattern = $this->pattern;
		$displayPattern = null;
		if (!empty($pattern)){
			if ($pattern->getRequired())        $displayPattern .= 'required ';
			if ($pattern->getMinLength() > 0)   $displayPattern .= 'data-minlength="'.$pattern->getMinLength().'" ';
			if ($pattern->getMaxLength() > 0)   $displayPattern .= 'data-maxlength="'.$pattern->getMaxLength().'" ';
			if ($pattern->getRegExp() !== null) $displayPattern .= 'pattern="'.$pattern->getRegExp().'" ';
		}
		?>
			<textarea class="form-control<?php echo ' '.$this->class; ?>" rows="<?php echo count($this->value); ?>" id="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> <?php if ($this->disabled) echo 'disabled'; ?> <?php echo $displayPattern; ?>><?php echo implode(PHP_EOL, $value); ?></textarea>
			<input type="hidden" id="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>_serialize" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>_serialize" value="<?php echo $this->serialize ?>">
			<p class="help-block">Utilisation : Un item par ligne</p>
		<?php
	}
} 