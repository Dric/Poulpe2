<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/06/14
 * Time: 12:17
 */

namespace Modules\UsersTraces;


use Components\Item;
use Components\Menu;
use Db\DbFieldSettings;
use Db\DbTable;
use Forms\Fields\Int;
use Forms\Fields\String;
use Forms\Fields\Table;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Sanitize;
use Forms\Field;
use Users\ACL;

class UsersTraces extends Module {
	protected $name = 'Logs de connexion';
	protected $title = 'Lecture de logs de connexions aux applications ou aux systèmes';
	protected $logs = array();

	public function __construct(){
		parent::__construct();
		//Front::setCssHeader('<link href="js/DataTables/media/css/jquery.dataTables.min.css" rel="stylesheet">');
		Front::setCssHeader('<link href="js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">');
		Front::setJsFooter('<script src="js/DataTables/media/js/jquery.dataTables.min.js"></script>');
		Front::setJsFooter('<script src="js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.js"></script>');
		Front::setJsFooter('<script src="js/DataTables/plugins/sorting/date-euro.js"></script>');
		Front::setJsFooter('<script src="Modules/UsersTraces/UsersTraces.js"></script>');
	}

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('UsersTraces', 'Logs de connexion', MODULE_URL.end(explode('\\', get_class())), 'Lecture de logs de connexions aux applications ou aux systèmes', null, null));
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
		$this->dbTables['module_userstraces'] = array(
			'name'        => 'module_userstraces',
			'desc'        => $this->name,
			'fields'      => array(
				'id'    => array(
					'show'          => false,
					'type'          => 'int',
					'length'        => 5,
					'null'          => false,
					'autoIncrement' => true,
				),
				'name' => array(
					'label'   => 'Nom du serveur',
					'type'    => 'string',
					'length'  => 100,
					'null'    => false
				),
				'type'  => array(
					'label'   => 'Type',
					'type'    => 'string',
					'length'  => 50,
					'null'    => false
				),
			  'folder'  => array(
				  'label'   => 'Répertoire',
			    'type'    => 'string',
			    'length'  => 255,
			    'null'    => false
			  )
			),
			'primaryKey'  => 'id',
			'indexKey'   => array('name', 'type'),
			'onDuplicateKeyUpdate' => array('name', 'type', 'folder')
		);
		$usersTraces = new DbTable('module_userstraces', $this->name);
		$usersTraces->addField(new Int('id', 'global', null, null, null, null, null, new DbFieldSettings('number', true, 5, 'primary', false, true, 0, null, false, false)));
		$usersTraces->addField(new String('name', 'global', null, null, 'Nom du serveur', null, null, new DbFieldSettings('text', true, 100, 'index', false, false, 0, null, true)));
		$usersTraces->addField(new String('type', 'global', null, null, 'Type', null, null, new DbFieldSettings('text', true, 50, 'index', false, false, 0, null, true)));
		$usersTraces->addField(new String('folder', 'global', null, null, 'Répertoire', null, null, new DbFieldSettings('text', true, 255, false, false, false, 0, null, true)));
		$this->dbTables['module_userstraces'] = $usersTraces;

			// Cette table sera gérée via les paramètres
		$this->settings['module_userstraces'] = new Table($usersTraces, 'global');
	}

	/**
	 * Récupère la liste des applications, la met en forme, remplit toutes ses propriétés et crée les items de menus en rapport
	 */
	protected function moduleMenu(){
		global $db;
		$menu = new Menu($this->name, $this->name, '', '', '');
		$menu->add(new Item('logsList', 'Liste des logs', $this->url, 'Résumé des logs de connexion', 'book', null), 10);
		$logsDb = $db->get('module_userstraces');
		foreach ($logsDb as $log){
			if (!isset($this->logs[$log->type])){
				$menu->add(new Item($log->type, $log->type, $this->url.'&logs='.$log->type, 'Accès à '.$log->type, 'file'));
				$this->logs[$log->type] = new LogType($log->type);

			}
			$this->logs[$log->type]->add(new Server($log->name, $log->folder));
		}
		Front::setSecondaryMenus($menu);
	}

	public function display(){
		if (!$this->getPage()){
			if (isset($_REQUEST['logs'])){
				$logR = htmlspecialchars($_REQUEST['logs']);
				if (array_key_exists($logR, $this->logs)){
					$log = $this->logs[$logR];
					$this->breadCrumb['children'] = array(
						'title' => $log->type,
						'link'  => $this->url.'&logs='.$log->type
					);
					Front::displayBreadCrumb($this->breadCrumb);
					$this->displayLog($log);
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
					Cet outil vous permettra de savoir quels utilisateurs se sont connectés sur quels serveurs, et à quelle heure.
				</p>
				<div class="alert alert-warning">Attention : ces informations ne sont pas du temps-réel !</div>
				<div class="alert alert-warning">Attention 2 : Le chargement de certains logs peut être assez long !</div>
				<p>
					Les infos sont remontées via des fichiers texte dont le titre ou le contenu permettent de retrouver l'utilisateur et son heure de connexion.<br />
					Ce qui veut évidemment dire que cet outil ne peut remonter que des logs qui sont compatibles avec ce système.
				</p>
				<p>Pour ajouter des serveurs à surveiller, il faut passer par les paramètres du module.</p>
				<h3>Liste des logs</h3>
				<ul>
				<?php
				foreach ($this->logs as $type => $log){
					?>
					<li>
						<a href="<?php echo $this->url.'&logs='.$type; ?>"><?php echo $type; ?></a>
						<ul>
						<?php
						foreach ($log->servers as $server){
							?><li><?php echo strtoupper($server->name).' <small>('.$server->folder.')</small>'; ?></li><?php
						}
						?>
						</ul>
					</li>
					<?php
				}
				?>
				</ul>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	protected function displayLog(LogType $log){
		if (!array_key_exists($log->type, $this->logs)){
			new Alert('debug', '<code>UsersTraces->displayLog()</code> : le log <code>'.$log->type.'</code> n\'existe pas !');
			return false;
		}
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Logs de connexion à <?php echo $log->type; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<?php $log->display(); ?>
			</div>
		</div>
		<?php
	}

} 