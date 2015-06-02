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
use Front;

/**
 * Champ de saisie de texte long (textarea)
 *
 * @package Forms\Fields
 */
class Text extends Field{

	protected $type = 'text';
	/**
	 * Nombre de lignes affichées par le champ (hauteur du champ)
	 * @var int
	 */
	protected $rows = 5;

	/**
	 * Déclaration d'un champ de saisie Texte
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
	 * @param bool    $rows         Nombre de lignes du champ (facultatif)
	 */
	public function __construct($name, $value, $label = null, $placeholder = null, $help = null, $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $rows = null){
		if (!empty($rows)) $this->rows = (int)$rows;
		// Hauteur automatiquement adaptée au contenu
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/autosize.min.js"></script>');
		Front::setJsFooter('<script>autosize($(\'textarea\'));</script>');
		// Avec les onglets, les textarea ne sont pas forcément affichés dès le départ, ce qui empêche `autosize` de bien calculer leur hauteur initiale. Il faut donc mettre à jour la hauteur lors du changement d'onglet
		Front::setJsFooter('<script>$(\'a[data-toggle="tab"]\').on(\'shown.bs.tab\', function (e) {autosize.update($(\'textarea\'));});</script>');
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
			if ($pattern->getMinValue() > 0)    $displayPattern .= 'min="'.$pattern->getMinValue().'" ';
			if ($pattern->getMaxValue() > 0)    $displayPattern .= 'max="'.$pattern->getMaxValue().'" ';
			if ($pattern->getRegExp() !== null) $displayPattern .= 'pattern="'.$pattern->getRegExp().'" ';
		}
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		?>
		<div class="form-group">
			<label for="field_<?php echo $this->type; ?>_<?php echo $this->name ?>"><?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help!= '') Help::iconHelp($this->help); ?></label>
			<textarea class="form-control<?php echo ' '.$this->class; ?>" rows="<?php echo $this->rows; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type ?>_<?php echo $this->name; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> <?php if ($this->disabled or !$enabled) echo 'disabled'; ?> <?php echo $displayPattern; ?>><?php echo $value; ?></textarea>
			<div class="help-block with-errors"></div>
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