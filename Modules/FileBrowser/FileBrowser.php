<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 04/08/14
 * Time: 14:25
 */

namespace Modules\FileBrowser;


use Components\Item;
use FileSystem\File;
use FileSystem\Fs;
use Forms\Fields\String;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;

class FileBrowser extends Module{
	protected $name = 'Explorateur de fichiers';
	protected $title = 'Parcourir répertoires et fichiers';



	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('FileBrowser', 'Fichiers', MODULE_URL.end(explode('\\', get_class())), 'Parcourir répertoires et fichiers'));
	}

	/**
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		$this->settings['rootFolder']  = new String('rootFolder', '/media/salsifis','Répertoire racine des fichiers', '/media/salsifis', null, new Pattern('string', true), true);
		Front::setCssHeader('<link href="js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">');
		Front::setCssHeader('<link href="js/highlight/styles/default.css" rel="stylesheet">');
		Front::setJsFooter('<script src="js/DataTables/media/js/jquery.dataTables.min.js"></script>');
		Front::setJsFooter('<script src="js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.js"></script>');
		Front::setJsFooter('<script src="js/highlight/highlight.pack.js"></script>');
		Front::setJsFooter('<script src="Modules/FileBrowser/FileBrowser.js"></script>');
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
		$file = null;
		if (isset($_REQUEST['file'])){
			$file = urldecode($_REQUEST['file']);
			if (!file_exists($file)){
				new Alert('error', 'Le fichier <code>'.$file.'</code> n\'existe pas !');
				$file = null;
			}elseif (strpos($file, $this->settings['rootFolder']->getValue()) === false){
				new Alert('error', 'Vous n\'avez pas l\'autorisation de visualiser ce fichier !');
				$file = null;
			}
		}
		if (empty($file)){
			$folder = (isset($_REQUEST['folder'])) ? urldecode($_REQUEST['folder']): $this->settings['rootFolder']->getValue();
			if (!file_exists($folder)){
				new Alert('error', 'Le répertoire <code>'.$folder.'</code> n\'existe pas !');
				$folder = $this->settings['rootFolder']->getValue();
			}elseif (strpos($folder, $this->settings['rootFolder']->getValue()) === false){
				new Alert('error', 'Vous n\'avez pas l\'autorisation de visualiser ce répertoire !');
				$folder = $this->settings['rootFolder']->getValue();
			}
		}
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p><?php echo $this->title; ?>.</p>
				<?php
				if (empty($file)) {
					$this->displayFolder($folder);
				}else{
					$this->displayFile($file);
				}
				?>
			</div>
		</div>
		<?php
	}

	/********* Méthodes propres au module *********/

	protected function displayFile($file){
		$path = dirname($file).DIRECTORY_SEPARATOR;
		$file = str_replace($path, '', $file);
		$fs = new Fs($path);
		$fileMeta = $fs->getFileMeta($file);

		?>
		<h2><span class="fa fa-<?php echo $fileMeta->getIcon(); ?>"></span>&nbsp;&nbsp;<?php echo $file; ?></h2>
		<p>&nbsp;&nbsp;Dans <a href="<?php echo $this->url.'&folder='.urlencode($fileMeta->parentFolder); ?>"><?php echo $fileMeta->parentFolder; ?></a></p>
		<ul>
			<li>Date de création : <?php echo \Sanitize::date($fileMeta->dateCreated, 'dateTime'); ?></li>
			<li>Date de dernière modification : <?php echo \Sanitize::date($fileMeta->dateModified, 'dateTime'); ?></li>
			<li>Taille : <?php echo \Sanitize::readableFileSize($fileMeta->size); ?></li>
			<li>
				Contenu :<br>
				<?php
				switch ($fileMeta->type){
					case 'Image':
						?><img class="img-thumbnail" alt="<?php echo $file; ?>" src="<?php echo $this->url.'&action=displayImage&file='.$fileMeta->fullName; ?>"<?php
						break;
					case 'Fichier texte':
					case 'Fichier code':
						$fileContent = $fs->readFile($file, 'string');
						?><pre><code class="hljs"><?php echo \Sanitize::SanitizeForDb($fileContent, false); ?></code></pre><?php
						break;
					default:
						?><div class="alert alert-info">Vous ne pouvez pas visualiser ce type de contenu.</div><?php
				}
				?>
			</li>
		</ul>
	<?php
	}

	protected function displayImage(){
		$fileName = $_REQUEST['file'];
		$file = new File('', $fileName);
		header('content-type: '. $file->fullType);
		header('content-disposition: inline; filename="'.$fileName.'";');
		readfile($fileName);
		exit();
	}

	/**
	 * Affiche l'arborescence d'un répertoire
	 * @param string $folder Répertoire à afficher
	 */
	protected function displayFolder($folder){
		$rootFolder = rtrim($this->settings['rootFolder']->getValue(), DIRECTORY_SEPARATOR);
		$fs = new Fs($folder);
		$filesInDir = $fs->getFilesInDir();
		// On classe les items, les répertoires sont en premier
		$files = $folders = array();
		/**
		 * @var File $item
		 */
		foreach ($filesInDir as $item){
			if ($item->type == 'Répertoire'){
				$folders[] = $item;
			}else{
				$files[] = $item;
			}
		}
		unset($filesInDir);
		$folders = \Sanitize::sortObjectList($folders, 'name');
		$files = \Sanitize::sortObjectList($files, 'name');
		$files = array_merge($folders, $files);
		$parentFolder = dirname($folder);
		?>
		<h2><span class="fa fa-folder-open"></span>&nbsp;&nbsp;<?php echo $folder; ?></h2>
		<br>
		<?php if (strpos($parentFolder, $rootFolder) !== false and $parentFolder != '/') { ?><p>&nbsp;&nbsp;<a href="<?php echo $this->url.'&folder='.urlencode($parentFolder); ?>"><span class="fa fa-arrow-up"></span> Remonter d'un niveau</a></p><?php } ?>
		<div class="table-responsive">
			<table id="fileBrowser" class="table table-striped">
				<thead>
					<tr>
						<td>Nom</td>
						<td>Type</td>
						<td>Taille</td>
						<td>Dernière modification</td>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($files as $i => $item){
					$itemUrl = ($item->type != 'Répertoire') ? '&file='.urlencode($item->fullName).'&folder='.urlencode($item->parentFolder) : '&folder='.urlencode($item->fullName);
					?>
					<tr class="<?php echo $item->colorClass(); ?>">
						<td data-order="<?php echo $i; ?>">
							&nbsp;
							<a href="<?php echo $this->url.$itemUrl; ?>" class="<?php echo $item->colorClass(); ?>">
								<?php $item->display(); ?>
							</a>
						</td>
						<td><?php echo $item->fullType; ?></td>
						<td data-order="<?php echo ($item->type == 'Répertoire') ? 0 : $item->size; ?>"><?php if ($item->type != 'Répertoire') echo \Sanitize::readableFileSize($item->size); ?></td>
						<td data-order="<?php echo $item->dateModified; ?>"><?php echo \Sanitize::date($item->dateModified, 'dateTime'); ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
		<?php
	}

}