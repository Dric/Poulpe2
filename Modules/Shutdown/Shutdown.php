<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 31/07/14
 * Time: 10:14
 */

namespace Modules\Shutdown;


use Components\Item;
use FileSystem\Fs;
use Forms\Fields\Button;
use Forms\Form;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Users\ACL;

/**
 * Module d'arrêt-relance du système
 *
 * @link <http://yatb.giacomodrago.com/en/post/10/shutdown-linux-system-from-within-php-script.html>
 *
 * @package Modules\Shutdown
 */
class Shutdown extends Module{
	protected $name = 'Extinction/Redémarrage';
	protected $title = 'Eteindre ou redémarrer le serveur';



	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('shutdown', 'Arrêt/redémarrage', Front::getModuleUrl().end($module), 'Arrêter ou redémarrer le serveur'));
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
		$disabled = !$this->checkFiles();
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>Vous pouvez arrêter ou redémarrer le serveur à partir d'ici.</p>
				<div class="alert alert-danger">
					<?php if (!$disabled){ ?>
					Vérifiez qu'aucune opération n'est en cours sur ce serveur, sans quoi vous risquez fortement de perdre des données !
					<?php }else{ ?>
					Ce module nécessite les opérations suivantes :
					<ol>
						<li>Déplacez les fichiers <code>shutdown_suid</code> et <code>reboot_suid</code> dans <code>/usr/local/bin</code> : <br><code>sudo mv <?php echo Front::getAbsolutePath(); ?>/Modules/Shutdown/*_suid /usr/local/bin</code></li>
						<li>Changez le propriétaire de ces fichiers par <code>root</code> : <br><code>sudo chown root:root /usr/local/bin/*_suid</code></li>
						<li>Changez les permissions sur ces fichiers :<br><code>sudo chmod 4755 /usr/local/bin/*_suid</code></li>
					</ol>
					<?php } ?>
				</div>
				<?php
				$form = new Form('Shutdown', null, null, 'module', $this->id);
				$form->addField(new Button('page', 'shutdown', 'Arrêter le serveur', 'admin', 'btn-lg btn-danger', $disabled));
				$form->addField(new Button('page', 'reboot', 'Redémarrer le serveur', 'admin', 'btn-lg btn-danger', $disabled));
				?>
				<div class="text-center">
					<?php $form->display(); ?>
				</div>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Vérifie que tout est en place pour permettre l'arrêt ou le redémarrage du serveur
	 * @return bool
	 */
	protected function checkFiles(){
		$fs = new Fs('/usr/local/bin/');
		if ($fs->fileExists('shutdown_suid') === false){
			new Alert('error', 'Le fichier permettant l\'arrêt du serveur n\'est pas dans <code>/usr/local/bin</code> !');
			return false;
		}
		if ($fs->fileExists('reboot_suid') === false){
			new Alert('error', 'Le fichier permettant le redémarrage du serveur n\'est pas dans <code>/usr/local/bin</code> !');
			return false;
		}
		$shutdownMeta = $fs->getFileMeta('shutdown_suid', array('chmod', 'owner'));
		if ($shutdownMeta->advChmod != 4755){
			new Alert('error', 'Le fichier permettant l\'arrêt du serveur n\'a pas les bonnes permissions : <code>'.$shutdownMeta->advChmod.'</code> au lieu de <code>4755</code> !');
			return false;
		}
		$rebootMeta = $fs->getFileMeta('reboot_suid', array('chmod', 'owner'));
		if ($rebootMeta->advChmod != 4755){
			new Alert('error', 'Le fichier permettant le redémarrage du serveur n\'a pas les bonnes permissions : <code>'.$rebootMeta->advChmod.'</code> au lieu de <code>4755</code> !');
			return false;
		}
		if ($shutdownMeta->owner != 'root'){
			new Alert('error', 'Le fichier permettant l\'arrêt du du serveur n\'a pas le bon propriétaire : <code>'.$shutdownMeta->owner.'</code> au lieu de <code>root</code> !');
			return false;
		}
		if ($rebootMeta->owner != 'root'){
			new Alert('error', 'Le fichier permettant le redémarrage du serveur n\'a pas le bon propriétaire : <code>'.$rebootMeta->owner.'</code> au lieu de <code>root</code> !');
			return false;
		}
		return true;
	}
	/**
	 * Redémarrage du serveur
	 */
	protected function moduleReboot(){
		if (!ACL::canAdmin('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		if (!$this->checkFiles()){
			return false;
		}
		$server = rtrim($_SERVER['HTTP_HOST'], '/');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<div class="jumbotron" style="font-size: 2em">
					<h2 class="text-danger text-center">Redémarrage en cours !</h2>
				</div>
				<p>Le redémarrage ne devrait pas excéder 5 minutes.<br />Si ce délai est dépassé et que vous n'arrivez toujours pas à accéder à votre serveur, il y a de fortes chances pour que quelque chose cloche.</p>
				<p>Cliquez sur le lien ci-dessous pour retourner à la page d'accueil. Vous aurez une erreur tant que le serveur n'aura pas redémarré.<br />
					Il vous suffit d'actualiser la page (<kbd>F5</kbd> sur un PC) régulièrement pour que l'interface apparaisse une fois le serveur redémarré.</p>
				<div class="text-center">
					<a class="btn btn-lg btn-primary" title="Cliquez ici et soyez patient !" href=".">Revenir à la page d'accueil</a>
				</div>
			</div>
		</div>
		<?php
		exec("/usr/local/bin/reboot_suid");
	}

	/**
	 * Arrêt du serveur
	 */
	protected function moduleShutdown(){
		if (!ACL::canAdmin('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		if (!$this->checkFiles()){
			return false;
		}
		$server = rtrim($_SERVER['HTTP_HOST'], '/');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<div class="jumbotron">
					<h2 class="text-danger text-center" style="font-size: 2em" >Arrêt en cours !</h2>
				</div>
				<p>Votre serveur va s'arrêter. Vous devrez appuyer sur le bouton d'alimentation de la machine pour la redémarrer.</p>
				<p>Vous pourrez ensuite vous connecter sur l'interface des Salsifis avec ce lien : </p>
				<div class="text-center"><h2><strong>http://<?php echo $server; ?></strong></h2></div>
				<p class="text-center">Vous pouvez fermer cette fenêtre.</p>
			</div>
		</div>
		<?php
		exec("/usr/local/bin/shutdown_suid");
	}
} 