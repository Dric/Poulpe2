<?php
/**
 * Creator: Dric
 * Date: 05/07/2016
 * Time: 13:58
 */

namespace Modules\LogFiles;

use Components\Item;
use FileSystem\Fs;
use Forms\Fields\Bool;
use Forms\Fields\Button;
use Forms\Fields\String;
use Forms\Form;
use Forms\JSSwitch;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\LogFiles\LogFileTypes\LogFileType;
use Modules\Module;
use Modules\ModulesManagement;
use Sanitize;

class LogFiles extends Module{
	protected $name = 'Visionneuse de logs';
	protected $title = 'Affiche les logs des différents scripts du domaine';
	/** @var array Tableau des fichiers de logs */
	protected $logFiles = array();
	/** @var Fs Chemin monté de la racine des scripts */
	protected $fs = null;
	/** @var array Types de logs */
	protected $logTypes = array();

	public function __construct($bypassACL = false) {
		parent::__construct($bypassACL);
		$this->fs = new Fs($this->settings['scriptsDir']->getValue());
	}

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('logsFiles', 'Visionneuse des logs de scripts', Front::getModuleUrl().end($module), 'Affiche les logs des différents scripts du domaine', null, null));
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
		$this->settings['scriptsDir']  = new String('scriptsDir', '\\\\intra.epsi.fr\scripts', 'Chemin des scripts', '\\\\intra.epsi.fr\scripts', null, new Pattern('string', true), true);
		$this->settings['allowExternalFiles'] = new Bool('allowExternalFiles', false, 'Autoriser l\'ouverture de fichiers situés en dehors du chemin', null, new Pattern('checkbox'), false, 'admin', null, false, new JSSwitch('small'));
		$this->settings['lastFirst'] = new Bool('lastFirst', true, 'Afficher les derniers événements en premier', null, new Pattern('checkbox'), false, 'modify', null, false, new JSSwitch('small'));
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		//Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/bootstrap3-typeahead.min.js"></script>');
		//Front::setJsFooter('<script src="'.Front::getBaseUrl().'/Modules/UserInfo/UserInfo.js"></script>');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Visionneuse des logs de scripts du domaine  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					Affiche les logs des différents scripts du domaine.
				</p>
				<?php
				$this->populateLogFiles();
				echo $this->displayLogFiles($this->logFiles);
				?>
			</div>
		</div>
		<?php
	}



	/********* Méthodes propres au module *********/

	/**
	 * Traitement et affichage d'un fichier de log.
	 *
	 * Un test va être effectué pour déterminer de quel type est le fichier de log.
	 * Si une correspondance est trouvée avec un type de logs connu, le fichier est traité et affiché suivant son type.
	 * 
	 * Si aucun fichier n'a été demandé, on rebascule sur l'affichage de l'arborescence.
	 */
	protected function moduleLog(){
		$displayFile = null;
		$req = $this->postedData;
		if (isset($req['item'])){
			$displayFile = $req['item'];
		}elseif(isset($_REQUEST['item'])){
			$displayFile = $_REQUEST['item'];
		}
		if (!is_null($displayFile)){
			$file = $this->loadFile($displayFile);
			$typeClass = $this->detectLogType($file);
			/** @var LogFileType $logType */
			$logType = new $typeClass($file, $this->settings['lastFirst']->getValue());
			?>
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="page-header">
						<h1>Affichage du fichier <code><?php echo $displayFile; ?></code>  <?php $this->manageModuleButtons(); ?></h1>
					</div>
					<?php if ($this->settings['lastFirst']->getValue()) { ?>
						<ul>
							<li>Les derniers événements sont affichés en premier.</li>
							<li>Type de fichier de logs : <code><?php echo $logType->getName(); ?></code></li>
						</ul>
					<?php }
					$logType->display();
					?>
				</div>
			</div>
			<?php
		}else{
			$this->mainDisplay();
		}
	}

	/**
	 * Détecte le type de log à partir d ucontenu d'un fichier
	 * @param array $logFile Contenu du fichier sous forme de tableau (1 ligne par item)
	 *
	 * @return string Classe du type détecté
	 */
	protected function detectLogType($logFile){
		// On récupère le chemin actuel de la classe du module
		$pathTypes = dirname(__FILE__).DIRECTORY_SEPARATOR.'LogFileTypes';

		// Inventaire des types de fichiers de logs
		$fsLTypes  = new Fs($pathTypes, 'localhost');
		$filesTypes = $fsLTypes->getFilesInDir(null, 'php');
		foreach ($filesTypes as $file){
			if ($file->name != 'LogFileType.php'){
				// On lit les 60 premières lignes de chaque fichier pour récupérer les infos nécessaires
				$lines=array();
				$fp = fopen($file->fullName, 'r');
				while(!feof($fp)){
					$line = fgets($fp);
					$lines[] = $line;
					if (preg_match('/^class (\w*) extends LogFileType/i', $line, $matches)){
						// On récupère le nom de la classe du module si c'est une extension de la classe Module
						$this->logTypes[] = '\Modules\LogFiles\LogFileTypes\\'.$matches[1];
						break;
					}
					if (count($lines) > 60) break;
				}
				fclose($fp);
			}
		}
		// détection du type de log

		foreach ($this->logTypes as $logType){
			if ($logType::testPattern($logFile)) return $logType;
		}
		return '\Modules\LogFiles\LogFileTypes\LogFileType';
	}

	/**
	 * Charge le contenu d'un fichier de logs
	 * 
	 * @param string $file Nom et chemin absolu du fichier
	 *
	 * @return bool|\string[] Retourne `false` en cas d'erreur, et le contenu du fichier sous forme de tableau en cas de succès.
	 */
	protected function loadFile($file){
		$scriptsDir = $this->settings['scriptsDir']->getValue();
		$pathInfo = pathinfo(str_replace('\\', '/', $file));
		if ($pathInfo['extension'] != 'log'){
			new Alert('error', 'Le fichier n\'a pas l\'extension <code>log</code> !');
			return false;
		}

		$regex = '/^'. Sanitize::SanitizeForRegex($scriptsDir).'/i';
		if (!preg_match($regex, $file)){
			if (!$this->settings['allowExternalFiles']->getValue()) {
				new Alert('error', 'Vous ne pouvez pas afficher de fichier de Logs situé en dehors de <code>' . $scriptsDir . '</code> !');
				return false;
			}
			$fs = new Fs($pathInfo['dirname']);
			return $fs->readFile($pathInfo['baseName']);
		}
		$relativePath = str_replace(str_replace('\\', '/', $scriptsDir), '', $pathInfo['dirname']);
		return Sanitize::convertToUTF8($this->fs->readFile($relativePath.DIRECTORY_SEPARATOR.$pathInfo['basename']));
	}
	/**
	 * Charge les fichiers de log présents dans le chemin indiqué en paramètre du module
	 */
	protected function populateLogFiles(){
		$this->logFiles = $this->fs->getRecursiveFilesInDir(null, 'log');
		//var_dump($fs->getRecursiveFilesInDir(null, 'log',true));
	}

	/**
	 * Affiche l'abrorescence avec les fichiers de logs trouvés (de façon récursive)
	 *
*@param array      $array Tableau contenant l'aborescence
	 * @param string $path  Chemin menant au dossier courant
	 *
	 * @return string
	 */
	protected function displayLogFiles($array, $path = null){
		if (is_null($path)) $path = $this->settings['scriptsDir']->getValue();
		$sanitizedId = str_replace('\\', '_', $path);
		$sanitizedId = str_replace('.', '§', $sanitizedId);
		$ret = '<ul id="logs_'.$sanitizedId.'" class="'.(($path == $this->settings['scriptsDir']->getValue()) ? '' : 'collapse ').'tree">';
		foreach ($array as $name => $item){
			$ret .= '<li>';
			if (is_array($item)){
				$ret .= '<i class="fa fa-folder-o"></i> <a role="button" data-toggle="collapse" href="#logs_'.$sanitizedId.'_'.$name.'" aria-expanded="false">'.$name.'</a>'.$this->displayLogFiles($item, $path.'\\'.$name);
			} else {
				$ret .= '<i class="fa fa-file-text-o"></i> <a href="'.$this->buildArgsURL(array('page'=>'log', 'item'=> $path.'\\'.$name)).'">'.$name.'</a>';
			}
			$ret .= '</li>';
		}
		$ret .= '</ul>';

		return $ret;
	}

	/**
	 * Affiche le champ de recherche utilisateurs
	 * @param string $userSearched Utilisateur recherché (juste pour affichage)
	 */
	protected function searchForm($userSearched = null){
		$form = new Form('userSearch', null, null, 'module', $this->id, 'post', 'form-inline');
		$form->addField(new String('user', $userSearched, 'Nom de l\'utilisateur', 'prenom.nom', 'La recherche peut se faire sur un login complet (prenom.nom) ou sur une partie de celui-ci', null, true, 'access', null, false, false));
		$form->addField(new Button('action', 'getUserInfo', 'Rechercher', 'access', 'btn-primary btn-sm'));
		$form->display();
	}


}