<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 18/07/14
 * Time: 14:01
 */

namespace Modules\Transmission;


use Components\Filter;
use Components\Item;
use Components\Menu;
use Db\DbFieldSettings;
use Db\DbTable;
use Forms\Fields\Bool;
use Forms\Fields\Button;
use Forms\Fields\CheckboxList;
use Forms\Fields\Float;
use Forms\Fields\Int;
use Forms\Fields\Select;
use Forms\Fields\String;
use Forms\Fields\Table;
use Forms\Fields\Time;
use Forms\Fields\Url;
use Forms\Form;
use Forms\JSSwitch;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;

/**
 * Class Transmission
 *
 * @package Modules\Transmission
 */
class Transmission extends Module{
	protected $name = 'Téléchargements';
	protected $title = 'Gestion des téléchargements via torrents';
	/**
	 * Activation de paramètres définissables par les utilisateurs
	 * @var bool
	 */
	protected $allowUsersSettings = true;

	/**
	 * Filtres généraux des torrents
	 * @var array
	 */
	protected $selectFilters = array(
		'all'			=> 'Tous les torrents',
		'inDl'		=> 'Les torrents en cours de téléchargement',
		'done'		=> 'Les torrents en cours de partage',
		'last10'	=> 'Les 10 derniers torrents terminés',
		'noCat'		=> 'Les torrents non affectés',
		'stopped'	=> 'Les torrents supprimables'
	);

	/**
	 * Répertoires de téléchargements
	 * @var array()
	 */
	static $downloadsDirs = array();

	/**
	 * Liste des torrents
	 * @var array()
	 */
	protected $torrents = array();

	/**
	 * Session transmission
	 * @var object
	 */
	protected $transSession = null;


	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('downloads', 'Téléchargements', MODULE_URL.end(explode('\\', get_class())), 'Gestion des téléchargements via torrents'));
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
	 * Gère le menu du module
	 */
	protected function moduleMenu(){
		$menu = new Menu($this->name, 'Téléchargements', '', '', '');
		$menu->add(new Item('serverSettings', 'Paramètres', $this->url.'&page=Gestion', 'Paramétrer le serveur de téléchargements', 'cog'));
		$menu->add(new Item('downloads', 'Téléchargements', $this->url, 'Voir et modifier les téléchargements', 'download-alt'));
		Front::setSecondaryMenus($menu);
	}

	/**
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		$this->settings['filesPath']  = new String('filesPath', '/media/salsifis','Chemin des scripts', '/media/salsifis', null, new Pattern('string', true), true);
		$this->settings['DLNAFolder']   = new String('DLNAFolder', 'dlna', 'Répertoire du DLNA', null, null, new Pattern('string', true), true);
		$this->settings['transmissionURL']  = new Url('transmissionURL', 'http://localhost:9091/bt/rpc', 'Url du serveur Web RTC Transmission', null, null, new Pattern('url', true), true);
		$this->settings['defaultDisplay'] = new Select('defaultDisplay', 'all', 'Affichage par défaut', 'Sélectionnez le filtre que vous voulez voir à chaque fois que vous allez dans les téléchargements', false, null, null, false, $this->getSelectFilters());
		$this->settings['defaultDisplay']->setUserDefinable();

		$dirs = new DbTable('module_downloadsDirs', 'Répertoires de téléchargements', null, 'Les chemins doivent être relatifs au répertoire de stockage des fichiers. (Ex : pour déclarer un répertoire "/media/salsifis/jeux", n\'inscrivez que "jeux"');
		$dirs->addField(new Int('id', null, 'ID du répertoire', null, null, new DbFieldSettings('number', true, 3, 'primary', false, true, 0, null, false, false)));
		$dirs->addField(new String('folder', null, 'Nom du répertoire', null, null, new DbFieldSettings('text', true, 100, 'unique', false, false, 0, null, true)));
		$dirs->addField(new Bool('isDLNAMember', true, 'Sous-répertoire du répertoire diffusé par le serveur DLNA', 'Si coché, le contenu des répertoires sera diffusé par le serveur DLNA sur votre télé, tablette, ordinateur, etc. via le protocole DLNA.', new DbFieldSettings('checkbox', false, 1, false, false, false, 0, null, true), false, null, null, false, new JSSwitch()));

		$this->dbTables['module_downloadsDirs'] = $dirs;

		$this->settings['module_downloadsDirs'] = new Table($dirs);
	}

	/**
	 * Affichage principal
	 */
	public function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p><?php echo $this->title; ?>.</p>
				<p>Cliquez sur les téléchargements pour en afficher les détails.</p>
				<?php
				$filter = (!empty($this->postedData['filter'])) ? $this->postedData['filter'] : $this->settings['defaultDisplay']->getValue();
				$this->filtersForm($filter);
				$this->displayTorrents($this->getFilter($filter));
				?>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	protected function moduleGestion(){
		/**
		 * @var TransSession
		 */
		$settings = $this->getTransSession();
		$bandwidth['Dl'] = (isset($this->postedData['bandwidthDl'])) ? $this->postedData['bandwidthDl'] : 7;
		$bandwidth['Up'] = (isset($this->postedData['bandwidthUp'])) ? $this->postedData['bandwidthUp'] : 1;
		$speeds['TotalMaxDl'] = floor(($bandwidth['Dl']*1048576)/8);
		$speeds['TotalMaxUp'] = floor(($bandwidth['Up']*1048576)/8);
		$speeds['TorrentMaxDl'] = \Get::roundTo(($speeds['TotalMaxDl'] * 0.8), 10240);
		$speeds['TorrentMaxUp'] = \Get::roundTo($speeds['TotalMaxUp'] * 0.8, 10240);
		$speeds['TorrentAltDl'] = \Get::roundTo($speeds['TotalMaxDl'] * 0.3, 10240);
		$speeds['TorrentAltUp'] = \Get::roundTo($speeds['TotalMaxUp'] * 0.3, 10240);
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Paramètres de téléchargement  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>Paramétrer la bande passante passante des téléchargements peut vite se révéler un vrai casse-tête : si vous mettez des valeurs trop basses vos téléchargements n'avanceront pas, et si vous mettez des valeurs trop hautes vous allez saturer votre bande passante et vous ne pourrez plus naviguer sur Internet.</p>
				<p>Une bonne pratique est de prendre votre débit Internet (en Mbps), le diviser par 8, prendre 80% de cette valeur pour la pleine vitesse et 30% pour le mode tortue. Ça vous semble compliqué ? Utilisez le calculateur ci-dessous, qui vous donnera des valeurs arrondies bien comme il faut.</p>
				<p>Et si vous ne savez pas ce qu'est le mode tortue, ça vous permet dans la journée de laisser plus de bande passante pour la navigation Internet habituelle, et de la reprendre pendant la nuit pour accélérer vos téléchargements.</p>
				<form method="post">
					<table class="table">
						<thead>
							<tr>
								<th>Sens</th>
								<th>Débit (Mbps)</th>
								<th>Vitesse max</th>
								<th>Maximum en mode normal</th>
								<th>Maximum en mode tortue</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>Téléchargements (download)</td>
								<td><input type="number" name="field_string_bandwidthDl" id="field_string_bandwidthDl" class="form-control" value="<?php echo $bandwidth['Dl']; ?>"></td>
								<td id="TotalMaxDl"><?php echo \Sanitize::readableFileSize($speeds['TotalMaxDl'], 0); ?>/s</td>
								<td id="TorrentMaxDl"><?php echo \Sanitize::readableFileSize($speeds['TorrentMaxDl'], 0); ?>/s</td>
								<td id="TorrentAltDl"><?php echo \Sanitize::readableFileSize($speeds['TorrentAltDl'], 0); ?>/s</td>
							</tr>
							<tr>
								<td>Chargements (upload)</td>
								<td><input type="number" name="field_string_bandwidthUp" id="field_string_bandwidthUp" class="form-control" value="<?php echo $bandwidth['Up']; ?>"></td>
								<td id="TotalMaxUp"><?php echo \Sanitize::readableFileSize($speeds['TotalMaxUp'], 0); ?>/s</td>
								<td id="TorrentMaxUp"><?php echo \Sanitize::readableFileSize($speeds['TorrentMaxUp'], 0); ?>/s</td>
								<td id="TorrentAltUp"><?php echo \Sanitize::readableFileSize($speeds['TorrentAltUp'], 0); ?>/s</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="field_hidden_noToken" value="1">
					<button type="submit" name="action" value="calculateSpeed" class="btn btn-default btn-sm">Calculer les vitesses</button>
				</form>
				<?php
				$form = new Form('serverSettings', null, null, 'module', $this->id);
				$form->addField(new Float('ratioLimit', $settings->getRatioLimit(), null, 'Ratio de partage/téléchargement', null, 'Ratio maximum pour un fichier entre les données partagées (upload) et les données téléchargées. Certains torrents nécessitent un ratio de 0.75 minimum, mettez un ou plus pour être tranquille.'));
				$form->addField(new Int('dlSpeed', $settings->getDlSpeed(), null, 'Vitesse de téléchargement <span class="glyphicon glyphicon-arrow-down tooltip-bottom" title="Sens descendant"></span>', null, 'Bande passante maximum allouée aux téléchargements, en ko/s. Cette bande passante ne doit pas excéder 80% de votre bande passante descendante ADSL ou Fibre.', new Pattern('number', true), true, 'admin'));
				$form->addField(new Int('upSpeed', $settings->getUpSpeed(), null, 'Vitesse de chargement <span class="glyphicon glyphicon-arrow-up tooltip-bottom" title="Sens montant"></span>', null, 'Bande passante maximum allouée aux chargements (sens montant), en ko/s. Cette bande passante ne doit pas excéder 80% de votre bande passante montante ADSL ou Fibre.', new Pattern('number', true), true, 'admin'));
				$form->addField(new Int('altDlSpeed', $settings->getAltDlSpeed(), null, 'Vitesse de téléchargement réduite (mode tortue)', null, 'Bande passante maximum allouée aux téléchargements lorsque le serveur est en mode tortue, en ko/s. Cette bande passante ne devrait pas excéder 30% de votre bande passante descendante ADSL ou Fibre, afin de ne pas pénaliser la navigation Internet ou la télévision.', new Pattern('number', true), true, 'admin'));
				$form->addField(new Int('altUpSpeed', $settings->getAltUpSpeed(), null, 'Vitesse de chargement réduite (mode tortue)', null, 'Bande passante maximum allouée aux chargements (sens montant) lorsque le serveur est en mode tortue, en ko/s. Cette bande passante ne devrait pas excéder 30% de votre bande passante montante ADSL ou Fibre, afin de ne pas pénaliser la navigation Internet ou la télévision.', new Pattern('number', true), true, 'admin'));
				$form->addField(new Bool('altSpeedEnabled', $settings->getAltSpeedEnabled(), null, 'Activer le mode tortue', 'Quand le mode tortue est actif, la bande passante utilisée pour les téléchargements est réduite. Cela vous permet en journée de naviguer sur Internet sans ralentissements.', null, true, 'modify', null, false, new JSSwitch()));
				$form->addField(new Time('altBegin', \Sanitize::time($settings->getAltBegin(true), 'time'), null, 'Heure de déclenchement du mode tortue', null, 'Le mode tortue se déclenchera à cette heure chaque jour que vous aurez indiqué. Il est conseillé de le déclencher un peu avant que vous n\'ayez besoin de naviguer sur Internet, tôt le matin par exemple', new Pattern('time'), false, 'admin'));
				$form->addField(new Time('altEnd', \Sanitize::time($settings->getAltEnd(true), 'time'), null, 'Heure d\'arrêt du mode tortue', null, 'Le mode tortue sera arrêté à cette heure chaque jour que vous aurez indiqué. Il est conseillé de le déclencher tard le soir lorsque vous n\'avez plus besoin de naviguer sur Internet, afin que les téléchargements puissent occuper un maximum de bande passante.', new Pattern('time'), false, 'admin'));
				/*
				 * Dimanche					= 1			(binary: 0000001)
				 * Lundi						= 2			(binary: 0000010)
				 * Mardi						= 4			(binary: 0000100)
				 * Mercredi					= 8			(binary: 0001000)
				 * Jeudi						= 16		(binary: 0010000)
				 * Vendredi					= 32		(binary: 0100000)
				 * Samedi						= 64		(binary: 1000000)
				 * Jours ouvrés			= 62		(binary: 0111110)
				 * Weekend					= 65		(binary: 1000001)
				 * Toute la semaine	= 127		(binary: 1111111)
				 * Aucun						= 0			(binary: 0000000)
				 */
				$days = array(
				  2   => 'Lundi',
				  4   => 'Mardi',
				  8   => 'Mercredi',
				  16  => 'Jeudi',
				  32  => 'Vendredi',
				  64  => 'Samedi',
				  1   => 'Dimanche',
				  62  => 'Jours ouvrés',
				  65  => 'Week-end',
				  127 => 'Toute la semaine'
				);
				$form->addField(new CheckboxList('altDaysEnabled', $settings->getAltDaysEnabled(true), null, 'Jours d\'activation du mode tortue', 'Sélectionnez les jours ou les plages de jours pendant lesquels le mode tortue s\'activera aux heures indiquées. Ne sélectionnez aucun jour pour désactiver le mode tortue.', false, 'admin', null, false, $days));
				$form->addField(new Button('action', 'saveServerSettings', 'Sauvegarder', 'admin', 'btn-primary'));
				$form->display();
				?>
			</div>
		</div>
	<?php
	}

	/**
	 * Retourne la liste des répertoires de téléchargements
	 *
	 * @return array
	 */
	protected function getDownloadsDirs(){
		global $db;
		if (empty($this->downloadsDirs)){
			$dlDirsDb = $db->get('module_downloadsDirs');
			foreach ($dlDirsDb as $dlDir){
				self::$downloadsDirs[$this->settings['DLNAFolder']->getValue().'/'.$dlDir->folder] = $dlDir->folder;
			}
		}
		return self::$downloadsDirs;
	}

	/**
	 * Affiche le formulaire de sélection des filtres
	 */
	protected function filtersForm($filter = 'all'){
		$form = new Form('filters', null, array(), null, null, 'get', 'form-inline', array('module' => 'Transmission'), true);
		$form->addField(new Select('filter', $filter, null, 'Afficher', null, false, null, null, false, $this->getSelectFilters()));
		$form->addField(new Button('action', 'filterDownloads', 'Filtrer', 'modify', 'btn-primary btn-sm'));
		$form->display();
	}

	/**
	 * @return array
	 */
	protected function getTorrents() {
		if (empty($this->torrents)){
			$transSession = $this->getTransSession();
			if (!empty($transSession)){
				// Voir https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt
				$torrents = $transSession->get(array(), array('id', 'name', 'addedDate', 'status', 'doneDate', 'totalSize', 'downloadDir', 'uploadedEver', 'isFinished', 'leftUntilDone', 'percentDone', 'files', 'eta', 'uploadRatio', 'comment'))->arguments->torrents;
				foreach ($torrents as $torrent){
					$this->torrents[] = new Torrent($torrent);
				}
			}
		}
		return $this->torrents;
	}

	/**
	 * @return object
	 */
	protected function getTransSession() {
		if (empty($this->transSession)){
			try{
			$this->transSession = new TransSession($this->settings['transmissionURL']->getValue());
			} catch (TransmissionRPCException $e){
				new Alert('error', 'Impossible de se connecter au serveur Transmission !');
				return null;
			}
		}
		return $this->transSession;
	}

	/**
	 * Génère et retourne le filtre adéquat suivant le filtrage demandé
	 *
	 * @param string $filter
	 *
	 * @return Filter|null
	 */
	protected function getFilter($filter = 'all'){
		switch ($filter){
			case 'all':
			case null:
				return null;
			default:
				// On veut les torrents qui sont dans la catégorie $filter
				return new Filter('downloadDir', 'in', array($filter));
			case 'inDl':
				return new Filter('percentDone', '<', 100);
			case 'done':
				return new Filter('percentDone', '=', 100);
			case 'last10':
				return new Filter('rawDoneDate', '>', 0, 10, 'DESC');
			case 'noCat':
				return new Filter('downloadDir', '!in', array($this->getDownloadsDirs()));
			case 'stopped':
				return new Filter('ratioPercentDone', '=', 100);
		}
	}

	/**
	 * Affiche la liste des torrents filtrés
	 * @param Filter $filter Objet de filtre à appliquer
	 */
	protected function displayTorrents(Filter $filter = null){
		if (!empty($filter)){
			$torrents = $filter->Objects($this->getTorrents(), 'name');
		}else{
			$torrents = \Sanitize::sortObjectList($this->getTorrents(), 'name');
		}
		if (!empty($torrents)){
			?><h3><?php echo count($torrents); ?> torrents affichés</h3><?php
			foreach ($torrents as $torrentRPC){
				$torrent = new Torrent($torrentRPC);
				$torrent->display($this->id);
			}
		}else{
			$msg = (!empty($filter)) ? 'qui corresponde à votre requête - <a class="" href="'.$this->url.'&field_select_filter=all&field_hidden_noToken=1&action=filterDownloads">Afficher tous les téléchargements</a>' : 'en cours';
			?>
			<br><br>
			<div class="alert alert-info">Il n'y a aucun téléchargement <?php echo $msg; ?>.</div>
			<?php
		}

		//var_dump($torrents);
	}

	/**
	 * Retourne la liste des filtres
	 *
	 * @param bool $noDownloadsDirs
	 *
	 * @return array
	 */
	protected function getSelectFilters($noDownloadsDirs = false) {
		if ($noDownloadsDirs){
			return $this->selectFilters;
		}else{
			return array_merge($this->selectFilters, array('Types de téléchargements' => $this->getDownloadsDirs()));
		}
	}
}