<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 30/05/14
 * Time: 14:21
 */

namespace Modules\Dupliqueur;


use Components\Item;
use Db\DbFieldSettings;
use Db\DbTable;
use FileSystem\Fs;
use Forms\Fields\Bool;
use Forms\Fields\Button;
use Forms\Fields\CheckboxList;
use Forms\Fields\Int;
use Forms\Fields\Select;
use Forms\Fields\String;
use Forms\Fields\Table;
use Forms\JSSwitch;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Forms\Field;
use Forms\Form;
use Forms\PostedData;
use Users\ACL;

class Dupliqueur extends Module {
	protected $name = 'Dupliqueur Fou';
	protected $title = 'Copie de fichier sur plein de serveurs à la fois';

	protected $logs = array();

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('dupliqueur', 'Dupliqueur de fichiers', Front::getModuleUrl().end($module), 'Copie de fichier sur plein de serveurs à la fois', null, null));
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
		$dupliqueur = new DbTable('module_dupliqueur', 'Serveurs');
		$dupliqueur->addField(new Int('id', null, null, null, null, new DbFieldSettings('number', true, 5, 'primary', false, true, 0, null, false, false)));
		$dupliqueur->addField(new String('name', null, 'Nom du serveur', null, 'Vous pouvez saisir une plage de serveurs. ex : au lieu de saisir SRV-CITRIX01, SRV-CITRIX02 et SRV-CITRIX03, vous pouvez saisir dans un seul champ SRV-CITRIX01-03', new DbFieldSettings('text', true, 150, 'unique', false, false, 0, null, true)));
		$dupliqueur->addField(new String('category', null, 'Catégorie', null, null, new DbFieldSettings('text', true, 100, 'index', false, false, 0, null, true)));
		$this->dbTables['module_dupliqueur'] = $dupliqueur;
		// Cette table sera gérée via les paramètres
		$this->settings['module_dupliqueur'] = new Table($dupliqueur);
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/Modules/Dupliqueur/Dupliqueur.js"></script>');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Dupliqueur de fichiers <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					Cette page vous permet de copier un fichier sur tous les serveurs d'une catégorie à la fois, simplement en indiquant le nom du fichier et le serveur d'origine.
				</p>
				<div class="row">
					<div class="col-md-5 col-md-offset-1">
						<?php $this->displayCopyForm(); ?>
					</div>
					<div class="col-md-4">
						<?php $this->displayLog(); ?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	protected function copyFile(){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		// On récupère les valeurs du formulaire
		$req = $this->postedData;
		//var_dump($req);

		// On fait les tests d'usage d'existence des différentes valeurs requises
		if (!isset($req['serverFrom']) or empty($req['serverFrom'])){
			new Alert('error', 'Le serveur d\'origine n\'est pas renseigné !');
			return false;
		}
		if (!isset($req['serversTo']) or empty($req['serversTo'])){
			new Alert('error', 'Aucun serveur de destination sélectionné !');
			return false;
		}
		if (!isset($req['file']) or empty($req['file'])){
			new Alert('error', 'Le nom du fichier à copier n\'est pas renseigné !');
			return false;
		}
		setcookie('dupliqueur_file', $req['file'], time()+31104000);

		// On récupère le chemin et le nom du fichier (pathinfo ne semble pas gérer les chemins Windows...)
		$fileOnly = substr(strrchr($req['file'], "\\"), 1);
		$path = str_replace('\\'.$fileOnly, '', $req['file']);

		$file = str_replace(':', '$', $req['file']);
		global $db;
		$servers = array();
		$serversDb = $db->get('module_dupliqueur');
		foreach ($serversDb as $server){
			// On regarde si une plage de serveurs a été saisie. La plage n'est détectée que si elle est du genre SRV-CITRIX01-10, ça ne fontionnera pas pour SRV-CITRIX01-TESTXEN
			preg_match('/^([^0-9]*)(\d{0,2}-\d{0,2}|\d{0,2})/i', $server->name, $matches);
			// Si aucune plage n'est détectée, on prend le nom complet
			if (empty($matches[2]) or strpos($matches[2], '-') === false) {
				$servers[] = $server->name;
			} else {
				list($firstServer, $lastServer) = explode('-', $matches[2]);
				// On récupère le nombre de chiffres dans l'identifiant du serveur (2 pour SVR-CITRIX01, 1 pour SRV-CARIAAPPLI2)
				$intSize = strlen((string)$firstServer);
				if (strlen((string)$lastServer) > $intSize) $intSize = strlen((string)$lastServer);
				// On crée un serveur par item
				for ($i = $firstServer; $i <= $lastServer; $i++) {
					$serverName = $matches[1] . str_pad($i, $intSize, '0', STR_PAD_LEFT);
					$servers[]  = $serverName;
				}
			}
		}
		// On vérifie que le serveur d'origine fait bien partie de la liste des serveurs en bdd
		if (!in_array($req['serverFrom'], $servers)){
			new Alert('error', 'Le serveur <code>'.$req['serverFrom'].'</code> ne fait pas partie des serveurs autorisés !');
			return false;
		}
		// On monte le partage et on vérifie que le fichier d'origine existe
		$module = explode('\\', get_class());
		$share[$req['serverFrom']] = new Fs($path, $req['serverFrom'], end($module));
		if (!$share[$req['serverFrom']]){
			$this->logs[] = array(
				'time'  => time(),
				'text'  => 'Impossible d\'accéder à \\\\'.$req['serverFrom'].'\\'.$path.' !'
			);
			return false;
		}
		if (!$share[$req['serverFrom']]->fileExists($fileOnly)){
			new Alert('error', 'Le fichier <code>'.$fileOnly.'</code> n\'existe pas !');
			return false;
		}
		$this->logs[] = array(
			'time'  => time(),
		  'text'  => 'Copie à partir de \\\\'.$req['serverFrom'].'\\'.str_replace(':', '$', $req['file'])
		);
		if ($req['overwrite']){
			$text = 'Les fichiers seront remplacés s\'ils existent sur les serveurs destinataires.';
		}else{
			$text = 'Les fichiers ne seront pas remplacés s\'ils existent sur les serveurs destinataires.';
		}
		$this->logs[] = array(
			'time'  => time(),
			'text'  => $text
		);
		foreach ($req['serversTo'] as $serverTo){
			// On vérifie que les serveurs de destination font bien partie de la liste des serveurs autorisés
			if (!in_array($serverTo, $servers)){
				new Alert('error', 'Le serveur <code>'.$serverTo.'</code> ne fait pas partie des serveurs autorisés !');
				return false;
			}
			// On ne copie évidemment pas le fichier sur lui-même
			if ($req['serverFrom'] != $serverTo){
				$share[$serverTo] = new Fs($path, $serverTo, end($module));
				if (!$share[$serverTo]){
					$this->logs[] = array(
						'time'  => time(),
						'text'  => 'Impossible d\'accéder à \\\\'.$req[$serverTo].'\\'.$path.' !'
					);
				}else{
					if (($share[$serverTo]->fileExists($fileOnly) and $req['overwrite']) or !$share[$serverTo]->fileExists($fileOnly)){
						if (!@copy($share[$req['serverFrom']]->getMountName().DIRECTORY_SEPARATOR.$fileOnly, $share[$serverTo]->getMountName().DIRECTORY_SEPARATOR.$fileOnly)){
							$this->logs[] = array(
								'time'  => time(),
								'text'  => 'Copie vers \\\\'.$serverTo.'\\'.$file.' échouée !'
							);
						}else{
							$this->logs[] = array(
								'time'  => time(),
								'text'  => 'Copie vers \\\\'.$serverTo.'\\'.$file.' réussie !'
							);
						}
					}elseif ($share[$serverTo]->fileExists($fileOnly) and !$req['overwrite']){
						$this->logs[] = array(
							'time'  => time(),
							'text'  => 'Copie vers \\\\'.$serverTo.'\\'.$file.' annulée car ce fichier existe déjà !'
						);
					}
				}
			}
		}
		$this->logs[] = array(
			'time'  => time(),
			'text'  => 'Opération terminée.'
		);

	}

	protected function displayLog(){
		?>
		<div class="form-group">
			<label>Logs</label>
			<textarea class="form-control" id="logs" rows="25" style="font-size: 12px"><?php
				if (!empty($this->logs)){
					foreach ($this->logs as $log){
						echo \Sanitize::date($log['time'], 'time').' - '.$log['text'].PHP_EOL;
					}
				}
				?></textarea>
		</div>
		<?php
	}

	/**
	 * Affiche le formulaire de copie du fichier
	 */
	protected function displayCopyForm(){
		global $db;
		$servers = $db->get('module_dupliqueur');
		$choices = array();
		foreach ($servers as $server){
			// On regarde si une plage de serveurs a été saisie. La plage n'est détectée que si elle est du genre SRV-CITRIX01-10, ça ne fontionnera pas pour SRV-CITRIX01-TESTXEN
			preg_match('/^([^0-9]*)(\d{0,2}-\d{0,2}|\d{0,2})/i', $server->name, $matches);
			// Si aucune plage n'est détectée, on prend le nom complet
			if (empty($matches[2]) or strpos($matches[2], '-') === false) {
				$choices[$server->name] = $server->name;
			} else {
				list($firstServer, $lastServer) = explode('-', $matches[2]);
				// On récupère le nombre de chiffres dans l'identifiant du serveur (2 pour SVR-CITRIX01, 1 pour SRV-CARIAAPPLI2)
				$intSize = strlen((string)$firstServer);
				if (strlen((string)$lastServer) > $intSize) $intSize = strlen((string)$lastServer);
				// On crée un serveur par item
				for($i = $firstServer; $i <= $lastServer; $i++){
					$serverName = $matches[1].str_pad($i, $intSize, '0', STR_PAD_LEFT);
					$choices[$serverName] = $serverName;
				}
			}

		}
		// On récupère les valeurs du formulaire
		$req = $this->postedData;
		$serverFrom = (isset($req['serverFrom'])) ? $req['serverFrom'] : null;
		$serversTo = (isset($req['serversTo'])) ? $req['serversTo'] : null;
		if (isset($req['file'])){
			$file = $req['file'];
		}elseif(isset($_COOKIE['dupliqueur_file'])){
			$file = $_COOKIE['dupliqueur_file'];
		}else{
			$file = null;
		}

		$overwrite = (isset($req['overwrite'])) ? $req['overwrite'] : true;

		// Création du formulaire
		$form = new Form('dupliqueur', null, null, 'module', $this->getId());
		$form->addField(new Select('serverFrom', $serverFrom, 'Serveur d\'origine', 'Choisissez le serveur sur lequel se trouve le fichier à copier', true, 'modify', null, false, $choices, true));
		$form->addField(new CheckboxList('serversTo', $serversTo, 'Serveurs de destination', 'Choisissez le(s) serveur(s) sur le(s)quel(s) copier le fichier', true, 'modify', 'serversList', false, $choices, 'all'));
		$form->addField(new String('file', $file, 'Fichier à copier', 'd:\\cariatides\\cariatides.cfg', 'Indiquez un chemin de fichier. Ex : d:\cariatides\cariatides.cfg pour aller chercher le fichier sur le disque D du serveur d\'origine', new Pattern('text', true), true, 'modify'));
		$form->addField(new Bool('overwrite', $overwrite, 'Ecraser les fichiers existants', 'Cochez cette case pour remplacer les fichiers déjà existants sur les serveurs', null, false, 'modify', null, false, new JSSwitch('mini', 'left')));
		$form->addField(new Button('action', 'copyFile', 'Lancer la copie', 'modify', 'btn-primary'));

		$form->display();
	}
} 