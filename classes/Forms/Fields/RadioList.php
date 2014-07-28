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

/**
 * Champ permettant de choisir une valeur dans une liste via des boutons radio
 *
 * On peut utiliser une simple liste dans un champ `select`, mais les boutons radio permettent d'afficher simultanément tous les choix.
 *
 * @package Forms\Fields
 */
class RadioList extends Field{

	protected $type = 'radioList';
	protected $htmlType = 'radio';

	/**
	 * Déclaration d'une liste de checkboxes
	 *
	 * @param string  $name           Nom du champ
	 * @param string  $value          Valeur du champ
	 * @param string  $label          Intitulé du champ (facultatif)
	 * @param string  $help           Message d'aide affiché en infobulle (facultatif)
	 * @param bool    $important      Le champ est marqué comme étant important (facultatif)
	 * @param string  $ACLLevel       Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string  $class          Classe CSS à ajouter au champ (facultatif)
	 * @param bool    $disabled       Champ désactivé (facultatif)
	 * @param array   $choices        Choix possibles dans la liste sous forme de tableau associatif `valeur => libellé`
	 */
	public function __construct($name, $value, $label = null, $help = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $choices = null){
		$this->choices = (array)$choices;
		parent::__construct($name, $this->type, $value, $label, null, $help, null, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Afficher la valeur de l'utilisateur au lieu de la valeur globale
	 */
	public function display($enabled = true, $userValue = false){
		$i = 0;
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		?><label><?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help != '') Help::iconHelp($this->help); ?></label><?php
		foreach ($this->choices as $choice => $label){
			$i++;
			?>
			<div class="<?php echo $this->htmlType.' '.$this->class; ?>" id="<?php echo $this->type.'_'.$choice; ?>">
				<label>
					<input id="field_<?php echo $this->type; ?>_<?php echo $this->name.'_'.$i; ?>" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?><?php if ($this->htmlType == 'checkbox') echo '[]'; ?>" type="<?php echo $this->htmlType; ?>" value="<?php echo $choice; ?>" <?php if ($choice == $value) echo 'checked'; ?> <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>>
					<?php echo $label; ?>
				</label>
				<div class="help-block with-errors"></div>
			</div>
			<?php
		}
	}
} 