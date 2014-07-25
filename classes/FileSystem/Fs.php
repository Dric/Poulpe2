<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 23/04/14
 * Time: 09:29
 */

namespace FileSystem;


use Logs\Alert;
use stdClass;

/**
 * Classe de gestion des points de montage de partages réseaux
 *
 * @package FileSystem
 */
class Fs {

	/**
	 * Serveur sur lequel faire le montage. Utiliser 'localhost' pour le serveur local.
	 * @var string
	 */
	protected $server = null;

	/**
	 * Chemin du point de montage
	 * @var string
	 */
	protected $path = null;

	/**
	 * Préfixe facultatif pour le points de montage
	 * @var string
	 */
	protected $prefix = null;

	/**
	 * Nom utilisé pour créer le point de montage
	 * @var string
	 */
	protected $mountName = null;

	/**
	 * Point de montage activé ou non
	 * @var bool
	 */
	protected $isMounted = false;

	/**
	 * Classe de gestion des points de montage de partages réseaux
	 *
	 * @param string $path Chemin du point de montage.
	 *  - Pour un partage SMB, on indique le chemin local ou un partage réseau sur le serveur. Ex : pour accéder au répertoire 'Scripts' sur le disque D d'un serveur, on indique 'd:\Scripts' et la fonction passera par le partage administratif 'd$\scripts'
	 *  - on peut aussi indiquer un partage SMB complet
	 * @param string $server Serveur sur lequel est stocké le chemin. Ce serveur doit être soit 'localhost' pour le serveur local, soit un serveur accessible par smb (partage Windows). Ce peut être un nom ou une adresse IP. Le serveur peut-être directement renseigné dans $path (facultatif)
	 * @param string $prefix Préfixe du point de montage dans /mnt. (facultatif)
	 */
	public function __construct($path, $server = null, $prefix = null){
		if (!empty($server)) {
			$this->server = $server;
			$this->path = $path;
		}else{
			// Possibilité d'optimisation : Il y a probablement plus efficace pour récupérer le nom du serveur et le chemin du partage...
			if (mb_substr($path, 0, 2) == '\\\\'){
				$tmpPath = mb_substr($path, 2);
				//$tmpPath = str_replace('\\\\', '', $path);
				$tab = explode('\\', $tmpPath);
				$this->server = $tab[0];
				unset($tab[0]);
				$this->path = implode('\\', $tab);
			}else{
				$this->server = 'localhost';
				$this->path = $path;
			}
		}
		if (!empty($prefix)) $this->prefix = $prefix;
		$path = str_replace(':', '_', $this->path);
		$path = str_replace('\\', '_', $path);
		$path = str_replace('$', '_', $path);
		$path = str_replace(' ', '_', $path);
		$this->mountName = ($this->server == 'localhost') ? $this->path : '/mnt/'.$this->prefix.'_'.$this->server.'_'.$path;
		$this->mount();
	}

	/**
	 * Retourne le statut du montage
	 * @return boolean
	 */
	public function getIsMounted() {
		return $this->isMounted;
	}

	/**
	 * Vérifie si le partage est monté et accessible.
	 *
	 * Si le serveur est localhost, on vérifie juste que l'accès est possible.
	 *
	 * @return bool
	 */
	protected function isMounted(){
		if ($this->server == 'localhost'){
			if (file_exists($this->path)){
				$this->isMounted = true;
				return true;
			}
		}elseif (file_exists($this->mountName)){
			/* On teste si ce répertoire est un répertoire vide (non monté)
			* Ce qui veut dire que si on monte un partage Windows, il faut s'assurer que celui-ci ne soit pas vide, sans quoi la fonction va retourner une erreur.
			*/
			$ret = exec('mount | grep '.$this->mountName.' 2>&1', $output);
			if (!empty($ret)){
				$this->isMounted = true;
				return true;
			}
		}
		$this->isMounted = false;
		return false;
	}

	/**
	 * Monte le partage SMB
	 *
	 * @return bool
	 */
	protected function mount(){
		if (!$this->isMounted()){
			/* On vérifie que les répertoires sont bien montés sous Linux, et on les monte le cas échéant. */
			/* Si le répertoire n'est pas créé, on le crée. */
			if (!file_exists($this->mountName)){
				$ret = exec('mkdir '.$this->mountName.' 2>&1', $output);
				if (!file_exists($this->mountName)){
					new Alert('error', 'Impossible de créer le point de montage <code>'.$this->mountName.'</code> pour la raison suivante : <br /><code>'.$ret.'</code>.<br />Assurez-vous que le répertoire /mnt est accessible en écriture à tous les utilisateurs Linux, ou que vous avez les droits de créer un répertoire sur le serveur local.');
					return false;
				}
			}
			/* On regarde si le montage est actif. */
			$ret = exec('mount | grep '.$this->mountName.' 2>&1', $output);
			if (empty($ret)){
				/* On monte le partage */
				$share = str_replace(':', '$', $this->path);
				$share = str_replace('\\', '/', $share);
				$winShare = '//'.$this->server.'/'.$share;
				$cmd = 'sudo mount -t cifs "'.$winShare.'" '.$this->mountName.' -o uid=administrateur,gid=www-data,credentials=/etc/dfs_creds.conf 2>&1';
				$ret = exec($cmd, $retArray, $varRet);
				if (!empty($ret)){
					new Alert('error', 'Impossible de monter le partage <code>'.$winShare.'</code>.<br />Assurez-vous que l\'utilisateur Apache a les droits d\'invoquer sudo mount sans mot de passe.<br />'.$ret.'<br />'.$varRet);
					return false;
				}
			}
		}
		$this->isMounted = true;
		return true;
	}

	/**
	 * Retourne le contenu récursif d'un répertoire sous forme de tableau
	 *
	 * Si $absolutePath est à true, les fichiers sont retournés avec leur chemin absolu dans un tableau séquentiel
	 * Si $absolutePath est à false (défaut), les fichiers sont retournés dans un tableau associatif dont les clés sont les sous-répertoires
	 *
	 * @param string $path Chemin à inventorier dans le chemin de base de l'objet Fs instancié (facultatif)
	 * @param string $extension Pour ne répertorier que les fichiers ayant une certaine extension (facultatif)
	 * @param bool   $absolutePath Retourne un tableau à plat avec tous les fichiers, avec leur chemin absolu (facultatif)
	 *
	 * @return string[]|array[]
	 */
	public function getFilesInDir($path = null, $extension = null, $absolutePath = false){
		$result = array();
		$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$path = ltrim($path, DIRECTORY_SEPARATOR);
		$dir = $this->mountName.DIRECTORY_SEPARATOR.$path;
		$cDir = scandir($dir);
		foreach ($cDir as $key => $value){
			if (!in_array($value,array(".",".."))){
				if (is_dir($dir . $value)){
					if ($absolutePath){
						$result = array_merge($this->getFilesInDir($path . $value, $extension, $absolutePath), $result);
					}else{
						$result[] = $this->getFilesInDir($path . $value, $extension, $absolutePath);
					}
				}else{
					if ((!empty($extension) and pathinfo($value, PATHINFO_EXTENSION) == $extension) or empty($extension)){
						$result[] = ($absolutePath) ? $dir.$value : $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Vérifie si un fichier existe
	 *
	 * @param string $fileName Nom du fichier
	 *
	 * @return bool
	 */
	public function fileExists($fileName){
		return file_exists($this->mountName.DIRECTORY_SEPARATOR.$fileName);
	}

	/**
	 * Retourne des informations sur un fichier
	 *
	 * Cette méthode est lente à exécuter, aussi vaut-il mieux filtrer les champs à retourner afin de n'obtenir que ceux qui seront utilisés.
	 *
	 * Propriétés retournées :
	 * - dateCreated
	 * - dateModified
	 * - size
	 * - extension
	 * - type
	 * - chmod
	 * - writable
	 * - owner
	 * - groupOwner
	 *
	 * @param string $fileName Nom du fichier
	 * @param string[]|string  $filters Filtres facultatifs sous forme de chaîne ou de tableau séquentiel (facultatif)
	 *
	 * @return bool|object
	 */
	public function getFileMeta($fileName, $filters = array()){
		if (!is_array($filters)){
			$filters = array($filters);
		}
		$meta = new stdClass;
		/*$meta->dateCreated = 0;
		$meta->dateModified = 0;
		$meta->size = 0;
		$meta->extension = null;
		$meta->type = null;
		$meta->chmod = null;
		$meta->writable = false;
		$meta->owner = null;
		$meta->groupOwner = null;*/
		$file = $this->mountName.DIRECTORY_SEPARATOR. $fileName;
		if (!file_exists($file)){
			new Alert('debug', '<code>Fs->fileMeta()</code> : Le fichier <code>'.$file.'</code> n\'existe pas !');
			return false;
		}
		if ((!empty($filters) and (in_array('dateCreated', $filters) or in_array('dateModified', $filters) or in_array('size', $filters))) or empty($filters)){
			$stat = stat($file);
			$meta->dateCreated = $stat['ctime'];
			$meta->dateModified = $stat['mtime'];
			$meta->size = $stat['size'];
		}
		if ((!empty($filters) and in_array('extension', $filters)) or empty($filters)){
			$meta->extension = pathinfo($fileName, PATHINFO_EXTENSION);
		}
		if ((!empty($filters) and in_array('type', $filters)) or empty($filters)){
			$meta->type = mime_content_type($file);
		}
		if ((!empty($filters) and in_array('chmod', $filters)) or empty($filters)){
			$meta->chmod = decoct(fileperms($file) & 0777);
		}
		if ((!empty($filters) and in_array('writable', $filters)) or empty($filters)){
			$meta->writable = is_writable($file);
		}
		if ((!empty($filters) and in_array('owner', $filters)) or empty($filters)){
			$meta->owner = posix_getpwuid(fileowner($file))['name'];
		}
		if ((!empty($filters) and in_array('groupOwner', $filters)) or empty($filters)){
			$meta->groupOwner = posix_getgrgid(filegroup($file))['name'];
		}
		return $meta;
	}

	/**
	 * Lit un fichier
	 *
	 * @param string $fileName Nom du fichier
	 * @param string $format Format de retour (array ou string) - par défaut, renvoie toutes les lignes dans un tableau (facultatif)
	 * @param bool   $ignoreNewLines N'ajoute pas les caractères de passage à la ligne à la fin de chaque ligne dans le tableau retourné (facultatif)
	 * @param bool   $skipEmptyLines Ignore les lignes vides (désactivé par défaut) (facultatif)
	 *
	 * @return string[]|string|bool renvoie le fichier dans le format de demandé, ou false en cas d'erreur
	 */
	public function readFile($fileName, $format = 'array', $ignoreNewLines = true, $skipEmptyLines = false){
		$opt = null;
		if ($ignoreNewLines and $skipEmptyLines){
			$opt = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
		}elseif($ignoreNewLines){
			$opt = FILE_IGNORE_NEW_LINES;
		}elseif($skipEmptyLines){
			$opt = FILE_SKIP_EMPTY_LINES;
		}
		switch ($format){
			case 'array':
				$file=@file($this->mountName . DIRECTORY_SEPARATOR . $fileName, $opt );
				break;
			case 'string':
				$file = file_get_contents($this->mountName . DIRECTORY_SEPARATOR . $fileName);
				break;
			default:
				new Alert('debug', '<code>FileSytem\Fs->fileRead()</code> : Le format <code>'.$format.'</code> n\'est pas dans la liste des formats autorisés !');
				return false;
		}
		if ($file === false){
			new Alert('error', 'Impossible de lire le fichier <code>'.$fileName.'</code> !');
			return false;
		}
		return $file;
	}

	/**
	 * Crée un fichier si celui-ci n'existe pas
	 *
	 * La date de modification du fichier sera modifiée à l'heure actuelle si celui-ci existe
	 * @param string $fileName Nom du fichier à créer si inexistant
	 *
	 * @return bool
	 */
	public function touchFile($fileName){
		$ret = touch($this->mountName . DIRECTORY_SEPARATOR .$fileName);
		if (!$ret) new Alert('error', 'Impossible de trouver ou de créer le fichier <code>'.$fileName.'</code>');
		return $ret;
	}

	/**
	 * Ecrit dans un fichier
	 *
	 * @param string       $fileName Nom du fichier
	 * @param array|string $content Contenu du fichier
	 * @param bool         $append Ajoute le contenu à la suite du fichier au lieu de l'écraser si celui-ci existe (facultatif)
	 * @param bool         $backupFile Crée un backup du fichier avec l'extension .backup (facultatif)
	 *
	 * @return bool
	 */
	public function writeFile($fileName, $content, $append = false, $backupFile = false){
		if ($backupFile){
			if (!@copy($this->mountName . DIRECTORY_SEPARATOR . $fileName, $this->mountName . DIRECTORY_SEPARATOR . $fileName.'.backup')){
				$error= error_get_last();
				new Alert('error', 'Impossible de faire un backup du fichier <code>'.$fileName.'</code> !<br>'.$error['message']);
				return false;
			}
		}
		// N'oublions pas les retours à la ligne à la fin de chaque ligne.
		if (is_array($content)){
	    array_walk($content, function(&$value){
		    $value .= "\r\n";
	    });
		}else{
			$content .= "\r\n";
		}
		$fileAppend = ($append) ? FILE_APPEND : null;
		$ret = file_put_contents($this->mountName . DIRECTORY_SEPARATOR . $fileName, $content, $fileAppend);
		if ($ret === false){
			new Alert('error', 'Impossible d\'écrire dans le fichier <code>'.$fileName.'</code> !');
			return false;
		}
		new Alert('success', 'Les modifications ont été enregistrées dans le fichier <code>'.$fileName.'</code>');
		return true;
	}

	/**
	 * Lit les x dernières lignes d'un fichier
	 *
	 * @from <http://stackoverflow.com/questions/15025875/what-is-the-best-way-in-php-to-read-last-lines-from-a-file/15025877#15025877>
	 * @param string $fileName Nom du fichier
	 * @param int  $lines Nombre de lignes à retourner (facultatif)
	 * @param bool $adaptive (facultatif)
	 *
	 * @return bool|string[]
	 */
	public function tailFile($fileName, $lines = 1, $adaptive = true) {
		// Open file
		$f = @fopen($this->mountName . DIRECTORY_SEPARATOR . $fileName, "rb");
		if ($f === false){
			new Alert('error', 'Impossible d\'ouvrir le fichier <code>'.$fileName.'</code> !');
			return false;
		}

		// Sets buffer size
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {

			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");

		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {

			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);

		}

		// Close file and return
		fclose($f);

		return explode(PHP_EOL, trim($output));

	}

	/**
	 * Définit les permissions sur un fichier (chmod)
	 *
	 * Dans le doute, mieux vaut s'abstenir de faire ceci sur des fichiers Windows
	 *
	 * @param string  $fileName Nom du fichier
	 * @param int     $chmod    Chmod à appliquer au fichier
	 *
	 * @return bool
	 */
	public function setChmod($fileName, $chmod){
		$ret = exec('sudo chmod '.$chmod.' '.$this->mountName . DIRECTORY_SEPARATOR . $fileName.' 2>&1', $output);
		if (empty($ret)){
			return true;
		}
		new Alert('error', 'Impossible de changer les droits sur le fichier <code>'.$fileName.'</code>');
		return false;
	}

	/**
	 * retourne le nom du montage réseau
	 *
	 * @return string
	 */
	public function getMountName() {
		return $this->mountName;
	}
} 