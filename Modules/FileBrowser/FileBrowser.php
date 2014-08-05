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
		Front::setJsFooter('<script src="js/DataTables/media/js/jquery.dataTables.min.js"></script>');
		Front::setJsFooter('<script src="js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.js"></script>');
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
			?>
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="page-header">
						<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
					</div>
					<p><?php echo $this->title; ?>.</p>
					<?php $this->displayFolder($folder); ?>
				</div>
			</div>
			<?php
		}
	}

	/********* Méthodes propres au module *********/

	/**
	 * Affiche l'arborescence d'un répertoire
	 * @param string $folder Répertoire à afficher
	 */
	protected function displayFolder($folder){
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
		$files = array_merge($folders, $files);
		$parentFolder = dirname($folder);
		?>
		<h3><span class="fa fa-folder-open"></span>&nbsp;&nbsp;<?php echo $folder; ?></h3>
		<br>
		<?php if (strpos($parentFolder, $this->settings['rootFolder']->getValue()) !== false and $parentFolder != '/') { ?><p>&nbsp;&nbsp;<a href="<?php echo $this->url.'&folder='.urlencode($parentFolder); ?>"><span class="fa fa-arrow-up"></span> Remonter d'un niveau</a></p><?php } ?>
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
					?>
					<tr class="<?php echo $item->colorClass(); ?>">
						<td data-order="<?php echo $i; ?>">
							&nbsp;
							<?php if ($item->type == 'Répertoire') { ?><a href="<?php echo $this->url.'&folder='.urlencode($item->fullName); ?>" class="<?php echo $item->colorClass(); ?>"><?php } ?>
								<?php $item->display(); ?>
							<?php if ($item->type == 'Répertoire') { ?></a><?php } ?>
						</td>
						<td><?php echo $item->type; ?></td>
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