<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 05/08/14
 * Time: 12:06
 */

namespace FileSystem;


use Logs\Alert;

/**
 * Objet fichier
 *
 * @package FileSystem
 *
 * @property-read int $name
 * @property-read string $fullName
 * @property-read int $dateCreated
 * @property-read int $dateModified
 * @property-read int $size
 * @property-read string $extension
 * @property-read string $fullType
 * @property-read string $type
 * @property-read int $chmod
 * @property-read int $advChmod
 * @property-read bool $writable
 * @property-read string $owner
 * @property-read bool $linuxHidden
 * @property-read string $parentFolder
 *
 */
class File {

	protected $name = null;
	protected $fullName = null;
	protected $dateCreated = 0;
	protected $dateModified = 0;
	protected $size = 0;
	protected $extension = null;
	protected $encoding = null;
	protected $fullType = null;
	protected $type = null;
	protected $chmod = 0;
	protected $advChmod = 0;
	protected $writable = false;
	protected $owner = null;
	protected $groupOwner = null;
	protected $linuxHidden = false;
	protected $parentFolder = null;

	/**
	 * Construit un objet fichier
	 *
	 * @param string  $mountName Répertoire du fichier
	 * @param string  $fileName Nom du fichier
	 * @param array   $filters Filtrage de propriétés, certaines d'entre elles pouvant être lentes à récupérer
	 */
	public function __construct($mountName, $fileName, array $filters = array()){
		$this->name = $fileName;
		$this->fullName = rtrim($mountName, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR. $fileName;
		if (file_exists($this->fullName)){
			if ((!empty($filters) and in_array('extension', $filters)) or empty($filters)){
				$this->extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
				if (empty($this->extension) and !is_dir($this->fullName)) $this->extension = strtolower(end(explode('.', $this->name)));
			}
			if ((!empty($filters) and in_array('writable', $filters)) or empty($filters)){
				$this->writable = is_writable($this->fullName);
			}
			if ((!empty($filters) and in_array('encoding', $filters)) or empty($filters)){
				exec('file -i ' . $this->fullName, $output);
				if (isset($output[0])){
					$ex = explode('charset=', $output[0]);
					$this->encoding = isset($ex[1]) ? $ex[1] : null;
				}else{
					$this->encoding = null;
				}
			}
			$this->linuxHidden = (substr($fileName, 0, 1) == '.') ? true : false;
			if (!empty($this->filters)) $this->filters[] = 'linuxHidden';
			$this->parentFolder = dirname($this->fullName);
			if (!empty($this->filters)) $this->filters[] = 'parentFolder';
			/**
			 * On teste si stat retourne une erreur.
			 * Si oui, il y a de fortes chances que ce soit à cause d'un fichier trop gros pour être géré en PHP 32 bits.
			 *
			 * Dans ce cas, il faut passer par la commande linux `stat` pour récupérer les infos.
			 */
			$stat = @stat($this->fullName);
			if ($stat === false){
				exec('stat -c "%s %a %U %G %W %Y" "'.$this->fullName.'"', $out);
				list($this->size, $this->chmod, $this->owner, $this->groupOwner, $this->dateCreated, $this->dateModified) = explode(' ', $out[0]);
				if ((!empty($filters) and in_array('type', $filters)) or empty($filters)){
					/**
					 * @warning Il se peut que cette commande ne renvoie pas le bon type MIME.
					 *  Dans ce cas, il faut faire une mise à jour des types MIME du serveur avec `sudo update-mime-database /usr/share/mime`
					 */
					exec('file -b --mime-type "'.$this->fullName.'"', $out);
					$this->fullType = end($out);
					$this->type();
				}
			}else{
				if ((!empty($filters) and (in_array('dateCreated', $filters) or in_array('dateModified', $filters) or in_array('size', $filters))) or empty($filters)){
					$this->dateCreated = $stat['ctime'];
					$this->dateModified = $stat['mtime'];
					$this->size = $this->getFileSize();
				}
				if ((!empty($filters) and in_array('type', $filters)) or empty($filters)){
					/**
					 * @var \finfo $fInfo
					 */
					$this->fullType = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->fullName);
					$this->type();
				}
				if ((!empty($filters) and in_array('chmod', $filters)) or empty($filters)){
					$this->chmod = (int)decoct(@fileperms($this->fullName) & 0777);
					$this->advChmod = (int)substr(decoct(@fileperms($this->fullName)),2);
				}
				if ((!empty($filters) and in_array('owner', $filters)) or empty($filters)){
					$this->owner = posix_getpwuid(@fileowner($this->fullName))['name'];
				}
				if ((!empty($filters) and in_array('groupOwner', $filters)) or empty($filters)){
					$this->groupOwner = posix_getgrgid(@filegroup($this->fullName))['name'];
				}
			}
		}else{
			new Alert('debug', '<code>File Constructor</code> : le fichier <code>'.$this->fullName.'</code> n\'existe pas !');
			$this->name = null;
			$this->fullName = null;
		}
	}

	/**
	 * Récupère la taille d'un fichier
	 *
	 * Sur des systèmes 32bits, la taille des fichiers > 2 Go est mal retournée (nombre négatif)
	 * On passe donc par cette fonction pour obtenir la taille réelle.
	 *
	 * @link <http://stackoverflow.com/a/5501987/1749967>
	 *
	 * @return float
	 */
	protected function getFileSize() {
		$size = @filesize($this->fullName);
		if ($size === false) {
			$fp = @fopen($this->fullName, 'r');
			if (!$fp) {
				return 0;
			}
			$offset = PHP_INT_MAX - 1;
			$size = (float) $offset;
			if (!fseek($fp, $offset)) {
				return 0;
			}
			$chunksize = 8192;
			while (!feof($fp)) {
				$size += strlen(fread($fp, $chunksize));
			}
		} elseif ($size < 0) {
			// Handle overflowed integer...
			$size = sprintf("%u", $size);
		}
		return floatval($size);
	}
	
	public function __isset($prop){
		return isset($this->$prop);
	}
	
	public function __get($prop){
		if (isset($this->$prop)) return $this->$prop;
		return null;
	}

	/**
	 * Détermine le type "commun" du fichier suivant son type MIME et/ou son extension de fichier.
	 *
	 * @warning Le véritable format du fichier n'est pas vérifié.
	 */
	protected function type(){
		if ($this->fullType == 'application/octet-stream'){
			$this->hackTypes();
		}
		switch ($this->fullType){
			case 'directory':
				$ext = 'Répertoire';
				break;
			case 'text/plain':
				switch ($this->extension){
					case 'ini':
					case 'cfg':
						$ext = 'Paramétrage';
						break;
					case 'nfo':
						$ext = 'Information';
						break;
					default:
						$ext = 'Fichier texte';
				}
				break;
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$ext = 'Document Word';
				break;
			case 'application/pdf':
				$ext = 'PDF';
				break;
			case 'application/vnd.ms-excel':
				$ext = 'Document Excel';
				break;
			case 'application/vnd.ms-powerpoint':
				$ext = 'Document Powerpoint';
				break;
			case 'application/zip':
			case 'application/x-gzip':
				$ext = 'Archive';
				break;
			case 'application/x-iso9660-image':
				$ext = 'Image ISO';
				break;
			case 'application/x-executable':
				$ext = 'Exécutable';
				break;
			case 'application/x-dosexec':
				if (strtolower($this->extension) == 'exe'){
					$ext = 'Exécutable';
				}else{
					$ext = 'Composant';
				}
				break;
			case 'application/octet-stream':
				switch ($this->extension){
					case 'lnk':
						$ext = 'Raccourci';
						break;
					case 'iso':
						$ext = 'Image ISO';
						break;
					case 'pst':
						$ext = 'Archives Outlook';
						break;
					default:
						$ext = 'Fichier';
						break;
				}
				break;
			case 'application/pgp-keys':
				$ext = 'Certificat';
				break;
			case 'application/x-debian-package':
				$ext = 'Installeur';
				break;
			case 'audio/mpeg':
				$ext = 'Musique';
				break;
			default:
				if (preg_match('/text\/(html|x-.*)/i', $this->fullType)){
					$ext = 'Fichier code';
				}elseif (preg_match('/image\/.*/i', $this->fullType)){
					$ext = 'Image';
				}elseif (preg_match('/video\/.*/i', $this->fullType)){
					$ext = 'Vidéo';
				}else{
					$ext = 'Fichier';
				}

		}
		$this->type = $ext;
	}

	/**
	 * Permet d'affecter les bons types aux vidéos quand elles sont trop grandes pour un système 32bits et que les types MIME du système ne sont pas à jour.
	 * Cette fonction ne devrait en théorie pas être utilisée.
	 */
	protected function hackTypes(){
		if ($this->fullType == 'application/octet-stream'){
			switch ($this->extension){
				case 'mkv':
					$this->fullType = 'video/x-matroska';
					break;
				case 'mp4':
					$this->fullType = 'video/mp4';
			}
		}
	}

	/**
	 * Retourne la classe Font Awesome de l'icône de fichier
	 *
	 * @return string
	 */
	public function getIcon(){
		switch ($this->type){
			case 'Répertoire':
				return 'folder';
			case 'Fichier texte':
				return 'file-text-o';
			case 'Archive':
				return 'file-archive-o';
			case 'Archives Outlook':
				return 'envelope-o';
			case 'Exécutable':
				return 'cog';
			case 'Composant':
				return 'cube';
			case 'Certificat':
				return 'key';
			case 'Fichier code':
				return 'file-code-o';
			case 'Paramétrage':
				return 'sliders';
			case 'Installateur':
				return 'download';
			case 'Image ISO':
				return 'hdd-o';
			case 'Image':
				return 'image';
			case 'Information':
				return 'info-circle';
			case 'Raccourci':
				return 'share-square-o';
			case 'Document Word':
				return 'file-word-o';
			case 'Document Excel':
				return 'file-excel-o';
			case 'Document Powerpoint':
				return 'file-powerpoint-o';
			case 'Musique':
				return 'music';
			case 'PDF':
				return 'file-pdf-o';
			case 'Vidéo':
				return 'film';
			default:
				return 'file-o';
		}
	}

	/**
	 * Affiche l'icône en rapport avec le fichier
	 */
	public function displayIcon(){
		?><span class="fa fa-<?php echo $this->getIcon(); ?>"></span>&nbsp;<?php
	}

	/**
	 * Affiche le nom et l'icône du fichier
	 */
	public function display(){
		$this->displayIcon();
		echo '&nbsp;'.$this->name;
	}

	/**
	 * Retourne la couleur à appliquer au fichier lors de l'affichage
	 * @return string
	 */
	public function colorClass(){
		$class = '';
		switch ($this->type){
			case 'Répertoire':
				$class = 'text-warning';
		}
		if ($this->linuxHidden) $class = 'text-muted';
		return $class;
	}
} 