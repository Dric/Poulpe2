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
 * Champ spécial qui affiche un lien hypertexte ressemblant à un bouton
 *
 * @package Forms\Fields
 */
class LinkButton extends Button{

	/** @var string Type de champ (pour sauvegarde) */
	protected $type = 'linkButton';

	/**
	 * Affichage du champ
	 *
	 * @param bool    $enabled    Champ modifiable
	 * @param bool    $userValue  Applique la valeur de l'utilisateur
	 * @param string  $attrs      Attributs html à ajouter au champ de saisie
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
		?>
		<a class="btn btn-default<?php echo ' '.$this->class; ?>" id="<?php echo $this->name; ?>"  <?php if($this->help != '') echo 'title="'.$this->help.'"'; ?> name="<?php echo $this->name; ?>" href="<?php echo $this->value; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>><?php echo $this->label; ?></a>
		<?php
	}

} 