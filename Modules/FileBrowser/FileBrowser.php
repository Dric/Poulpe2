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
use Forms\Fields\Button;
use Forms\Fields\String;
use Forms\Form;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;

class FileBrowser extends Module{
	protected $name = 'Explorateur de fichiers';
	protected $title = 'Parcourir répertoires et fichiers';

	protected $tmdbUrl = 'http://www.themoviedb.org/';



	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('FileBrowser', 'Fichiers', Front::getModuleUrl().end($module), 'Parcourir répertoires et fichiers'));
	}

	/**
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		$this->settings['rootFolder']  = new String('rootFolder', '/media/salsifis','Répertoire racine des fichiers', '/media/salsifis', null, new Pattern('string', true), true);
		Front::setCssHeader('<link href="'.Front::getBaseUrl().'/js/DataTables/extensions/Responsive/css/dataTables.responsive.css" rel="stylesheet">');
		Front::setCssHeader('<link href="'.Front::getBaseUrl().'/js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">');
		Front::setCssHeader('<link href="'.Front::getBaseUrl().'/js/highlight/styles/default.css" rel="stylesheet">');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/DataTables/media/js/jquery.dataTables.min.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/DataTables/extensions/Responsive/js/dataTables.responsive.min.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/DataTables/plugins/integration/bootstrap/3/dataTables.bootstrap.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/highlight/highlight.pack.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/Modules/FileBrowser/FileBrowser.js"></script>');
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
		<p>&nbsp;&nbsp;Dans <a href="<?php echo $this->buildArgsURL(array('folder' => urlencode($fileMeta->parentFolder))); ?>"><?php echo $fileMeta->parentFolder; ?></a></p>
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
					case 'Information':
						$fileContent = $fs->readFile($file, 'string');
						if (!\Check::isUtf8($fileContent)){
							$fileContent = mb_convert_encoding($fileContent, "UTF-8", "ASCII, ISO-8859-1, Windows-1252");
						}
						?><pre><?php echo htmlentities($fileContent, ENT_NOQUOTES|ENT_SUBSTITUTE); ?></pre><?php
						break;
					case 'Vidéo':
						$this->getTMDBData($file);
						break;
					case 'Fichier code':
					case 'Fichier de paramétrage':
						$fileContent = $fs->readFile($file, 'string');
						if (!\Check::isUtf8($fileContent)){
							$fileContent = mb_convert_encoding($fileContent, "UTF-8", "ASCII, ISO-8859-1, Windows-1252");
						}
						?><pre><code><?php echo htmlentities($fileContent, ENT_NOQUOTES|ENT_SUBSTITUTE); ?></code></pre><?php
						break;
					default:
						?><div class="alert alert-info">Vous ne pouvez pas visualiser ce type de contenu.</div><?php
				}
				?>
			</li>
		</ul>
	<?php
	}

	/**
	 * Récupère les informations d'un film ou d'un épisode de série TV auprès de TheMovieDataBase.org
	 *
	 * Un filtre est effectué pour nettoyer le nom du fichier et augmenter les chances de récupérer le bon film ou épisode TV.
	 *
	 * @param string $fileName Nom du fichier
	 */
	protected function getTMDBData($fileName){
		/**
	  * Nettoie le nom d'un téléchargement
	  */
		$replace = array(
			'.mkv'        => '',
			'.mp4'        => '',
			'x264'        => '',
			'H264'        => '',
			'720p'        => '',
			'1080p'       => '',
			'dvdrip'      => '',
			'h.264'       => '',
			'BluRay'      => '',
			'Blu-Ray'     => '',
			'XviD'        => '',
			'BRRip'       => '',
			'BDRip'       => '',
			'HDrip'       => '',
			' HD '        => '',
			'mHD'         => '',
			'HDLIGHT'     => '',
			'WEB.DL'      => '',
			'WEB-DL'      => '',
			'PS3'         => '',
			'XBOX360'     => '',
			'V.longue'    => '',
			'TRUEFRENCH'  => '',
			'french'      => '',
			'vff'         => '',
			'subforces'   => '',
			' MULTI '     => '',
			'ac3'         => '',
			'aac'         => '',
			'5.1'         => '',
			'.'           => ' ',
			'  '          => ' '
		);
		$name =  str_ireplace(array_keys($replace), array_values($replace), $fileName);
		// On vire les indications de qualité ou de compatibilité entre crochets
		$name = preg_replace('/\[.*\]/i', '', $name);
		// Et on vire les noms à la noix en fin de torrent
		$name = trim(preg_replace('/(-.\S*)$/i', '', $name), ' -');
		preg_match_all('/(\\d{4})/i', $name, $matches);
		$year = $matches[0];
		unset($matches);
		$name = preg_replace('/(\\d{4})/i', '', $name);

		// Détection d'un épisode de série TV
		preg_match_all('/\sS(\d{1,2})E(\d{1,2})/im', $name, $matches);
		if (!empty($matches[0])){
			$season = intval($matches[1][0]);
			$episode = intval($matches[2][0]);
			unset($matches);
			$name = preg_replace('/\sS(\d{1,2})E(\d{1,2})/i', '', $name);
			$type = 'tv';
		}else{
			$type = 'movie';
		}
		echo '<!-- name : '.\Get::varDump($name).' -->'."\n";
		if ($type =='tv') echo '<!-- saison : '.\Get::varDump($season).' - épisode : '.\Get::varDump($episode).' -->'."\n";
		spl_autoload_register(function ($class) {
			if (DETAILED_DEBUG) {
				global $classesUsed;
				$classesUsed[] = $class;
			}
			$tab = explode('\\', $class);
			// Les modules sont dans un répertoire à part
			if ($tab[0] == 'TMDB'){
				@include_once Front::getAbsolutePath().'/Modules/FileBrowser/'.str_replace("\\", "/", $class) . '.php';
			}
		});
		$tmdb = \TMDB\Client::getInstance('dfac51ae8cfdf42455ba6b01f392940f');
		$tmdb->language ='fr';
		$tmdb->paged = true;
		$filter = array(
			'query' => $name
		);
		if (!empty($year)) $filter['year'] = (int)$year;
		$results = $tmdb->search($type, array('query' => $name));
		if (empty($results)){
			// On tente avec le premier mot du film
			$results = $tmdb->search($type, array('query' => explode(' ', $name)[0]));
		}
		if (!empty($results)){
			$class = "\\TMDB\\structures\\".ucfirst($type);
			$movie = new $class(reset($results)->id);
			if ($type == 'movie'){
				// On récupère la date de sortie en France
				$release = \Get::getObjectsInList($movie->releases()->countries, 'iso_3166_1', 'FR');
				if (empty($release)){
					// Si la date de sortie en France n'est pas renseignée, on prend celle des USA
					$release = \Get::getObjectsInList($movie->releases()->countries, 'iso_3166_1', 'US');
				}
				$release = $release[0];
			}else{
				$release = new \StdClass();
				$release->release_date = $movie->first_air_date;
			}
			?>
			<div class="row">
				<div class="col-md-8">
					<h2><a href="<?php echo $this->tmdbUrl.$type.'/'.$movie->id; ?>"><?php echo ($type == 'movie') ? $movie->title : $movie->name; ?></a></h2>
					<ul>
						<li>Date de <?php echo ($type == 'movie') ? 'sortie en France' : 'première diffusion aux USA'; ?> : <?php echo date("d/m/Y", strtotime($release->release_date)); ?></li>
						<?php if ($type == 'movie'){ ?>
						<li>Classification : <span class="badge tooltip-bottom" title="<?php echo (is_numeric($release->certification)) ? 'Interdit aux moins de '.$release->certification.' ans' : (in_array($release->certification, array('U', 'PG'))) ? 'Tout public' : 'Classification : '.$release->certification; ?>"><?php echo (is_numeric($release->certification)) ? '-'.$release->certification : $release->certification; ?></span></li>
						<?php }else{ ?>
						<li>Série<?php echo ($movie->status == 'Ended') ? '' : ' non'; ?> terminée</li>
						<li>Nombre de saisons : <?php echo $movie->number_of_seasons; ?></li>
						<?php } ?>
					</ul>
					<h3>Résumé</h3>
					<p><?php echo $movie->overview; ?></p>
					<h3>Genre(s)</h3>
					<?php
					foreach ($movie->genres as $genre){
						?><span class="badge badge-info"><?php echo $genre->name; ?></span>&nbsp;<?php
					}
					?>
					<?php if ($type == 'movie'){ ?>
					<h3>Réalisateur</h3>
					<?php
					$casting = $movie->casts();
					$director = \Get::getObjectsInList($casting['crew'], 'job', 'Director')[0];
					?>
					<div class="media">
						<a class="pull-left" href="<?php echo $this->tmdbUrl.'person/'.$director->id; ?>">
							<img class="media-object" src="<?php echo (!empty($director->profile_path)) ? $tmdb->image_url('poster', 80, $director->profile_path) : AVATAR_PATH.DIRECTORY_SEPARATOR.AVATAR_DEFAULT; ?>" alt="<?php echo $director->name; ?>">
						</a>
						<div class="media-body">
							<h4 class="media-heading"><a href="<?php echo $this->tmdbUrl.'person/'.$director->id; ?>"><?php echo $director->name; ?></a></h4>
						</div>
					</div>
					<h3>Casting</h3>
					<?php
					foreach ($casting['cast'] as $actor){
						?>
						<div class="media">
							<a class="pull-left" href="<?php echo $this->tmdbUrl.'person/'.$actor->id; ?>">
								<img class="media-object" src="<?php echo (!empty($actor->profile_path)) ? $tmdb->image_url('poster', 80, $actor->profile_path) : AVATAR_PATH.DIRECTORY_SEPARATOR.AVATAR_DEFAULT; ?>" alt="<?php echo $actor->name; ?>">
							</a>
							<div class="media-body">
							<h4 class="media-heading"><a href="<?php echo $this->tmdbUrl.'person/'.$actor->id; ?>"><?php echo $actor->name; ?></a></h4>
							Personnage : <strong><?php echo $actor->character; ?></strong>
							</div>
						</div>
						<?php
					}
					?>
					<?php
					}else{
						$episodeData = $movie->episode($season, $episode);
					?>
					<h2><a href="<?php echo $this->tmdbUrl.'tv/'.$movie->id.'/season/'.$season.'/episode/'.$episode; ?>">Saison <?php echo $season; ?>, épisode <?php echo $episode; ?> : <?php echo $episodeData->name; ?></a></h2>
					<ul>
						<li>Date de première diffusion dans le pays d'origine : <?php echo date("d/m/Y", strtotime($episodeData->air_date)); ?></li>
					</ul>
					<h3>Résumé</h3>
						<p><?php echo (!empty($episodeData->overview)) ? $episodeData->overview : 'Pas de résumé.'; ?></p>
					<?php } ?>
				</div>
				<div class="col-md-4">
					<img class="img-responsive" alt="<?php echo $movie->title; ?>" src="<?php echo $movie->poster('300'); ?>">
				</div>
			</div>
			<?php
		}else{
			?><div class="alert alert-warning">Aucune correspondance n'a pu être trouvée pour <code><?php echo $name; ?></code> sur <a href="http://www.themoviedb.org/">The Movie DataBase</a> !</div><?php
		}
	}

	/**
	 * Affiche une image distante (méthode utilisée en asynchrone - AJAX)
	 */
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
		$filesInDir = $fs->getFilesInDir(null, null, array('dateModified', 'type', 'size', 'extension'));
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
		<?php echo $this->breadcrumbTitle($folder); ?>
		<?php if (strpos($parentFolder, $rootFolder) !== false and $parentFolder != '/') { ?><p>&nbsp;&nbsp;<a href="<?php echo $this->buildArgsURL(array('folder' => urlencode($parentFolder))); ?>"><span class="fa fa-arrow-up"></span> Remonter d'un niveau</a></p><?php } ?>
		<div class="table-responsive">
			<table id="fileBrowser" class="table table-striped" width="100%">
				<thead>
					<tr>
						<td>Nom</td>
						<td>Type</td>
						<td>Taille</td>
						<td>Dernière modification</td>
						<td>Actions</td>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($files as $i => $item){
					//$itemUrl = ($item->type != 'Répertoire') ? '&file='.urlencode($item->fullName).'&folder='.urlencode($item->parentFolder) : '&folder='.urlencode($item->fullName);
					if ($item->type != 'Répertoire') {
						$itemUrl = $this->buildArgsURL(
							array(
								'file' => urlencode($item->fullName),
								'folder' => urlencode($item->parentFolder)
							)
						);
					}else{
						$itemUrl = $this->buildArgsURL(
							array(
								'folder' => urlencode($item->fullName)
							)
						);
					}
					?>
					<tr class="<?php echo $item->colorClass(); ?>">
						<td data-order="<?php echo $i; ?>">
							&nbsp;
							<a href="<?php echo $itemUrl; ?>" class="<?php echo $item->colorClass(); ?>">
								<?php $item->display(); ?>
							</a>
						</td>
						<td><abbr class="tooltip-bottom" title="<?php echo $item->fullType; ?>"><?php echo $item->type; ?></abbr></td>
						<td data-order="<?php echo ($item->type == 'Répertoire') ? 0 : $item->size; ?>"><?php if ($item->type != 'Répertoire') echo \Sanitize::readableFileSize($item->size); ?></td>
						<td data-order="<?php echo $item->dateModified; ?>"><?php echo \Sanitize::date($item->dateModified, 'dateTime'); ?></td>
						<td>
							<?php
							$disabled = ($item->type == 'Répertoire') ? true : false;
							$form = new Form('actionsOn'.$item->name, null, array(), 'module', $this->id);
							$form->addField(new Button('action', 'delFile', 'Supprimer', 'modify', 'btn-xs', $disabled));
							//$form->display();
							?>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Crée un fil d'ariane à partir du chemin du dossier
	 *
	 * @param string $folder Répertoire
	 *
	 * @return string
	 */
	protected function breadcrumbTitle($folder){
		$rootFolder = rtrim($this->settings['rootFolder']->getValue(), DIRECTORY_SEPARATOR);
		$breadcrumb = '</ol>';
		do{
			$currentFolderPath = $folder;
			$folder = dirname($folder);
			$currentFolderName = str_replace($folder.DIRECTORY_SEPARATOR, '', $currentFolderPath);
			$breadcrumb = '<li><a href="'.$this->buildArgsURL(array('folder' => urlencode($currentFolderPath))).'">'.$currentFolderName.'</a></li>'.$breadcrumb;
		} while (strpos($folder, $rootFolder) !== false and $folder != '/');
		$breadcrumb = '<ol class="breadcrumb">'.$breadcrumb;
		return $breadcrumb;
	}

}