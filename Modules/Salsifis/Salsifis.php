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
				<h4>Les salsifis sont fièrement à leur poste depuis <?php echo $this->getUptime(); ?></h4>
				<br>
				<?php $this->serverStatus(); ?>
				<?php $this->processes(); ?>
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
		$days = floor($uptime/60/60/24);
		$hours = $uptime/60/60%24;
		$mins = $uptime/60%60;
		$secs = $uptime%60;
		$ret = '';
		if ($days > 0){
			$ret .= $days.' jour';
			if ($days > 1){
				$ret .='s';
			}
			$ret .= ' ';
		}
		if ($hours > 0){
			$ret .= $hours.' heure';
			if ($hours > 1){
				$ret .='s';
			}
			$ret .= ' ';
		}
		if ($mins > 0){
			$ret .= $mins.' minute';
			if ($mins > 1){
				$ret .='s';
			}
			$ret .= ' ';
		}
		if ($secs > 0){
			$ret .= 'et '.$secs.' seconde';
			if ($secs > 1){
				$ret .='s';
			}
		}
		return $ret;
	}

	/**
	 * Retourne un objet contenant l'utilisation en mémoire vive du serveur
	 * @return serverUsage
	 */
	protected function getServerMemory(){
		$free = (string)trim(shell_exec('free'));
		$freeArr = explode("\n", $free);
		$mem = explode(" ", $freeArr[1]);
		$mem = array_filter($mem);
		$mem = array_merge($mem);
		return new serverUsage((($mem[1]-$mem[2])*1024), ($mem[1]*1024), ($mem[2]/$mem[1]*100), true);
	}

	/**
	 * Retourne un objet contenant l'utilisation CPU du serveur
	 * @return serverUsage
	 */
	protected function getServerCPU(){
		$load = sys_getloadavg();
		return new serverUsage((100-$load[0]), 0, $load[0], false);
	}

	/**
	 * Retourne un objet contenant l'utilisation disque du serveur
	 * @return serverUsage
	 */
	protected function getServerDisk(){
		$free = disk_free_space(".");
		$total = disk_total_space(".");
		//$used = $total - $free;
		$usedPercent = (($total - $free)/$total)*100;
		return new serverUsage($free, $total, $usedPercent, true);
	}

	/**
	 * Affiche une barre de progression
	 * @param serverUsage $data Objet contenant les données à afficher
	 *
	 */
	protected function progressBar(serverUsage $data){
		if ($data->percent > 80){
			$level = 'danger';
		}elseif($data->percent < 50){
			$level = 'success';
		}else{
			$level = 'warning';
		}
		if ($data->total > 0){
			$libelle = $data->free.' libres sur '.$data->total;
		}else{
			$libelle = $data->free.' inoccupé';
		}
		?>
		<div class="progress tooltip-bottom" title="<?php echo $libelle; ?>">
			<div class="progress-bar progress-bar-<?php echo $level; ?>" role="progressbar" aria-valuenow="<?php echo $data->percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $data->percent; ?>%">
				<?php echo round($data->percent, 1).'%'; ?>
			</div>
		</div>
	<?php
	}

	/**
	 * Affiche les ressources utilisées actuellement par le serveur
	 */
	protected function serverStatus(){
		?>
		<h3>Suivi du système</h3>
		<p>Occupation de l'espace disque :</p>
		<?php $this->progressBar($this->getServerDisk()); ?>
		<p>Occupation mémoire vive (RAM) :</p>
		<?php $this->progressBar($this->getServerMemory()); ?>
		<p>Occupation du système :</p>
		<?php $this->progressBar($this->getServerCPU()); ?>
	<?php
	}
} 