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

class LinkButton extends Button{

	protected $type = 'linkButton';

	/**
	 * Affichage du champ
	 *
	 * @param bool   $enabled Champ modifiable
	 */
	public function display($enabled = true){
		?>
		<a class="btn btn-default<?php echo ' '.$this->class; ?>" id="<?php echo $this->name; ?>"  <?php if($this->help != '') echo 'title="'.$this->help.'"'; ?> name="<?php echo $this->name; ?>" href="<?php echo $this->value; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>><?php echo $this->label; ?></a>
		<?php
	}

} 