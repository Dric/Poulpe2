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
 * Bouton de formulaire
 *
 * @package Forms\Fields
 */
class Button extends Field{

	/** @var string Type de champ */
	protected $type = 'button';

	/**
	 * Déclaration d'un bouton
	 *
	 * @param string    $name           Nom du champ
	 * @param string    $value          Valeur du champ
	 * @param string    $label          Intitulé du champ (facultatif)
	 * @param string    $ACLLevel       Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string    $class          Classe CSS à ajouter au champ (facultatif)
	 * @param bool      $disabled       Champ désactivé (facultatif)
	 */
	public function __construct($name, $value, $label = null, $ACLLevel = 'admin', $class = '', $disabled = false){
		parent::__construct($name, $this->type, $value, $label, null, null, null, true, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool    $enabled Champ modifiable
	 * @param bool    $userValue  Applique la valeur de l'utilisateur
	 * @param string  $attrs      Attributs html à ajouter au champ de saisie
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
		?>
		<button type="submit" class="btn btn-default<?php echo ' '.$this->class; ?>" id="<?php echo $this->name; ?>" name="<?php echo $this->name; ?>" value="<?php echo $this->value; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>><?php echo $this->label; ?></button>
		<?php
	}

} 