<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 08:58
 */

namespace Forms\Fields;


use Components\Help;
use Forms\Pattern;

/**
 * Champ de saisie de mot de passe
 *
 * Si javascript est activé, un bouton à côté du champ permet d'afficher le mot de passe en clair.
 * Cette fonctionnalité n'est pas compatible avec les versions d'Internet Explorer antérieures à la 9.
 *
 * @package Forms\Fields
 */
class Password extends String{

	protected $htmlType = 'password';

	/**
	 * Affichage du champ
	 *
	 * @param bool   $enabled Champ modifiable
	 * @param bool   $userValue Afficher les valeurs utilisateurs au lieu des valeurs globales
	 * @param string $attrs Attributs optionnels à ajouter au champ
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
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
		<div class="form-group <?php if ($this->important) echo 'has-warning'; ?>">
			<label for="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>"><?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help != '') Help::iconHelp($this->help); ?></label>
			<div class="input-group">
				<input type="<?php echo $this->htmlType;?>" class="form-control<?php echo ' '.$this->class; ?> pwd" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> value="<?php echo $value; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?> <?php echo $attrs; ?> <?php echo $displayPattern; ?>>
				<span class="input-group-btn">
					<button class="btn btn-default reveal tooltip-bottom<?php echo ' '.$this->class; ?>" title="Afficher les caractères" type="button"><i class="glyphicon glyphicon-eye-open"></i></button>
				</span>
			</div>
			<div class="help-block with-errors"></div>
		</div>
	<?php
	}
} 