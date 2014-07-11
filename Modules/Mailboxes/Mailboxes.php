<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 20/05/14
 * Time: 15:16
 */

namespace Modules\Mailboxes;


use Components\Item;
use Components\Menu;
use FileSystem\Fs;
use Forms\Fields\Button;
use Forms\Fields\Select;
use Forms\Fields\String;
use Front;
use Ldap\Ldap;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Forms\Field;
use Forms\Form;
use Forms\PostedData;
use Users\ACL;

/**
 * Class Mailboxes
 *
 * Pour déplacer une boîte exchange vers une autre base, on utilise un script powershell situé dans \\srv-exchange\c$\scripts qui va lire chaque fichier correspondant à chaque base exchange.
 * S'il trouve un nom de compte AD, il va essayer de migrer sa boîte vers la db correspondante. La boîte en cours de déplacement étant indisponible aux utilisateurs, cette opération est faite chaque soir à 21h.
 *
 * Ce module va lire les logs de résultats générés par le script powershell, ainsi que les fichiers de demandes de déplacement vers les db exchange. Il permet aussi d'ajouter ou de supprimer une demande de déplacement de boite.
 *
 * Organisation du module :
 * - modules/mailboxes/module.php : contient le code principal du module. Fichier chargé que si le module est actif.
 * - modules/mailboxes/mailboxes.js : contient le javascript relatif au module.
 *
 * @package Modules\Mailboxes
 */
class Mailboxes extends Module {
	protected $name = 'Gestion des boîtes Exchange';
	protected $title = 'Permet de migrer des boîtes Exchange d\'une base à une autre';

	/**
	 * Nom du fichier de logs
	 * @var string
	 */
	protected $logFile = 'move.log';

	/**
	 * Nom du fichier contenant la liste des bases Exchange
	 * @var string
	 */
	protected $mdbFile = 'mdbs.lst';

	/**
	 * Préfixe des fichiers de programmation de déplacements
	 * @var string
	 */
	protected $filePrefix = 'deplacement-vers-';

	/**
	 * Tableau des déplacements échoués
	 * @var array
	 */
	protected $failedMoves = array();

	/**
	 * Liste des bases Exchange
	 * @var array
	 */
	protected $databases = array();

	/**
	 * Liste des programmations de déplacements
	 * @var array
	 */
	protected $schedule = array();

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('mailboxes', 'Boîtes Exchange', MODULE_URL.end(explode('\\', get_class())), 'Permet de migrer des boîtes Exchange d\'une base à une autre', null, null));
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
		$this->settings['scriptsPath'] = new String('scriptsPath', 'global', '\\\\srv-exchange\scripts', null, 'Chemin des scripts Powershell', '\\\\srv-exchange\scripts', null, null, true);

	}

	/**
	 * Gère le menu d'administration
	 */
	protected function moduleMenu(){
		$menu = new Menu($this->name, 'Boîtes Exchange', '', '', '');
		$menu->add(new Item('logs', 'Logs', $this->url, 'Voir le résultat des déplacements', 'inbox'));
		$menu->add(new Item('moves', 'Déplacements', $this->url.'&page=Deplacements', 'Voir et ajouter les déplacements programmés', 'envelope'));
		Front::setSecondaryMenus($menu);
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
				<p>Les déplacements se font de nuit à partir de 21h. Il va donc vous falloir revenir sur cette page demain si vous venez d'ajouter une demande de déplacement, afin de vérifier que tout s'est bien passé !</p>
				<?php $this->displayLogs(); ?>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Lit les fichiers liés au déplacement et remplit les variables !
	 */
	protected function readLogFile(){
		if (!$share = new Fs($this->settings['scriptsPath']->getValue(), null, end(explode('\\', get_class())))){
			return false;
		}
		$logs = array();
		// On lit le fichier de logs
		$logFile = $share->readFile($this->logFile);
		foreach ($logFile as $line){
			// On ne récupère que les logs dans le fichier
			if (preg_match('/^([0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{1,2}) - (.*)$/i', $line, $matches)){
				list(, $date, $event) = $matches;
				//On converti le texte du format ANSI Windows au format UTF-8
				$event = iconv("WINDOWS-1252", "UTF-8", $event);
				$logs[] = array('date' => $date, 'event' => $event);

				// On récupère les déplacements échoués
				if (substr($event, 0, 6) == 'Erreur'){
					$tab = explode('objet', $event);
					if (isset($tab[1])){
						preg_match("/(?<=').*?(?=')/", $tab[1], $match);
						if (isset($match[0])){
							$this->failedMoves[] = $match[0];
						}
					}
				}
			}
		}
		$this->failedMoves = array_unique($this->failedMoves);
		return $logs;
	}

	/**
	 * Récupère les déplacements en erreur
	 *
	 * @return bool
	 */
	protected function populateFailed(){
		if (!$share = new Fs($this->settings['scriptsPath']->getValue(), null, end(explode('\\', get_class())))){
			return false;
		}
		$logs = array();
		// On lit le fichier de logs
		$logFile = $share->readFile($this->logFile);
		foreach ($logFile as $line){
			// On ne récupère que les logs dans le fichier
			if (preg_match('/^([0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{1,2}) - (.*)$/i', $line, $matches)){
				list(, $date, $event) = $matches;
				//On converti le texte du format ANSI Windows au format UTF-8
				$event = iconv("WINDOWS-1252", "UTF-8", $event);
				// On récupère les déplacements échoués
				if (substr($event, 0, 6) == 'Erreur'){
					$tab = explode('objet', $event);
					if (isset($tab[1])){
						preg_match("/(?<=').*?(?=')/", $tab[1], $match);
						if (isset($match[0])){
							$this->failedMoves[] = $match[0];
						}
					}
				}
			}
		}
		$this->failedMoves = array_unique($this->failedMoves);
		return true;
	}

	/**
	 * Récupère la liste des bases Exchange
	 *
	 * @return bool
	 */
	protected function populateDatabases(){
		if (!$share = new Fs($this->settings['scriptsPath']->getValue(), null, end(explode('\\', get_class())))){
			return false;
		}
		// On lit le fichier qui liste les bases Exchange
		$this->databases = $share->readFile($this->mdbFile, 'array', true, true);
		if ($this->databases === false){
			return false;
		}
		sort($this->databases);
		foreach ($this->databases as $mdb){
			$file = $share->readFile($this->filePrefix.$mdb.'.TXT');
			$this->schedule[$mdb] = ($file === false) ? null : $file;
		}
		return true;
	}


	/**
	 * Affiche les logs de déplacements de boîtes
	 */
	protected function displayLogs(){
		if ($logs = $this->readLogFile()){
			if (count($this->failedMoves) > 0){
				?><div class="alert alert-danger">Il y a des boîtes en erreur !</div><?php
			}
			?>
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>Date</th>
						<th>Événement</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($logs as $log){
					//Surlignage des évènements en échec ou réussis.
					$class = '';
					if (substr($log['event'], 0, 6) == 'Erreur' or substr($log['event'], 0, 10) == 'Impossible'){
						$class = 'danger';
					}elseif(substr($log['event'], -9, 9) == 'traitée.'){
						$class = 'success';
					}
					?>
					<tr class="<?php echo $class; ?>">
						<td><span class="text-<?php echo $class; ?>"><?php echo $log['date']; ?></span></td>
						<td><span class="text-<?php echo $class; ?>"><?php echo $log['event']; ?></span></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Affiche les déplacements programmés vers les bases Exchange et le formulaire de déplacement de boîte
	 */
	protected function moduleDeplacements(){
		$this->populateDatabases();
		$canModify = ACL::canModify('module', $this->id);
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Déplacements de boîte Exchange  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<!-- Nav tabs -->
				<ul class="nav nav-tabs">
					<li class="active"><a href="#databases" data-toggle="tab">Déplacements programmés</a></li>
					<li <?php if (!$canModify) echo 'class="disabled"'; ?>><a href="#add" <?php echo (!$canModify) ? 'class="disabled"' : 'data-toggle="tab"'; ?>>Programmer un déplacement</a></li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content">
					<div class="tab-pane active" id="databases">
						<noscript>
							<h3>Déplacements programmés</h3>
						</noscript>
						<?php
						foreach ($this->databases as $mdb){
							$this->displayMoves($mdb);
						}
						?>
					</div>
					<div class="tab-pane" id="add">
						<p></p>
						<?php $this->scheduleMove(); ?>

					</div>
				</div>


			</div>
		</div>
	<?php
	}

	/**
	 * Affiche le formulaire de programmation de déplacement de boîte
	 */
	protected function scheduleMove(){
		Front::setJsFooter('<script src="js/bootstrap3-typeahead.min.js"></script>');
		Front::setJsFooter('<script src="Modules/Mailboxes/Mailboxes.js"></script>');
		$form = new Form('addMove', null, null, 'module', $this->id, 'post');
		$form->addField(new String('user', 'global', '', null, 'Nom de l\'utilisateur', 'prénom.nom', 'La recherche peut se faire sur un login utilisateur complet (prénom.nom) ou sur une partie de celui-ci. Seuls les comptes possédant une boîte exchange sont affichés', null, true, null, 'modify', null, false, false));
		$form->addField(new Select('mdb', 'global', '', null, 'Base de destination', null, true, null, 'modify', null, false, array_combine($this->databases, $this->databases)));
		$form->addField(new Button('action', 'global', 'addMove', 'Programmer le déplacement', 'modify', 'btn-primary'));
		?>
		<div class="row">
			<div class="col-md-6">
				<noscript>
					<h3>Programmer un déplacement</h3>
				</noscript>
				<?php $form->display(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Traite l'envoi d'une demande de déplacement
	 *
	 * Méthode lancée après un envoi de formulaire
	 *
	 * @return bool
	 */
	protected function addMove(){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de programmer un déplacement de boîte !');
			return false;
		}
		$req = PostedData::get();

		// C'est parti pour les vérifications !
		if (!isset($req['user'])){
			new Alert('error', 'L\'utilisateur n\'est pas renseigné !');
			return false;
		}
		if (!isset($req['mdb'])){
			new Alert('error', 'La base Exchange n\'est pas renseignée !');
			return false;
		}
		$this->populateDatabases();
		$ldap = new Ldap();
		$reqUser = htmlspecialchars($req['user']);
		$user = $ldap->search('user', $reqUser, null, array(), true);
		if ($user['count'] == 0){
			new Alert('error', 'L\'utilisateur <code>'.$reqUser.'</code> n\'existe pas !');
			return false;
		}
		if (!in_array($req['mdb'], $this->databases)){
			new Alert('error', 'La base Exchange <code>'.$req['mdb'].'</code> n\'existe pas !');
			return false;
		}
		if (\Check::inMultiArray($reqUser, $this->schedule)){
			new Alert('error', 'Une demande de déplacement a déjà été faite pour la boîte Exchange de <code>'.$reqUser.'</code> !');
			return false;
		}
		$share = new Fs($this->settings['scriptsPath']->getValue(), null, end(explode('\\', get_class())));

		// Si le fichier lié à la base de données n'existe pas, on le crée.
		if (!$share->touchFile($this->filePrefix.$req['mdb'].'.TXT')){
			new Alert('error', 'Impossible de créer ou d\'accéder au fichier <code>'.$this->filePrefix.$req['mdb'].'.TXT'.'</code> !');
			return false;
		}
		$ret = $share->writeFile($this->filePrefix.$req['mdb'].'.TXT', $reqUser, true);
		if (!$ret){
			new Alert('error', 'Impossible d\'écrire dans le fichier <code>'.$this->filePrefix.$req['mdb'].'.TXT'.'</code> !');
			return false;
		}
		$this->schedule[$req['mdb']][] = $reqUser;
		return true;
	}

	/**
	 * Traite la suppression d'une demande
	 *
	 * Méthode lancée après un envoi de formulaire
	 *
	 * @return bool
	 */
	protected function delMove(){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de supprimer un déplacement de boîte !');
			return false;
		}
		$req = PostedData::get();
		$reqUser = htmlspecialchars($req['user']);
		// C'est parti pour les vérifications !
		if (!isset($req['user'])){
			new Alert('error', 'L\'utilisateur n\'est pas renseigné !');
			return false;
		}
		if (!isset($req['mdb'])){
			new Alert('error', 'La base Exchange n\'est pas renseignée !');
			return false;
		}
		$this->populateDatabases();
		if (!in_array($req['mdb'], $this->databases)){
			new Alert('error', 'La base Exchange <code>'.$req['mdb'].'</code> n\'existe pas !');
			return false;
		}

		$share = new Fs($this->settings['scriptsPath']->getValue(), null, end(explode('\\', get_class())));
		// Si le fichier lié à la base de données n'existe pas, on le crée.
		if (!$share->touchFile($this->filePrefix.$req['mdb'].'.TXT')) return false;
		$mbxFile = $share->readFile($this->filePrefix.$req['mdb'].'.TXT');
		$fileToWrite = array();
		foreach ($mbxFile as $line){
			if ($line !== $reqUser){
				$fileToWrite[] = $line;
			}
		}
		$ret = $share->writeFile($this->filePrefix.$req['mdb'].'.TXT', $fileToWrite);
		if (!$ret){
			new Alert('error', 'Impossible d\'écrire dans le fichier <code>'.$this->filePrefix.$req['mdb'].'.TXT'.'</code> !');
			return false;
		}
		// On vire la boite de la liste des déplacements
		if(($key = array_search($reqUser, $this->schedule[$req['mdb']])) !== false) {
			unset($this->schedule[$req['mdb']][$key]);
		}
		return true;

	}

	/**
	 * Affiche les demandes de déplacement vers une base Exchange
	 * @param string $mdb Nom de la base Exchange vers laquelle est programmé le déplacement
	 *
	 * @return bool
	 */
	protected function displayMoves($mdb){
		$canModify = ACL::canModify('module', $this->id);
		$share = new Fs($this->settings['scriptsPath']->getValue(), null, end(explode('\\', get_class())));

		// Si le fichier lié à la base de données n'existe pas, on le crée.
		if (!$share->touchFile($this->filePrefix.$mdb.'.TXT')) return false;
		$mbxFile = $share->readFile($this->filePrefix.$mdb.'.TXT');
		?>
		<h3>Base <?php echo $mdb; ?></h3>
		<?php if (!empty($mbxFile)){ ?>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th>Boîte Exchange</th>
					<th>Statut</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($mbxFile as $line){
				//Surlignage des évènements en échec ou réussis.
				$class = '';
				$statut = 'En attente';
				$line = iconv("WINDOWS-1252", "UTF-8", $line);
				if (in_array($line, $this->failedMoves)){
					$class = 'danger';
					$statut = 'En erreur';
				}
				?>
					<tr class="<?php echo $class; ?> text-<?php echo $class; ?>" id="line_<?php echo $line; ?>">
						<td><?php echo $line; ?></td>
						<td><?php echo $statut; ?></td>
						<td width="4%">
							<form method="post">
								<input type="hidden" name="field_string_user" value="<?php echo $line; ?>">
								<input type="hidden" name="field_string_mdb" value="<?php echo $mdb; ?>">
								<button class="btn btn-xs tooltip-bottom move-del" title="Supprimer la demande de déplacement" name="action" value="delMove" <?php if(!$canModify) echo 'disabled'; ?>>
									<span class="glyphicon glyphicon-trash"></span>
								</button>
							</form>
						</td>
					</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php }else{ ?>
		<div class="alert alert-info">Il n'y a pas de déplacement programmé vers cette base.</div>
		<?php
		}
		return true;
	}

	/**
	 * Affiche un tableau JSON des utilisateurs correspondant à la recherche.
	 *
	 * Utilisable uniquement dans le cas d'une requête asynchrone (AJAX)
	 */
	protected function returnUsers(){
		$ldap = new Ldap();
		$req = PostedData::get();
		$users = array();
		header('Content-type: application/json');
		$ret = $ldap->search('person', $req['query'], null, array('sAMAccountName', 'homemdb'));
		foreach ($ret as $user){
			if (isset($user['samaccountname'][0]) and isset($user['homemdb'][0])){
				$mdbTab = explode(',', $user['homemdb'][0]);
				$mdb = ltrim($mdbTab[0], 'CN=');
				$users[] = '{"Name" : "'.$user['samaccountname'][0].'", "Mdb" : "'.$mdb.'"}';
			}
		}
		echo '['.implode(', ', $users).']';
		exit();
	}

	/**
	 * Affiche un tableau JSON des bases de données Exchange.
	 *
	 * Utilisable uniquement dans le cas d'une requête asynchrone (AJAX)
	 */
	protected function returnDatabases(){
		$this->populateDatabases();
		header('Content-type: application/json');
		$mdbs = $this->databases;
		array_walk($mdbs, function(&$value){
			$value = '"'.$value.'"';
		});
		echo '['.implode(', ', $mdbs).']';
		exit();
	}

} 