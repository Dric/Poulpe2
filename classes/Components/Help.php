<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/04/14
 * Time: 11:27
 */

namespace Components;


class Help {
	public static function iconHelp($text, $tooltipPosition = 'bottom'){
		?>
		<span class="glyphicon glyphicon-question-sign tooltip-<?php echo $tooltipPosition; ?> help-icon" title="<?php echo $text; ?>"></span>
		<?php
	}
} 