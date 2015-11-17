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
		<span class="fa fa-question-circle tooltip-<?php echo $tooltipPosition; ?> help-icon" title="<?php echo $text; ?>"></span>
		<?php
	}

	/**
	 * Affichage d'une icône d'alerte
	 *
	 * Le contenu de l'alerte est affiché dans une infobulle
	 *
	 * @param string $text
	 * @param string $tooltipPosition
	 *  Ce paramètre peut prendre les valeurs suivantes
	 *  - bottom
	 *  - top
	 *  - left
	 *  - right
	 */
	public static function iconWarning($text, $tooltipPosition = 'bottom'){
		?>
		<span class="fa fa-warning tooltip-<?php echo $tooltipPosition; ?> error-icon" title="<?php echo $text; ?>"></span>
	<?php
	}

	/**
	 * Affichage d'une icône
	 *
	 * Le contenu est affiché dans une infobulle
	 *
	 * @param string $icon Classe de l'icône de Font-Awesome, sans le préfixe `fa-`
	 * @param string $color Couleur de l'icône. peut prendre les valeurs suivantes : `success`, `info`, `warning`, `error`
	 * @param string $text
	 * @param string $tooltipPosition
	 *  Ce paramètre peut prendre les valeurs suivantes
	 *  - bottom
	 *  - top
	 *  - left
	 *  - right
	 */
	public static function icon($icon, $color, $text, $tooltipPosition = 'bottom'){
		?>
		<span class="fa fa-<?php echo $icon; ?> tooltip-<?php echo $tooltipPosition; ?> <?php echo $color; ?>-icon" title="<?php echo $text; ?>"></span>
		<?php
	}
} 