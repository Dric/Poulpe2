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

class Text extends Field{

	protected $type = 'text';
	protected $rows = 5;

	/**
	 * Déclaration d'un champ de saisie Texte
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
	 * @param int     $id           ID du champ (facultatif)
	 * @param string  $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string  $class        Classe CSS à ajouter au champ (facultatif)
	 * @param bool    $disabled     Champ désactivé (facultatif)
	 * @param bool    $rows         Nombre de lignes du champ (facultatif)
	 */
	public function __construct($name, $category, $value, $userValue = null, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $id = null, $ACLLevel = 'admin', $class = '', $disabled = false, $rows = null){
		if (!empty($rows)) $this->rows = (int)$rows;
		parent::__construct($name, $this->type, $category, $value, $label, $placeholder, null, $help, $pattern, $userValue, $important, $id, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue
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
			if ($pattern->getMinValue() > 0)    $displayPattern .= 'min="'.$pattern->getMinValue().'" ';
			if ($pattern->getMaxValue() > 0)    $displayPattern .= 'max="'.$pattern->getMaxValue().'" ';
			if ($pattern->getRegExp() !== null) $displayPattern .= 'pattern="'.$pattern->getRegExp().'" ';
		}
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		?>
		<div class="form-group">
			<label for="field_<?php echo $this->type; ?>_<?php echo $this->name ?>"><?php echo $this->label; ?> <?php if($this->help!= '') Help::iconHelp($this->help); ?></label>
			<textarea class="form-control<?php echo ' '.$this->class; ?>" rows="<?php echo $this->rows; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type ?>_<?php echo $this->name; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> <?php if ($this->disabled or !$enabled) echo 'disabled'; ?> <?php echo $displayPattern; ?>><?php echo $value; ?></textarea>
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
			if ($pattern->getMinValue() > 0)    $displayPattern .= 'min="'.$pattern->getMinValue().'" ';
			if ($pattern->getMaxValue() > 0)    $displayPattern .= 'max="'.$pattern->getMaxValue().'" ';
			if ($pattern->getRegExp() !== null) $displayPattern .= 'pattern="'.$pattern->getRegExp().'" ';
		}
		?>
		<div class="form-group">
			<textarea class="form-control<?php echo ' '.$this->class; ?>" rows="<?php echo $this->rows; ?>" id="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> <?php if ($this->disabled) echo 'disabled'; ?> <?php echo $displayPattern; ?>><?php echo $value; ?></textarea>
		</div>
		<?php
	}
} 