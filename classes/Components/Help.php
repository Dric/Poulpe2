<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/04/14
 * Time: 11:27
 */

namespace Components;

/**
 * Classe de gestion de l'aide dans Poulpe2
 *
 * @package Components
 */
class Help {

	/**
	 * Affichage d'une icône d'aide
	 *
	 * Le contenu de l'aide est affiché dans une infobulle
	 *
	 * @param string $text
	 * @param string $tooltipPosition
	 *  Ce paramètre peut prendre les valeurs suivantes
	 *  - bottom
	 *  - top
	 *  - left
	 *  - right
	 */
	public static function iconHelp($text, $tooltipPosition = 'bottom'){
		?>
		<span class="glyphicon glyphicon-question-sign tooltip-<?php echo $tooltipPosition; ?> help-icon" title="<?php echo $text; ?>"></span>
		<?php
	}
} 