<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 23/04/14
 * Time: 12:23
 */

namespace Modules\DenyAppAccess;


use Components\Item;
use Components\Menu;
use Db\DbFieldSettings;
use Db\DbTable;
use FileSystem\Fs;
use Forms\Fields\Bool;
use Forms\Fields\Button;
use Forms\Fields\Int;
use Forms\Fields\String;
use Forms\Fields\Table;
use Forms\Fields\Text;
use Forms\JSSwitch;
use Front;
use Logs\Alert;
use Logs\EventLog;
use Logs\EventsManager;
use Modules\Module;
use Modules\ModulesManagement;
use Sanitize;
use Forms\Field;
use Forms\Form;
use Forms\PostedData;
use Users\ACL;

/**
 * Class DenyAppAccess
 *
 * Permet d'empêcher l'accès à une application via un paramètre changé dans un script vbs qui appelle l'application à bloquer.
 *
 * @package Modules\DenyAppAccess
 */
class DenyAppAccess extends Module{

	protected $name = 'Accès aux applications';
	protected $title = 'Empêcher l\'accès à une application Citrix';

	/**
	 * Liste des applications
	 * @var array
	 */
	protected $apps = array();

	public function __construct(){
		// On appelle la méthode de la classe Module
		parent::__construct();

		// Passons maintenant au module proprement dit
		$this->populateApps();
	}

	/**
	 * Installe le module en bdd, avec ses paramètres
	 */
	public function install(){
		// Définition des ACL par défaut pour ce module
		$defaultACL = array(
		  'type'  => 'modify',
		  'value' => true
		);

		return ModulesManagement::installModule($this, $defaultACL);
	}

	/**
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		$applis = new DbTable('module_applis', 'Liste des applications');
		$applis->addField(new Int('id', null, 'ID de l\'application', null, null, new DbFieldSettings('number', true, 11, 'primary', true, true, 0, null, false, false)));
		$applis->addField(new String('title', null, 'Nom affiché dans le menu', null, null, new DbFieldSettings('text', true, 150, 'unique', true, false, 0, null, true)));
		$applis->addField(new String('file', null, 'Script VBS', null, null, new DbFieldSettings('text', true, 255, false, false, false, 0, null, true)));
		$this->dbTables['module_applis'] = $applis;
		$this->settings['vbsPath'] = new String('vbsPath', '\\\\intra.epsi.fr\profils\xen\xenlogin\scripts', 'Chemin des scripts de lancement VBS', '\\\\intra.epsi.fr\profils\xen\xenlogin\scripts', null, null, true);
		// Cette table sera gérée via les paramètres
		$this->settings['module_applis'] = new Table($applis);
	}

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('acces', 'Accès aux applications', '?module='.end($module), 'Empêcher les utilisateurs d\'accéder à certaines applications', null, null));
	}

	public function display(){
		if (!$this->getPage()){
			if (isset($_REQUEST['app'])){
				$appR = htmlspecialchars($_REQUEST['app']);
				if (array_key_exists($appR, $this->apps)){
					/**
					 * @var App $app
					 */
					$app = $this->apps[$appR];
					$this->breadCrumb['children'] = array(
						'title' => $app->getTitle(),
						'link'  => $this->url.'&app='.$app->getName()
					);
					Front::displayBreadCrumb($this->breadCrumb);
					$this->displayApp($app);
					return 0;
				}else{
					new Alert('error', 'L\'application demandée n\'existe pas !');
				}
			}
			Front::displayBreadCrumb($this->breadCrumb);
			$this->mainDisplay();
		}
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		global $db;
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Gestion des accès applicatifs <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					Certaines applications sont lancées via des scripts VBS pour permettre en cas de mise à jour ou de maintenance applicative d'empêcher les utilisateurs de s'y connecter.<br />
					Cependant tout le monde n'est pas à l'aise avec les scripts VBS, d'autant qu'une fausse manip' plante tout le script avec un vilain message d'erreur.<br />
					C'est pourquoi ce petit module vous permet de gérer l'accès aux applications : vous cochez/décochez une case, vous saisissez un message de notification pour les utilisateurs, et une armée de lutins fera tout le sale boulot en allant éditer pour vous ces fichiers de script.<br /><br />
					Rappel : il est possible de passer outre l'interdiction de lancer l'application en ajoutant l'argument <code>admin</code> dans le raccourci de lancement du script, afin de tester l'appli sans pour autant la rendre disponible à tout le monde.
				</p>
				<?php $this->displayAppList(); ?>
				<h3>Logs de la gestion d'accès</h3>
				<?php
				$module = explode('\\', get_class());
				$events = EventsManager::displayLogs(null, null, end($module), null, true);
				if (!empty($events) or !is_bool($events)){
					$usersList = array();
					$usersDb = $db->get('users', array('id', 'name'));
					foreach ($usersDb as $user){
						$usersList[$user->id] = $user->name;
					}
					?>
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Horodatage</th>
								<th>Utilisateur</th>
								<th>Action</th>
								<th>Application</th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ($events as $event){
							?>
							<tr>
								<td><?php echo Sanitize::date($event->time, 'dateTime'); ?></td>
								<td><?php echo (isset($usersList[$event->user])) ? $usersList[$event->user] : 'Utilisateur supprimé (id <code>'.$event->user.'</code>)'; ?></td>
								<td><?php echo ($event->type == 'BLOCK') ? 'a bloqué' : 'a autorisé'; ?></td>
								<td><?php echo $this->apps[$event->data]->getTitle(); ?></td>
							</tr>
						<?php
						}
						?>
						</tbody>
					</table>
				<?php	}else{ ?>
					<div class="alert alert-info">Il n'y a aucun événement à afficher.</div>
				<?php }	?>
				</div>
			</div>
		<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Récupère la liste des applications, la met en forme, remplit toutes ses propriétés et crée les items de menus en rapport
	 */
	protected function populateApps(){
		global $db;
		$menu = new Menu($this->name, 'Applications', '', '', '');
		$menu->add(new Item('acces', 'Résumé des accès', $this->url, 'Résumé des accès aux applications et logs de modification des accès', 'book', null), 10);

		$appsDb = $db->get('module_applis');
		if (!empty($appsDb)){
			foreach ($appsDb as $app){
				$appName = Sanitize::sanitizeFilename($app->title);
				$this->apps[$appName] = new App($app->title, $app->file);
				$this->getAppStatus($this->apps[$appName]);
				if (ACL::canModify('module', $this->id)){
					$menu->add(new Item($appName, $app->title, $this->url.'&app='.$appName, 'Accès à '.$app->title, 'file'));
				}
			}
		}
		Front::setSecondaryMenus($menu);
	}

	/**
	 * Affiche la gestion de l'accès à une application
	 *
	 * @param App $app Application
	 *
	 * @return bool
	 */
	protected function displayApp(App $app){
		if (!array_key_exists($app->getName(), $this->apps)){
			new Alert('debug', '<code>DenyAppAccess->displayApp()</code> : l\'application <code>'.$app->getName().'</code> n\'existe pas !');
			return false;
		}
		// Construction du formulaire
		$form = new Form('appMaintenance', null, null, 'module', $this->id);
		$form->addField(new Bool('maintenance', $app->getMaintenance(), 'Maintenance', 'Passez la maintenance sur \'Active\' pour empêcher les utilisateurs d\'accéder à '.$app->getTitle(), null, true, 'modify', null, false, new JSSwitch('large', 'left', 'Activée', 'Désactivée', 'warning')));
		$form->addField(new Text('message', $app->getMessage(), 'Message', null, 'Saisissez le message que verront les utilisateurs en essayant de se connecter à '.$app->getTitle().' lorsque l\'accès est bloqué.', null, false, 'modify'));
		$form->addField(new Button('action', 'saveAppStatus', 'Sauvegarder', 'modify', 'btn-primary'));
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Accès à <?php echo $app->getTitle(); ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<div class="maintenanceWarning">
				<?php if ($app->getMaintenance()) { ?>
					<div class="alert alert-warning">
						La maintenance est activée !<br />
						Les utilisateurs ne peuvent pas se connecter à <?php echo $app->getTitle(); ?>.
					</div>
				<?php
					}
				?>
				</div>
				<?php
				$form->display();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Récupère le status de maintenance d'une application
	 *
	 * @param App $app Application dans la liste de $this->apps
	 * @see populateApps()
	 *
	 * @return bool
	 */
	protected function getAppStatus(App $app){
		$share = new Fs($this->settings['vbsPath']->getValue(), null, 'appaccess');
		$file = $share->readFile($app->getFile());
		if (!$file) return false;
		if (empty($file)){
			new Alert('error', 'Le fichier <code>'.$app->getFile().'</code> est vide !');
			return false;
		}
		foreach ($file as $line) {
			if (strtolower(substr($line, 0, 11)) == 'maintenance'){
				$lineTab = explode(' = ', $line);
				$app->setMaintenance($lineTab[1]);
			}elseif (strtolower(substr($line, 0, 7)) == 'message'){
				$lineTab = explode(' = ', $line);
				$message = $lineTab[1];
				$message = iconv("WINDOWS-1252", "UTF-8", $message);
				$message = str_replace(' & VbCrLf', PHP_EOL, $message);
				$message = str_replace(' & ', '', $message);
				$app->setMessage(str_replace('"', '', $message));
			}
		}
		return true;
	}

	/**
	 * Sauvegarde les modifications d'accès de l'application dans le fichier de celle-ci
	 * @param App $app Objet application à sauvegarder
	 *
	 * @return bool
	 */
	protected function saveAppFile(App $app){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$share = new Fs($this->settings['vbsPath']->getValue(), null, 'appaccess');
		$file = $share->readFile($app->getFile());
		if (!$file) return false;
		if (empty($file)){
			new Alert('error', 'Le fichier <code>'.$app->getFile().'</code> est vide !');
			return false;
		}
		foreach ($file as &$line) {
			if (strtolower(substr($line, 0, 11)) == 'maintenance'){
				$line = "Maintenance = ".((int)$app->getMaintenance());
			}elseif (strtolower(substr($line, 0, 7)) == 'message'){
				$message = iconv("UTF-8", "WINDOWS-1252", $app->getMessage());
				$message = str_replace("\r", '', $message);
				$message = str_replace(PHP_EOL, '" & VbCrLf & "', $message);
				$message = rtrim($message,'" & VbCrLf & "');
				$line = 'Message = "'.$message.'"';
			}
		}
		$logType = ($app->getMaintenance()) ? 'BLOCK' : 'ALLOW';
		// On log la modification de l'accès
		$module = explode('\\', get_class());
		new EventLog($logType, end($module), $app->getName());
		// On écrit dans le fichier
		$ret = $share->writeFile($app->getFile(), $file);
		if (!$ret){
			new Alert('error', 'La modification de l\'accès à <code>'.$app->getTitle().'</code> n\'a pas été prise en compte !');
			return false;
		}
		new Alert('success', 'La modification de l\'accès à <code>'.$app->getTitle().'</code> a été prise en compte !');
		return true;
	}

	/**
	 * Récupère l'envoi du formulaire de modification des accès à une application
	 * @return bool
	 */
	protected function saveAppStatus(){
		// On vérifie que tous les champs ont bien été envoyés par la requête d'envoi de formulaire
		$appReq = $_REQUEST['app'];
		// On vérifie que l'application renvoyée existe bien dans les applications enregistrées
		if (!isset($this->apps[$appReq])){
			new Alert('error', 'L\'application <code>'.$appReq.'</code> envoyée par le formulaire ne fait pas partie des applications enregistrées !');
			return false;
		}
		/**
		 * @var App $app
		 */
		$app = $oldApp = $this->apps[$appReq];
		// On récupère les variables postées par le formulaire
		$req = $this->postedData;
		if (!isset($req['maintenance']) or $req['maintenance'] === null){
			$this->apps[$appReq] = $oldApp;
			new Alert('Error', 'Le champ <code>maintenance</code> n\'a pas été envoyé par le formulaire !');
			return false;
		}else{
			$app->setMaintenance($req['maintenance']);
		}
		if (!isset($req['message'])){
			$this->apps[$appReq] = $oldApp;
			new Alert('Error', 'Le champ <code>message</code> n\'a pas été envoyé par le formulaire !');
			return false;
		}else{
			$app->setMessage($req['message']);
		}
		// On sauvegarde dans le fichier VBS
		if (!$this->saveAppFile($app)){
			$this->apps[$appReq] = $oldApp;
			return false;
		}
		return true;
	}

	/**
	 * Affiche la liste des applications et leur accès
	 */
	protected function displayAppList(){
		$canModify = ACL::canModify('module', $this->id);
		?>
		<h3>Résumé des accès</h3>
		<?php
		if (!empty($this->apps)){
			?><table class="table"><?php
			/**
			 * @var App $app
			 */
			foreach ($this->apps as $app){
				?><tr><td><?php if ($canModify) { ?><a href="<?php echo $this->url.'&app='.$app->getName(); ?>" title="Cliquez ici pour changer l'accès à l'application <?php echo $app->getTitle(); ?>"><?php } ?><?php echo $app->getTitle(); ?><?php if ($canModify) { ?></a><?php } ?></td><td><span class="label <?php echo ($app->getMaintenance()) ? 'label-warning' : 'label-success'; ?>"><?php echo ($app->getMaintenance()) ? 'Accès refusé' : 'Accès autorisé'; ?></span></td></tr><?php
			}
			?></table><?php
		}else{
			?><div class="alert alert-warning">Vous n'avez renseigné aucune application ! Ajoutez-en dans les <a href="<?php echo $this->url; ?>&page=settings">paramètres du module</a>.</div><?php
		}
	}

} 