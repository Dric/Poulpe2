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
use Forms\JSSwitch;
use Forms\Pattern;

/**
 * Champ pour les booléens (checkbox)
 *
 * @package Forms\Fields
 */
class Bool extends Field{

	protected $type = 'bool';
	/**
	 * Switch Javascript pour la mise en forme de la checkbox
	 * @var \Forms\JSSwitch
	 */
	protected $switch = null;

	/**
	 * Déclaration d'un champ de saisie Texte
	 *
	 * @param string    $name         Nom du champ
	 * @param string    $category     Catégorie du champ (global ou user)
	 * @param string    $value        Valeur du champ
	 * @param string    $userValue    Valeur utilisateur du champ (facultatif)
	 * @param string    $label        Intitulé du champ (facultatif)
	 * @param string    $help         Message d'aide affiché en infobulle (facultatif)
	 * @param Pattern   $pattern      Paramètres de validation (facultatif)
	 * @param bool      $important    Le champ est marqué comme étant important (facultatif)
	 * @param string    $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string    $class        Classe CSS à ajouter au champ (facultatif)
	 * @param bool      $disabled     Champ désactivé (facultatif)
	 * @param JSSwitch  $switch       Paramètres de switch
	 */
	public function __construct($name, $category, $value, $userValue = null, $label = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $switch = null){
		$this->switch = $switch;
		parent::__construct($name, $this->type, $category, $value, $label, null, null, $help, $pattern, $userValue, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Applique la valeur de l'utilisateur
	 */
	public function display($enabled = true, $userValue = false){
		// Gestion des Switchs
		$dataAttr = '';
		if (!empty($this->switch)){
			/**
			 * @var JSSwitch $switch
			 */
			$switch = $this->switch;
			$dataAttr .= 'data-on-text="'.$switch->getOnText().'" data-off-text="'.$switch->getOffText().'" data-on-color="'.$switch->getOnColor().'" data-off-color="'.$switch->getOffColor().'" data-size="'.$switch->getSize().'"';
		}
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		if (!empty($this->switch) and $this->switch->getlabelPosition() == 'left'){
			?>
			<div class="form-group <?php if ($this->important) echo 'has-warning'; ?>">
				<label for="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>">
					<?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help != '') Help::iconHelp($this->help); ?>
				</label>
				<input type="checkbox" class="form-control <?php if (!empty($this->switch)) echo 'checkboxSwitch'; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_checkbox" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_checkbox" value="1" <?php if ($value === true or $value == 'true') echo 'checked'; ?> <?php if (!empty($dataAttr)) echo $dataAttr; ?> <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>>
				<input type="hidden" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_hidden" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_hidden" value="0" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>>
				<div class="help-block with-errors"></div>
			</div>
		<?php
		}else{
			?>
			<div class="checkbox <?php if ($this->important) echo 'has-warning'; ?>">
				<label>
					<input type="checkbox" class="<?php if (!empty($this->switch)) echo 'checkboxSwitch'; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_checkbox" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_checkbox" value="1" <?php if ($value === true or $value == 'true') echo 'checked'; ?> <?php if (!empty($dataAttr)) echo $dataAttr; ?> <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>>
					<input type="hidden" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_hidden" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>_hidden" value="0" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>>
					<?php echo $this->label; ?> <?php if($this->help != '') Help::iconHelp($this->help); ?>
				</label>
				<div class="help-block with-errors"></div>
			</div>
		<?php
		}
	}

	/**
	 * Affichage du champ dans une table de bdd
	 *
	 * @param string      $tableName  Nom de la table
	 * @param int|string  $rowId      ID de la ligne
	 * @param mixed       $value      Valeur du champ
	 */
	public function tableItemDisplay($tableName, $rowId, $value = null){
		// Gestion des Switchs
		$dataAttr = '';
		if (!empty($this->switch)){
			/**
			 * @var JSSwitch $switch
			 */
			$switch = $this->switch;
			$dataAttr .= 'data-on-text="'.$switch->getOnText().'" data-off-text="'.$switch->getOffText().'" data-on-color="'.$switch->getOnColor().'" data-off-color="'.$switch->getOffColor().'" data-size="'.$switch->getSize().'"';
		}
		$value = ($value == 1 or $value == 'true' or $value == true) ? true : false;
		?>
		<input type="checkbox" class="<?php if (!empty($this->switch)) echo 'checkboxSwitch'; ?>" id="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>_checkbox" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>_checkbox" value="1" <?php if ($value) echo 'checked'; ?> <?php if (!empty($dataAttr)) echo $dataAttr; ?> <?php if ($this->disabled) echo 'disabled'; ?>>
		<input type="hidden" id="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>_hidden" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>_hidden" value="0" <?php if ($this->disabled) echo 'disabled'; ?>>

		<?php
	}
} 