<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 31/07/14
 * Time: 14:15
 */

namespace Modules\Salsifis;


use Admin\serverUsage;
use Components\Item;
use Components\serverResource;
use Front;
use Modules\Module;
use Modules\ModulesManagement;

class Salsifis extends Module{
	protected $name = 'Serveur des Salsifis';
	protected $title = 'One salsify a day doesn\'t keep anything away !';



	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		//Front::$mainMenu->add(new Item('shutdown', 'Arrêt/redémarrage', MODULE_URL.end(explode('\\', get_class())), 'Arrêter ou redémarrer le serveur'));
	}

	/**
	 * Installe le module
	 */
	public function install(){
		// Définition des ACL par défaut pour ce module
		$defaultACL = array(
			'type'  => 'access',
			'value' => true
		);
		return ModulesManagement::installModule($this, $defaultACL);
	}

	/**
	 * Affichage principal
	 */
	public function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h3><?php echo $this->title; ?>  <?php $this->manageModuleButtons(); ?></h3>
				</div>
				<h4>Les salsifis sont fièrement à leur poste depuis <?php echo $this->getUptime(); ?> !</h4>
				<div class="row">
					<div class="col-md-6">
						<?php $this->serverStatus(); ?>
					</div>
					<div class="col-md-6">
						<?php $this->processes(); ?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/********* Méthodes spécifiques au module ***********/

	/**
	 * Suivi de l'état des processus importants pour le serveur média
	 */
	protected function processes(){
		$transbt  = $this->isProcessRunning('transmission-da');
		$minidlna = $this->isProcessRunning('minidlna');
		$samba    = $this->isProcessRunning('smbd');
		?>
		<h3>Suivi des services</h3>
		<table class="table">
			<tr>
				<td>Transmission</td>
				<td>
					<abbr class="tooltip-bottom" title="Bitorrent est un protocole de téléchargement. Il est zieuté par Hadopi (ou ce qu'il en reste), aussi n'utilisez que des trackers privés !">bittorrent</abbr>
				</td>
				<td>
					<?php echo '<span class="label label-'.(($transbt) ? 'success' : 'danger').'">'.(($transbt) ? 'Lancé' : 'Stoppé').'</span>'; ?>
				</td>
			</tr>
			<tr>
				<td>MiniDLNA</td>
				<td>
					<abbr class="tooltip-bottom" title="Si votre téléviseur, votre décodeur, votre smartphone/tablette ou votre box ADSL sont compatibles avec la norme DLNA, vous pourrez lire vos films, musiques et photos directement depuis ceux-ci.">serveur média</abbr>
				</td>
				<td>
					<?php echo '<span class="label label-'.(($minidlna) ? 'success' : 'danger').'">'.(($minidlna) ? 'Lancé' : 'Stoppé').'</span>'; ?>
				</td>
			</tr>
			<tr>
				<td>Samba</td>
				<td>
					<abbr class="tooltip-bottom" title="Pour pouvoir accéder à vos fichiers depuis Windows">partage de fichiers</abbr>
				</td>
				<td>
					<?php echo '<span class="label label-'.(($samba) ? 'success' : 'danger').'">'.(($samba) ? 'Lancé' : 'Stoppé').'</span>'; ?>
				</td>
			</tr>
		</table>

	<?php
	}

	/**
	 * Retourne l'état d'un processus
	 * @param string $process
	 *
	 * @return bool
	 */
	protected function isProcessRunning($process){
		exec("pgrep ".$process, $output, $return);
		if ($return == 0) {
			return true;
		}
		return false;
	}
	/**
	 * Retourne le temps d'uptime
	 * @return string
	 */
	protected function getUptime(){
		$uptime = shell_exec("cut -d. -f1 /proc/uptime");
		return \Sanitize::timeDuration($uptime);
	}

	/**
	 * Affiche les ressources utilisées actuellement par le serveur
	 */
	protected function serverStatus(){
		?>
		<h3>Suivi du système</h3>
		<?php
		$serverDisks  = new serverResource('disk');
		$serverMemory = new serverResource('mem');
		$serverCpu    = new serverResource('cpu');
		?>
		<p>Occupation de l'espace disque :</p>
		<?php $serverDisks->displayBar(); ?>
		<p>Occupation mémoire vive (RAM) :</p>
		<?php $serverMemory->displayBar(); ?>
		<p>Occupation CPU :</p>
		<?php $serverCpu->displayBar(); ?>
		<?php
	}
} 