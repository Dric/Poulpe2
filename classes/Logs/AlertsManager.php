<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 04/04/14
 * Time: 14:42
 */
namespace Logs;

use Sanitize;

class AlertsManager {
	static protected $alerts = array();

	/**
	 * Tableau des typesLabels d'alertes autorisés
	 *
	 * Ces typesLabels d'alertes reprennent les typesLabels d'alerte de Bootstrap (sauf 'debug' et 'error' qui est remappé sur 'danger')
	 * @var array
	 */
	protected static $allowedTypes = array('success', 'warning', 'info', 'danger', 'error', 'debug');

	/**
	 * Retourne ou affiche les alertes générées
	 *
	 * @param string $type Type d'alerte à afficher
	 * @param string $format Format d'affichage (js ou html)
	 *
	 * @return void
	 */
	static public function getAlerts($type = '', $format = 'js'){
		if (!empty($type)){
			foreach (self::$alerts[$type] as $alert){
				self::displayAlert($alert, $format);
			}
		}else{
			foreach (self::$alerts as $type => $typeAlerts){
				if ((!DEBUG and $type != 'debug') or DEBUG){
					foreach ($typeAlerts as $alert){
						self::displayAlert($alert, $format);
					}
				}
			}
		}
		if ($format == 'js') echo '</script>'.PHP_EOL;
	}

	public static function displayAlert(Alert $alert, $format = 'js'){
		if ($format == 'js') echo '<script>'.PHP_EOL;
		$type = $alert->getType();
		if ($format == 'js'){
			if ($alert->getType() == 'danger') $type = 'error';
			if ($alert->getType() == 'warning') $type = 'notice';
			if ($alert->getType() == 'debug') $type = 'info';
			?>
			$.pnotify({
				<?php if ($alert->getTitle() != '') { ?>
				title: '<?php echo $alert->getTitle(); ?>',
				<?php } ?>
				<?php if ($alert->getType() == 'debug'){ ?>
				addclass: "stack-bottomright",
				stack: stack_bottomright,
				<?php } ?>
				type: '<?php echo $type; ?>',
				text: '<?php echo Sanitize::SanitizeForJs($alert->getContent()); ?>'
			});
			<?php
		}else{
			if ($alert->getType() == 'error') $type = 'danger';
			if ($alert->getType() == 'debug') $type = 'info';
			?>
			<div class="alert alert-<?php echo $type; ?>">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<?php if ($alert->getTitle() != '') { ?>
					<h3><?php echo $alert->getTitle(); ?></h3>
				<?php } ?>
				<?php echo strtoupper($alert->getType()).' : '.str_replace('\'', '&quote;', $alert->getContent()); ?>
			</div>
			<?php
		}
	if ($format == 'js') echo '</script>'.PHP_EOL;
	}

	/**
	 * @return array
	 */
	public static function getAllowedTypes() {
		return self::$allowedTypes;
	}

	public static function addToAlerts(Alert $alert){
		self::$alerts[$alert->getType()][] = $alert;
	}

	public static function removeAlert(Alert $alert){
		unset(self::$alerts[$alert->getType()][array_search($alert, self::$alerts, true)]);
	}

	public static function debug(){
		global $db, $classesUsed;
		new Alert('debug', '<code>Db->getQueriesCount</code> : <strong>'.$db->getQueriesCount().'</strong> requête(s) SQL effectuées.');
		new Alert('debug', '<code>PHP</code> : Mémoire utilisée : <ul><li>Script :  <strong>'.Sanitize::readableFileSize(memory_get_usage()).'</strong></li><li>Total :   <strong>'.Sanitize::readableFileSize(memory_get_usage(true)).'</strong></li></ul>');
		if (DETAILED_DEBUG){
			$classesDisplay = '<ul>';
			asort($classesUsed);
			$classes = array();
			foreach ($classesUsed as $classUsed){
				$tab = explode('\\', $classUsed, 2);
				if (count($tab) > 1){
					$classes[$tab[0]][] = $tab[1];
				}else{
					$classes['AAAA'][] = $classUsed;
				}
			};
			ksort($classes);
			foreach ($classes as $key => $nameSpace){
				if ($key != 'AAAA'){
					$classesDisplay .= '<li>'.$key.'<ul>';
					foreach ($nameSpace as $class){
						$classesDisplay .= '<li>'.str_replace('\\', '\\\\', $class).'</li>';
					}
					$classesDisplay .= '</ul></li>';
				}else{
					foreach ($nameSpace as $class){
						$classesDisplay .= '<li>'.str_replace('\\', '\\\\', $class).'</li>';
					}
				}
			}
			$classesDisplay .= '</ul>';
			new Alert('debug', '<code>Classes chargées</code> : '.$classesDisplay);
		}
	}
} 