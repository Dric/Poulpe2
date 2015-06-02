<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/06/14
 * Time: 12:17
 */

namespace Modules\UsersTraces2;


use API\API;
use API\APIManagement;
use Components\Help;
use Components\Item;
use Components\Menu;
use Db\DbFieldSettings;
use Db\DbTable;
use FileSystem\Fs;
use Forms\Fields\Button;
use Forms\Fields\Hidden;
use Forms\Fields\Int;
use Forms\Fields\String;
use Forms\Fields\Table;
use Forms\Form;
use Forms\Pattern;
use Front;
use Ldap\Ldap;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Sanitize;
use Forms\Field;
use Users\ACL;

/**
 * Classe de gestion et d'affichage de logs de connexions utilisateurs
 *
 * Contrairement à la v1, ces logs sont alimentés en temps réel et permettent à tout instant de savoir si un utilisateur est connecté, sur quel serveur, si un poste est utilisé, etc.
 *
 * @package Modules\UsersTraces2
 */
class UsersTraces2 extends Module {
	protected $name = 'Logs de connexion (v2)';
	protected $title = 'Logs de connexions aux serveurs Citrix XenApp 7 ou supérieur';
	protected $logs = array();

	public function __construct(){
		parent::__construct();
		//Front::setCssHeader('<link href="'.Front::getBaseUrl().'/js/DataTables/media/css/jquery.dataTables.min.css" rel="stylesheet">');
		Front::setCssHeader('<link href="'.Front::getBaseUrl().'/js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">');
	}

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('UsersTraces2', 'Logs de connexions (v2)', Front::getModuleUrl().end($module), 'Logs de connexions aux serveurs Citrix XenApp 7 ou supérieur', null, null));
	}

	/**
	 * Gère le menu du module
	 */
	protected function moduleMenu(){
		$menu = new Menu($this->name, 'Journalisation des connexions', '', '', '');
		$menu->add(new Item('default', 'Tout voir', $this->url, '', 'bookmark-o'), 2);
		$menu->add(new Item('servers', 'Serveur', $this->url.'&page=server', 'Voir les connexions sur un serveur', 'server'));
		$menu->add(new Item('users', 'Utilisateur', $this->url.'&page=user', 'Voir les connexions d\'un utilisateur', 'user'));
		$menu->add(new Item('client', 'Poste client', $this->url.'&page=client', 'Voir les connexions effectuées à partir d\'un poste client', 'desktop'));
		Front::setSecondaryMenus($menu);
	}

	public static function initModuleLoading(){
		APIManagement::setAPIs(new API('traces', get_class(), ':server/:app/:user/:event/:client/:session/:data'));
	}

	/**
	 * Installe le module
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
		/**
		 * @see Db\Db->createTable pour plus de détails sur la création d'une table.
		 */

		$id = new Int('id');
		$id->setPattern(new DbFieldSettings('number', true, 10, 'primary', false, true));
		$server = new String('server');
		$server->setPattern(new DbFieldSettings('text', true, 25, 'index'));
		$app = new String('app');
		$app->setPattern(new DbFieldSettings('text', true, 30, 'index'));
		$user = new String('user');
		$user->setPattern(new DbFieldSettings('text', false, 30, 'index'));
		$event = new String('event');
		$event->setPattern(new DbFieldSettings('text', true, 15, 'index'));
		$client = new String('client');
		$client->setPattern(new DbFieldSettings('text', false, 20, 'index'));
		$session = new Int('$session');
		$session->setPattern(new DbFieldSettings('number', true, 5));
		$data = new String('data');
		$data->setPattern(new DbFieldSettings('text', false, 80));
		$timestamp = new Int('timestamp');
		$timestamp->setPattern(new DbFieldSettings('number', true, 10));

		$usersTraces2 = new DbTable('module_userstraces2', $this->name);
		$usersTraces2->addField($id);
		$usersTraces2->addField($server);
		$usersTraces2->addField($app);
		$usersTraces2->addField($user);
		$usersTraces2->addField($event);
		$usersTraces2->addField($client);
		$usersTraces2->addField($session);
		$usersTraces2->addField($data);
		$usersTraces2->addField($timestamp);
		$this->dbTables['module_userstraces2'] = $usersTraces2;

		$activeSessions = new Int('actives', 0);
		$activeSessions->setPattern(new DbFieldSettings('number', false, 3));
		$disconnectedSessions = new Int('disconnected', 0);
		$disconnectedSessions->setPattern(new DbFieldSettings('number', false, 3));
		$lastStart = new Int('lastStart', 0);
		$lastStart->setPattern(new DbFieldSettings('number', true, 10));
		$server->setPattern(new DbFieldSettings('text', true, 25, 'unique'));
		$id->setPattern(new DbFieldSettings('number', true, 3, 'primary', false, true));

		$servers = new DbTable('module_userstraces2_servers', $this->name);
		$servers->addField($id);
		$servers->addField($server);
		$servers->addField($activeSessions);
		$servers->addField($disconnectedSessions);
		$servers->addField($lastStart);
		$this->dbTables['module_userstraces2_servers'] = $servers;

		$this->settings['defaultDisplayTime'] = new Int('defaultDisplayTime', 15, 'Affichage des x derniers jours par défaut', 'Nombre de jours', 'Afficher les événements datant de moins de x jours sur la vue par défaut');
		$this->settings['defaultDisplayTime']->setUserDefinable();
	}

	/**
	 * Affichage du module
	 *
	 * L'affichage est redéfini dans ce module car les différentes pages demandées ont le même code (mais pas les mêmes paramètres) qu'il est plus simple de mutualiser.
	 *
	 * @return int
	 */
	public function display(){
			if (isset($_REQUEST['page'])){
				$subject = htmlspecialchars($_REQUEST['page']);
				if (in_array($subject, array('server', 'user', 'client'))){
					$this->breadCrumb['children'] = array(
						'title' => $subject,
						'link'  => $this->url.'&page='.$subject
					);
					if (isset($_REQUEST[$subject])){
						$item = htmlspecialchars($_REQUEST[$subject]);
						$this->breadCrumb['children']['children'] = array(
							'title' => $item,
							'link'  => $this->url.'&page='.$subject.'&'.$subject.'='.$item
						);
					}
					Front::displayBreadCrumb($this->breadCrumb);
					$this->displaySubjectLogs($subject);
					return 0;
				}else{
					new Alert('error', 'La page demandée n\'existe pas !');
				}
			}
			Front::displayBreadCrumb($this->breadCrumb);
			$this->mainDisplay();
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?> <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					<?php echo $this->title; ?>.
				</p>
				<p>
					Cet outil vous permet de parcourir les logs de connexion à Citrix XenApp 7
				</p>
				<?php $this->displayDefaultLogs(); ?>
			</div>
		</div>
	<?php
	}

	/**
	 * Traite les requêtes d'API
	 *
	 * @see initModuleLoading() pour la définition de l'API
	 *
	 * Chaque requête validée crée (au moins) un enregistrement en base de données.
	 *
	 * L'accès à l'API n'est pas authentifié. Il serait donc possible de créer de faux événements dans la base de données en faisant des appels bidon à l'API.
	 * Pour autant, il n'est pas possible de falsifier les événements réels.
	 */
	public function runAPI(){
		global $db;
		// Délai entre l'événement de fermeture et celui de déconnexion
		$delay = 20;
		$API = APIManagement::getAPI('traces');
		$toDb = $API->params;
		$toDb['timestamp'] = time();
		if ($toDb['user'] == '0') $toDb['user'] = null;
		if ($toDb['client'] == '0') $toDb['client'] = null;
		if ($toDb['session'] == '0') $toDb['session'] = 0;
		if ($toDb['event'] == 'Demarrage') $toDb['event'] = 'Démarrage';
		if ($toDb['event'] == 'Arret') $toDb['event'] = 'Arrêt';
		switch ($toDb['app']){
			case 'System':
				// On ne sait pas si le serveur est déjà répertorié dans la liste des serveurs. Pas de problème, on fait une insertion qui échouera silencieusement si le serveur existe déjà dans la base.
				$db->query('INSERT IGNORE INTO `module_userstraces2_servers` (`server`) VALUES ("'.$toDb['server'].'")');
				switch($toDb['event']){
					case 'Démarrage':
						$lastServerEvent = $db->query('SELECT * FROM module_userstraces2 WHERE `app` = "System" AND `server` = "'.$toDb['server'].'" ORDER BY `timestamp` DESC LIMIT 1');
						if (empty($lastServerEvent) or $lastServerEvent[0]->event != 'Arrêt'){
							/**
							 * Il n'y a pas d'événement `Arrêt`, nous avons affaire à un arrêt brutal du serveur.
							 * Dans ce cas, on va créer des événements spéciaux pour signaler que les sessions ont été fermées suite à un plantage serveur.
							 */
							$lastServerStart = (int)$db->getVal('module_userstraces2_servers', 'lastStart', array('server' => $toDb['server']));
							if (empty($lastServerStart)) $lastServerStart = 0;
							// On remet à 0 les compteurs de sessions
							//$retU = $db->update('module_userstraces2_servers', array('actives' => 0, 'disconnected' => 0), array('server' => $toDb['server']));
							// On récupère tous les événements de session depuis le dernier démarrage
							$sessionsDb = $db->query('SELECT * FROM module_userstraces2 WHERE `timestamp` >= '.$lastServerStart.' AND `server` = "'.$toDb['server'].'" ORDER BY `timestamp`');
							$openedSessions = array();
							foreach ($sessionsDb as $session){
								switch ($session->event){
									case 'Ouverture':
									case 'Reconnexion':
									case 'Deconnexion':
										$openedSessions[$session->user][$session->client] = $session->id;
										break;
									case 'Fermeture':
										// Si on trouve une correspondance dans le tableau des sessions ouvertes, on la supprime. de cette façon, il ne restera à la fin que les sessions qui n'ont pas d'événement de fermeture
										if (isset($openedSessions[$session->user][$session->client])) unset($openedSessions[$session->user][$session->client]);
										break;
								}
							}
							// On ajoute pour chaque session restée ouverte au moment du crash un événement de fermeture de session.
							foreach ($openedSessions as $user => $openedSession){
								foreach ($openedSession as $client => $id){
									$array = array(
										'server'    => $toDb['server'],
										'app'       => 'Login',
										'user'      => $user,
										'event'     => 'Fermeture_PS',
										'client'    => $client,
										'session'   => 0,
										'timestamp' => $toDb['timestamp']
									);
									$db->insert('module_userstraces2', $array);
								}
							}
							$toDb['event'] = 'Démarrage_PS';
						}
						$ret = $db->insert('module_userstraces2', $toDb);
						// On met à jour la valeur du dernier démarrage du serveur
						$sql = 'INSERT INTO `module_userstraces2_servers` (`server`, `lastStart`) VALUES ("'.$toDb['server'].'", '.$toDb['timestamp'].') ON DUPLICATE KEY UPDATE `lastStart` = VALUES(`lastStart`)';
						$ret2 = $db->query($sql);

						// Fin sub-case `Démarrage`
						break;
					case 'Arrêt':
						/**
						 * A l'arrêt du serveur, les événements de déconnexion n'ont souvent pas le temps d'être trnasmis, ce qui empêche le module de compléter l'événement de fermeture avec le nom du client.
						 * On va donc chercher les événements sans client pour les compléter.
						 */
						$lastServerStart = (int)$db->getVal('module_userstraces2_servers', 'lastStart', array('server' => $toDb['server']));
						if (empty($lastServerStart)) $lastServerStart = 0;

						/**
						 * On récupère tous les événements de session depuis le dernier démarrage, avec les événements les plus récents en premier.
						 * Le but est de récupérer tous les événements de fermeture qui n'ont pas de client renseigné, ainsi que de créer des événements de fermeture pour les sessions qui n'en ont pas.
						 */
						$sessionsDb = $db->query('SELECT * FROM module_userstraces2 WHERE `timestamp` >= '.$lastServerStart.' AND `server` = "'.$toDb['server'].'" ORDER BY `timestamp` DESC');
						$badClosedSessions = $goodClosedSession = array();
						foreach ($sessionsDb as $session){
							switch ($session->event){
								case 'Ouverture':
								case 'Reconnexion':
								case 'Déconnexion':
									if (isset($badClosedSessions[$session->user])){
										$db->update('module_userstraces2', array('client' => $session->client), array('id' => $badClosedSessions[$session->user]));
										unset($badClosedSessions[$session->user]);
									}elseif (!isset($goodClosedSession[$session->user][$session->client])){
										// Si l'événement d'ouverture n'a pas d'événement de fermeture rattaché, alors on en crée un.
										$array = array(
											'server'    => $toDb['server'],
											'app'       => 'Login',
											'user'      => $session->user,
											'event'     => 'Fermeture',
											'client'    => $session->client,
											'session'   => $session->session,
											'timestamp' => $toDb['timestamp']
										);
										$db->insert('module_userstraces2', $array);
									}
									break;
								case 'Fermeture':
									if (empty($session->client)){
										$badClosedSessions[$session->user] = $session->id;
									}else{
										$goodClosedSession[$session->user][$session->client] = $session->id;
									}
									break;
							}
						}
						// On remet à zéro les sessions actives et déconnectées du serveur
						//$sql = 'INSERT INTO `module_userstraces2_servers` (`server`, `actives`, `disconnected`) VALUES ("'.$toDb['server'].'", 0, 0) ON DUPLICATE KEY UPDATE `actives` = VALUES(`actives`), `disconnected` = VALUES(`disconnected`)';
						//$db->query($sql);
						$ret = $db->insert('module_userstraces2', $toDb);
						break;
					default:
						$ret = false;
				}
				if ($ret){
					echo json_encode(array('result'=>'success'));
				}else{
					echo json_encode(array('result'=>'fail'));
				}
				// Fin case `System`
				break;
			case 'Login':
				// On récupère le nom du poste via le DNS. L'avantage c'est que même si le poste change d'adresse IP, le suivi reste possible.
				if (\Check::isIpAddress($toDb['client'])) {
					$ClientName     = explode('.', gethostbyaddr($toDb['client']));
					$toDb['client'] = strtoupper($ClientName[0]);
				}
				// On ne sait pas si le serveur est déjà répertorié dans la liste des serveurs. Pas de problème, on fait une insertion qui échouera silencieusement si le serveur existe déjà dans la base.
				//$db->query('INSERT IGNORE INTO `module_userstraces2_servers` (`server`) VALUES ("'.$toDb['server'].'")');
				// On cherche si l'événement n'est pas déjà dans la base, Windows ayant parfois tendance à envoyer plusieurs fois la même chose.
				$sameEvents = $db->query('SELECT * FROM module_userstraces2 WHERE `server` = "'.$toDb['server'].'" AND `user` = "'.$toDb['user'].'" AND `event` = "'.$toDb['event'].'" AND `timestamp` > '.(time() - $delay));
				if (empty($sameEvents)) {
					if ($toDb['event'] == 'Deconnexion') {
						// Windows envoie systématiquement un événement de déconnexion après un événement de fermeture de session. Si la déconnexion fait suite à une fermeture, on ignore l'événement.
						$closeEvent = $db->query('SELECT * FROM module_userstraces2 WHERE `timestamp` >= ' . (time() - $delay) . ' AND `server` = "' . $toDb['server'] . '" AND `app` = "' . $toDb['app'] . '" AND `user` = "' . $toDb['user'] . '" and `event` = "Fermeture"');
						if (!empty($closeEvent)) {
							echo json_encode(array('result' => 'ignored'));
							exit();
						}
						// On incrémente le compteur de sessions déconnectées
						//$db->query('UPDATE module_userstraces2_servers SET `actives` = IF(`actives > 0, `actives` - 1, 0), `disconnected` = `disconnected` + 1 WHERE `server` = "' . $toDb['server'] . '"');
					} elseif ($toDb['event'] == 'Ouverture') {
						// On incrémente le compteur de sessions actives
						//$db->query('UPDATE module_userstraces2_servers SET `actives` = `actives` + 1 WHERE `server` = "' . $toDb['server'] . '"');
					} elseif ($toDb['event'] == 'Reconnexion') {
						// On incrémente le compteur de sessions actives tout en décrémentant le cmpteur de sessions inactives
						//$db->query('UPDATE module_userstraces2_servers SET `actives` = `actives` + 1, `disconnected` = IF(`disconnected` > 0, `disconnected` - 1, 0) WHERE `server` = "' . $toDb['server'] . '"');
					} else {
						// Fermeture de session
						// On récupère le nom du client depuis un événement de connexion/reconnexion car les fermetures de sessions ne le renvoient pas
						$toDb['client'] = $db->query('SELECT client FROM module_userstraces2 WHERE `server` = "' . $toDb['server'] . '" AND `app` = "' . $toDb['app'] . '" AND `user` = "' . $toDb['user'] . '" and `session` = "'.$toDb['session'].'" and `event` IN ("Reconnexion", "Ouverture") ORDER BY `timestamp` DESC LIMIT 1', 'val');
						// On décrémente le compteur de sessions actives
						//$db->query('UPDATE module_userstraces2_servers SET `actives` = IF(`actives` > 0, `actives` - 1, 0) WHERE `server` = "' . $toDb['server'] . '"');
					}
					$ret = $db->insert('module_userstraces2', $toDb);
					if ($ret) {
						echo json_encode(array('result' => 'success'));
					} else {
						echo json_encode(array('result' => 'fail'));
					}
				}else{
					echo json_encode(array('result' => 'ignored'));
				}
				// Fin case `Login`
				break;
			default:
				echo json_encode(array('result'=>'ignored'));
		}
		exit();
	}

	/********* Méthodes propres au module *********/



	/**
	 * Affiche les logs par défaut
	 */
	protected function displayDefaultLogs(){
		global $db;
		$displayTime = $this->settings['defaultDisplayTime']->getValue();
		$events = $db->query('SELECT * FROM module_userstraces2 WHERE `timestamp` >= '.(time()-30*86400).' ORDER BY `timestamp` DESC');
		?><div class="alert alert-info">Affichage des événements datant de moins de <b><?php echo $displayTime; ?></b> jours.</div><?php
		$this->displayTableLogs($events);
	}

	/**
	 * Gère l'affichage de la page des sujets (`server`, `user` ou `client`)
	 * @param string $subject Sujet (`server`, `user` ou `client`)
	 *
	 * @return bool
	 */
	protected function displaySubjectLogs($subject){
		global $db;
		if (!in_array($subject, array('server', 'user', 'client', 'all'))){
			new Alert('debug', '<code>displaySubjectLogs()</code> : le sujet <code>'.$subject.'</code> ne fait pas partie des sujets autorisés !');
			return false;
		}
		$req = $this->postedData;
		if (isset($_REQUEST[$subject])){
			$subjectName = htmlspecialchars($_REQUEST[$subject]);
		}elseif(isset($req[$subject])){
			$subjectName = $req[$subject];
		}else{
			$subjectName = null;
		}
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/bootstrap3-typeahead.min.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/Modules/UsersTraces2/searchForm.js"></script>');
		$this->searchForm($subject, $subjectName);
		if (!empty($subjectName)){
			$search = array(
				$subject => $subjectName
			);
			if ($subject != 'server')	$search['app'] = 'Login';
			$subjectEvents = $db->get('module_userstraces2', null, $search, array('timestamp' => 'DESC'));
			if (empty($subjectEvents)){
				new Alert('error', $subject.' <code>'.$subjectName.'</code> n\'existe pas !');
				return false;
			}
			switch ($subject){
				case 'server': $label = 'sur';break;
				default:
				case 'user': $label = 'de';break;
				case 'client': $label = 'depuis';break;
			}
			?><h2>Connexions <?php echo $label; ?> <?php echo $subjectName ?></h2><?php
			if ($subject == 'server'){
				$this->displayServerStats($subjectName);
			}
			$this->displayTableLogs($subjectEvents, $subject);
		}
		return true;
	}

	/**
	 * Affiche les statistiques d'un serveur
	 *
	 * @param string $serverName
	 */
	protected function displayServerStats($serverName){
		global $db;
		$stats = $db->get('module_userstraces2_servers', null, array('server' => $serverName));
		$stats = $stats[0];
		if (!empty($stats)){
			?>
			<ul>
				<!--<li>Sessions actives : <b><?php echo $stats->actives; ?></b></li>
				<li>Sessions déconnectées : <b><?php echo $stats->disconnected; ?></b></li>-->
				<li>Dernier démarrage : <b><?php echo ($stats->lastStart > 0) ? Sanitize::date($stats->lastStart, 'fullDateTime') : 'Inconnu'; ?></b> (allumé depuis <?php echo ($stats->lastStart > 0) ? Sanitize::timeDuration(time() - $stats->lastStart) : 'Inconnu'; ?>)</li>
			</ul>
			<br>
			<br>
			<?php
		}
	}

	/**
	 * Affiche un champ de recherche
	 *
	 * @param string $subject Sujet de la recherche
	 * @param string $searched Utilisateur recherché (juste pour affichage)
	 */
	protected function searchForm($subject, $searched = null){
		$form = new Form($subject.'Search', null, null, 'module', $this->id, 'post', 'form-inline');
		$form->addField(new String($subject, $searched, 'Nom', null, 'La recherche peut se faire sur le nom complet ou sur une partie de celui-ci', null, true, 'access', null, false, false));
		$form->addField(new Hidden('subject', $subject, 'access'));
		$form->addField(new Button('action', 'getInfo', 'Rechercher', 'access', 'btn-primary btn-sm'));
		$form->display();
	}

	/**
	 * Affiche la table d'événements d'un sujet (`server`, `user` ou `client`)
	 *
	 * @param object|array  $events  Tableau contenant les événements
	 * @param string $subject Sujet (`server`, `user` ou `client` ou `all` par défaut pour tout afficher)
	 *
	 * @return bool
	 */
	protected function displayTableLogs($events, $subject = 'all'){
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/DataTables/media/js/jquery.dataTables.min.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/Modules/UsersTraces2/UsersTraces2.js"></script>');
		if (!in_array($subject, array('server', 'user', 'client', 'all'))){
			new Alert('debug', '<code>displayTableLogs()</code> : le sujet <code>'.$subject.'</code> ne fait pas partie des sujets autorisés !');
			return false;
		}
		?>
		<table class="table table-striped table-bordered" id="logTable">
			<thead>
			<tr>
				<th>Date et heure</th>
				<?php if ($subject != 'server') { ?><th>Serveur</th><?php } ?>
				<?php if ($subject == 'server' or $subject == 'all') { ?><th>Type</th><?php } ?>
				<th>Evénement</th>
				<?php if ($subject != 'user') { ?><th>Utilisateur</th><?php } ?>
				<?php if ($subject != 'client') { ?><th>Client</th><?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ($events as $event){
				?>
				<tr>
					<td data-order="<?php echo $event->timestamp; ?>"><?php echo \Sanitize::date($event->timestamp, 'fullDateTime'); ?></td>
					<?php if ($subject != 'server') { ?>
						<td>
							<a class="tooltip-left" href="<?php echo $this->buildArgsURL(array('page' => 'server', 'server' => $event->server)); ?>" title="Voir toutes les connexions sur le serveur <?php echo $event->server; ?>">
								<?php echo $event->server; ?>
							</a>
						</td>
					<?php } ?>
					<?php if ($subject == 'server' or $subject == 'all') { ?>
					<td><?php echo $event->app; ?></td>
					<?php } ?>
					<td>
						<?php
						switch ($event->event){
							case 'Démarrage_PS':
								?>Démarrage	<span class="pull-right">
								<?php	Help::iconWarning('Le serveur a démarré suite à un plantage !', 'left');	?>
								</span>
								<?php
								break;
							case 'Fermeture_PS':
								?>Fermeture	<span class="pull-right">
								<?php	Help::iconWarning('La session a été fermée brutalement suite à un plantage serveur !', 'left');	?>
								</span>
								<?php
								break;
							default :
								echo $event->event;
						}
						?>
					</td>

					<?php if ($subject != 'user') { ?>
						<td>
							<a class="tooltip-left" href="<?php echo $this->buildArgsURL(array('page' => 'user', 'user' => $event->user)); ?>" title="Voir toutes les connexions de <?php echo $event->user; ?>">
								<?php echo $event->user; ?>
							</a>
						</td>
					<?php } ?>
					<?php if ($subject != 'client') { ?>
						<td>
							<a class="tooltip-left" href="<?php echo $this->buildArgsURL(array('page' => 'client', 'client' => $event->client)); ?>" title="Voir toutes les connexions effectuées depuis le poste <?php echo $event->client; ?>">
								<?php echo ($event->client != '0') ? $event->client: ''; ?>
							</a>
						</td>
					<?php } ?>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	<?php
	}

	/**
	 * Affiche un tableau JSON des objets correspondant à la recherche.
	 *
	 * Lorsqu'un utilisateur saisit quelque chose dans le champ de recherche que ce soit dans l'écran de recherche de serveur, d'utilisateur ou de client, l'auto-complétion va faire une recherche dans la base sur les lettres qui ont été saisies pour renvoyer des propositions à l'utilisateur.
	 * C'est cette méthode qui traite les recherches et qui renvoie les résultats
	 *
	 * Utilisable uniquement dans le cas d'une requête asynchrone (AJAX)
	 */
	protected function returnInfo(){
		global $db;
		$json = '{"options" :[';
		//var_dump($_REQUEST);
		if (isset($_REQUEST['subject']) and in_array($_REQUEST['subject'], array('server', 'user', 'client'))) {
			$subject = $_REQUEST['subject'];
		}else{
			exit();
		}
		//var_dump($subject);
		if (isset($_REQUEST[$subject]) and !empty($_REQUEST[$subject])) {
			$search = htmlspecialchars($_REQUEST[$subject]);
		}else{
			exit();
		}
		//var_dump($search);
		$results = $db->query('SELECT DISTINCT '.$subject.' FROM module_userstraces2 WHERE `'.$subject.'` LIKE "%'.$search.'%"');
		if (!empty($results)) {
			foreach ($results as $item) {
				$json .= '"' . $item->$subject . '",';
			}
			$json = rtrim($json, ',');
			$json .= ']}';
			header('Content-type: application/json');
			echo $json;
		}
		exit();
	}

} 