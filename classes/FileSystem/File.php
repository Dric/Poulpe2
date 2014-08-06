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
 */
class File {
	
	protected $name = null;
	protected $fullName = null;
	protected $dateCreated = 0;
	protected $dateModified = 0;
	protected $size = 0;
	protected $extension = null;
	protected $fullType = null;
	protected $type = null;
	protected $chmod = 0;
	protected $advChmod = 0;
	protected $writable = false;
	protected $owner = null;
	protected $groupOwner = null;
	protected $linuxHidden = false;
	protected $parentFolder = null;
	protected $filters = array();

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
			$this->filters = $filters;
			if ((!empty($filters) and (in_array('dateCreated', $filters) or in_array('dateModified', $filters) or in_array('size', $filters))) or empty($filters)){
				$stat = stat($this->fullName);
				$this->dateCreated = $stat['ctime'];
				$this->dateModified = $stat['mtime'];
				$this->size = $this->getFileSize();
				if (!empty($this->filters)) $this->filters = array_unique(array_merge($this->filters,array('dateCreated', 'dateModified', 'size')), SORT_REGULAR);
			}
			if ((!empty($filters) and in_array('extension', $filters)) or empty($filters)){
				$this->extension = pathinfo($fileName, PATHINFO_EXTENSION);
			}
			if ((!empty($filters) and in_array('type', $filters)) or empty($filters)){
				/**
				 * @var \finfo $fInfo
				 */
				$this->fullType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->fullName);
				if (!empty($this->filters)) $this->filters[] = 'fullType';
				$this->type();
			}
			if ((!empty($filters) and in_array('chmod', $filters)) or empty($filters)){
				$this->chmod = (int)decoct(fileperms($this->fullName) & 0777);
				$this->advChmod = (int)substr(decoct(fileperms($this->fullName)),2);
				if (!empty($this->filters)) $this->filters[] = 'advChmod';
			}
			if ((!empty($filters) and in_array('writable', $filters)) or empty($filters)){
				$this->writable = is_writable($this->fullName);
			}
			if ((!empty($filters) and in_array('owner', $filters)) or empty($filters)){
				$this->owner = posix_getpwuid(fileowner($this->fullName))['name'];
			}
			if ((!empty($filters) and in_array('groupOwner', $filters)) or empty($filters)){
				$this->groupOwner = posix_getgrgid(filegroup($this->fullName))['name'];
			}
			$this->linuxHidden = (substr($fileName, 0, 1) == '.') ? true : false;
			if (!empty($this->filters)) $this->filters[] = 'linuxHidden';
			$this->parentFolder = dirname($this->fullName);
			if (!empty($this->filters)) $this->filters[] = 'parentFolder';
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
	 * @return bool|float
	 */
	protected function getFileSize() {
		$size = filesize($this->fullName);
		if ($size === false) {
			$fp = fopen($this->fullName, 'r');
			if (!$fp) {
				return false;
			}
			$offset = PHP_INT_MAX - 1;
			$size = (float) $offset;
			if (!fseek($fp, $offset)) {
				return false;
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
		return ((!empty($this->filters) and in_array($prop, $this->filters)) or empty($filters) or in_array($prop, array('name', 'fullName'))) ? isset($this->$prop) : false;
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
		switch ($this->fullType){
			case 'directory':
				$ext = 'Répertoire';
				break;
			case 'text/plain':
				$ext = 'Fichier texte';
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
			case 'Installateur':
				return 'download';
			case 'Image ISO':
				return 'hdd-o';
			case 'Image':
				return 'image';
			case 'Raccourci':
				return 'share-square-o';
			case 'Document Word':
				return 'file-word-o';
			case 'Document Excel':
				return 'file-excel-o';
			case 'Document Powerpoint':
				return 'file-powerpoint-o';
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

	public function display(){
		$this->displayIcon();
		echo '&nbsp;'.$this->name;
	}

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