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
 * Champ de formulaire masqué
 *
 * @package Forms\Fields
 */
class Hidden extends Field{

	protected $type = 'hidden';
	protected $htmlType = 'hidden';

	/**
	 * Déclaration d'un champ de saisie Texte
	 *
	 * @param string  $name         Nom du champ
	 * @param string  $category     Catégorie du champ (global ou user)
	 * @param string  $value        Valeur du champ
	 * @param string  $userValue    Valeur utilisateur du champ (facultatif)
	 * @param string  $ACLLevel     Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param bool    $disabled     Champ désactivé (facultatif)
	 */
	public function __construct($name, $category, $value, $userValue = null, $ACLLevel = 'admin', $disabled = false){
		parent::__construct($name, $this->type, $category, $value, null, null, null, null, null, $userValue, false, $ACLLevel, null, $disabled);
	}

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
		<input type="<?php echo $this->htmlType;?>" class="form-control<?php echo ' '.$this->class; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> value="<?php echo $value; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?> <?php echo $attrs; ?> <?php echo $displayPattern; ?>>
		<?php
	}
} 