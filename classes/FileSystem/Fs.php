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
 * Classe de gestion des points de montage de partages réseaux et d'opérations sur des fichiers
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
	 * Racine du partage SMB
	 * @var string
	 */
	protected $SMBRootShare = null;

	/**
	 * Sous-répertoires du partage SMB
	 * @var string
	 */
	protected $SMBSubFolders = null;

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
				if (count($tab) > 1){
					$this->SMBRootShare = $tab[1];
					unset($tab[1]);
					$this->SMBSubFolders = implode('\\', $tab);
					$this->path = $this->SMBRootShare . '\\' . $this->SMBSubFolders;
					$this->SMBSubFolders = str_replace('\\', '/', $this->SMBSubFolders);
				}else{
					$this->path = implode('\\', $tab);
				}
			}else{
				$this->server = 'localhost';
				$this->path = $path;
			}
		}
		if (!empty($prefix)) $this->prefix = $prefix;
		// Pour éviter les erreurs, on ne monte que la racine du partage.
		if (!empty($this->SMBRootShare)){
			$path = $this->SMBRootShare;
		}else{
			$path = $this->path;
		}
		$path = str_replace(':', '_', $path);
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
	 * @warning : retournera `false` si le répertoire à monter est vide
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
			$ret = exec('mount | grep -w '.$this->mountName.' 2>&1', $output);
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
				// On crée le point de montage avec les droits `777` pour que tout le monde puisse y accéder, y compris en écriture
				$ret = exec('mkdir -m 777 '.$this->mountName.' 2>&1', $output);
				if (!file_exists($this->mountName)){
					new Alert('error', 'Impossible de créer le point de montage <code>'.$this->mountName.'</code> pour la raison suivante : <br /><code>'.$ret.'</code>.<br />Assurez-vous que le répertoire /mnt est accessible en écriture à tous les utilisateurs Linux, ou que vous avez les droits de créer un répertoire sur le serveur local.');
					return false;
				}
			}
			/* On regarde si le montage est actif. */
			$ret = exec('mount | grep -w '.$this->mountName.' 2>&1', $output);
			if (empty($ret)){
				/* On monte le partage */
				$share = (!empty($this->SMBRootShare)) ? $this->SMBRootShare : $this->path;
				$share = str_replace(':', '$', $share);
				$share = str_replace('\\', '/', $share);
				$winShare = '//'.$this->server.'/'.$share;

				// Pour s'authentifier sur les serveurs Windows, on utilise un fichier qui contient les identifiants de connexion aux serveurs Windows.
				$dfsCredsFile = \Front::getAbsolutePath().DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'FileSystem'.DIRECTORY_SEPARATOR.'dfs_creds.conf';
				if (!file_exists($dfsCredsFile)){
					new Alert('error', 'Le fichier <code>'.$dfsCredsFile.'</code> permettant de s\'authentifier auprès des serveurs Windows est introuvable !<br> Veuillez créer ce fichier et saisir dedans ces 3 lignes : <pre><code>username=&lt;nom_user&gt;<br>password=&lt;mot_de_passe&gt;<br>domain=&lt;nom_de_domaine&gt;</code></pre>');
					return false;
				}
				// Montage du partage. En cas d'erreur, il se peut que le package permettant les montages CIFS ne soit pas installé sur le serveur.
				$cmd = 'sudo mount -t cifs "'.$winShare.'" '.$this->mountName.' -o soft,uid=www-data,gid=www-data,credentials='.$dfsCredsFile.' 2>&1';
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
	 * Si `$absolutePath` est à `true`, les fichiers sont retournés avec leur chemin absolu dans un tableau séquentiel
	 * Si `$absolutePath` est à `false` (défaut), les fichiers sont retournés dans un tableau associatif dont les clés sont les sous-répertoires
	 *
	 * Les répertoires ne contenant rien ou ne contenant pas de fichiers ayant l'extension requise ne sont pas retournés.
	 *
	 * @param string $path          Chemin à inventorier dans le chemin de base de l'objet Fs instancié (facultatif)
	 * @param string $extension     Pour ne répertorier que les fichiers ayant une certaine extension (facultatif)
	 * @param bool   $absolutePath  Retourne un tableau à plat avec tous les fichiers, avec leur chemin absolu (facultatif)
	 *
	 * @return string[]|array[]
	 */
	public function getRecursiveFilesInDir($path = null, $extension = null, $absolutePath = false){
		$result = array();
		$path = ltrim($path, DIRECTORY_SEPARATOR);
		$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		$dir = $mountName.DIRECTORY_SEPARATOR.$path;
		$cDir = scandir($dir);
		foreach ($cDir as $key => $value){
			if (!in_array($value,array(".",".."))){
				if (is_dir($dir . $value)){
					if ($absolutePath){
						$result = array_merge($this->getRecursiveFilesInDir($path . $value, $extension, $absolutePath), $result);
					}else{
						$result[$value] = $this->getRecursiveFilesInDir($path . $value, $extension, $absolutePath);
					}
				}else{
					if ((!empty($extension) and pathinfo($value, PATHINFO_EXTENSION) == $extension) or empty($extension)){
						$result[$value] = ($absolutePath) ? $dir.$value : $value;
					}
				}
			}
		}
		// La fonction array_filter sans autre paramètre que le tableau supprime les éléments vides
		return array_filter($result);
	}

	/**
	 * Retourne les fichiers et éventuellement les sous-répertoires présents dans un répertoire
	 *
	 * Cette fonction n'est pas récursive
	 *
	 * @param string    $path       Chemin à inventorier dans le chemin de base de l'objet Fs instancié (facultatif)
	 * @param string    $extension  Seuls les fichiers portant cette extension seront retournés (facultatif)
	 * @param string[]  $filters    Filtres de propriétés à appliquer (facultatif)
	 * @param bool      $filesOnly  Ne retourne que les fichiers (facultatif, `false` par défaut)
	 *
	 * @return array Objets fichiers avec les propriétés suivantes :
	 *  - `name` :      Nom du fichier
	 *  - `type` :      `folder` ou `file`
	 *  - `hidden` :    Fichier caché
	 *  - `extension` : Extension du fichier (propriété disponible seulement pour les fichiers non cachés)
	 */
	public function getFilesInDir($path = null, $extension = null, $filters = array(), $filesOnly = false){
		$result = array();
		$path = ltrim($path, DIRECTORY_SEPARATOR);
		$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		$dir = $mountName.DIRECTORY_SEPARATOR.$path;
		$cDir = scandir($dir);
		foreach ($cDir as $value){
			if (!in_array($value,array(".","..")) and (($filesOnly and !is_dir($dir . $value)) or !$filesOnly)){
				if ((!empty($extension) and pathinfo($value, PATHINFO_EXTENSION) == $extension) or empty($extension)){
					$result[] = new File($dir, $value, $filters);
				}
			}
		}
		return $result;
	}

	/**
	 * Vérifie si un fichier existe, et retourne son nom avec sa casse si tel est le cas
	 *
	 * @param string  $fileName Nom du fichier
	 * @param bool    $caseSensitive Effectue une recherche en respectant la casse ou non (non par défaut)
	 * @return bool|string retourne `false` si le fichier n'existe pas, retourne le nom du fichier avec sa casse sinon.
	 *
	 * @from <http://stackoverflow.com/a/3964927/1749967>
	 */
	public function fileExists($fileName, $caseSensitive = false){
		$fileName = ltrim($fileName, DIRECTORY_SEPARATOR);
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		$fileName = $mountName.DIRECTORY_SEPARATOR.$fileName;
		if(file_exists($fileName)) {
			return $fileName;
		}
		if($caseSensitive) return false;

		// Handle case insensitive requests
		$directoryName = dirname($fileName);
		$fileArray = glob($directoryName . '/*', GLOB_NOSORT);
		$fileNameLowerCase = strtolower($fileName);
		foreach($fileArray as $file) {
			if(strtolower($file) == $fileNameLowerCase) {
				return str_replace($mountName.DIRECTORY_SEPARATOR, '', $file);
			}
		}
		return false;
	}

	/**
	 * Retourne des informations sur un fichier
	 *
	 * Cette méthode est lente à exécuter, aussi vaut-il mieux filtrer les champs à retourner afin de n'obtenir que ceux qui seront utilisés.
	 *
	 * @see File
	 * @param string            $fileName Nom du fichier
	 * @param string[]|string   $filters  Filtres facultatifs sous forme de chaîne ou de tableau séquentiel (facultatif)
	 *
	 * @return bool|File
	 */
	public function getFileMeta($fileName, $filters = array()){
		if (!is_array($filters)) $filters = array($filters);
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		$file = new File($mountName, $fileName, $filters);
		if (empty($file->name)){
			new Alert('error', 'Le fichier <code>'.$fileName.'</code> n\'existe pas !');
			return false;
		}
		return $file;
	}

	/**
	 * Lit un fichier
	 *
	 * @param string $fileName        Nom du fichier
	 * @param string $format          Format de retour (array ou string) - par défaut, renvoie toutes les lignes dans un tableau (facultatif)
	 * @param bool   $ignoreNewLines  N'ajoute pas les caractères de passage à la ligne à la fin de chaque ligne dans le tableau retourné (facultatif)
	 * @param bool   $skipEmptyLines  Ignore les lignes vides (désactivé par défaut) (facultatif)
	 *
	 * @return string[]|string|bool renvoie le fichier dans le format de demandé, ou false en cas d'erreur
	 */
	public function readFile($fileName, $format = 'array', $ignoreNewLines = true, $skipEmptyLines = false){
		$opt = null;
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		if ($ignoreNewLines and $skipEmptyLines){
			$opt = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
		}elseif($ignoreNewLines){
			$opt = FILE_IGNORE_NEW_LINES;
		}elseif($skipEmptyLines){
			$opt = FILE_SKIP_EMPTY_LINES;
		}
		switch ($format){
			case 'array':
				$file=@file($mountName . DIRECTORY_SEPARATOR . $fileName, $opt );
				break;
			case 'string':
				$file = file_get_contents($mountName . DIRECTORY_SEPARATOR . $fileName);
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
	 * La date de modification du fichier sera modifiée à l'heure actuelle si celui-ci existe.
	 * La fonction `touch` de php ne semble pas fonctionner si l'utilisateur apache n'est pas propriétaire du fichier. On emploie donc la commande Unix
	 *
	 * @param string $fileName Nom du fichier à créer si inexistant
	 *
	 * @return bool
	 */
	public function touchFile($fileName){
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		$fileName = $mountName . DIRECTORY_SEPARATOR .$fileName;
		$ret = exec("touch {$fileName}");
		if (!empty($ret)) new Alert('error', 'Impossible de trouver ou de créer le fichier <code>'.$fileName.'</code>.<br>Erreur : <code>'.$ret.'</code>');
		return (empty($ret)) ? true : false;
	}

	/**
	 * Ecrit dans un fichier
	 *
	 * @param string       $fileName    Nom du fichier
	 * @param array|string $content     Contenu du fichier
	 * @param bool         $append      Ajoute le contenu à la suite du fichier au lieu de l'écraser si celui-ci existe (facultatif)
	 * @param bool         $backupFile  Crée un backup du fichier avec l'extension .backup (facultatif)
	 *
	 * @return bool
	 */
	public function writeFile($fileName, $content, $append = false, $backupFile = false){
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		if ($backupFile){
			if (!@copy($mountName . DIRECTORY_SEPARATOR . $fileName, $mountName . DIRECTORY_SEPARATOR . $fileName.'.backup')){
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
		$ret = file_put_contents($mountName . DIRECTORY_SEPARATOR . $fileName, $content, $fileAppend);
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
	 *
	 * @param string  $fileName Nom du fichier
	 * @param int     $lines    Nombre de lignes à retourner (facultatif)
	 * @param bool    $adaptive (facultatif)
	 *
	 * @return bool|string[]
	 */
	public function tailFile($fileName, $lines = 1, $adaptive = true) {
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		// Open file
		$f = @fopen($mountName . DIRECTORY_SEPARATOR . $fileName, "rb");
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
	 * @warning Dans le doute, mieux vaut s'abstenir de faire ceci sur des fichiers Windows
	 *
	 * @param string  $fileName Nom du fichier
	 * @param int     $chmod    Chmod à appliquer au fichier
	 *
	 * @return bool
	 */
	public function setChmod($fileName, $chmod){
		$mountName = (!empty($this->SMBSubFolders)) ? $this->mountName.DIRECTORY_SEPARATOR.$this->SMBSubFolders : $this->mountName;
		$ret = exec('sudo chmod '.$chmod.' '.$mountName . DIRECTORY_SEPARATOR . $fileName.' 2>&1', $output);
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