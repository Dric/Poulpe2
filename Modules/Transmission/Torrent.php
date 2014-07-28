<?php

namespace Modules\Transmission;
use Forms\Fields\Button;
use Forms\Fields\Hidden;
use Forms\Fields\Select;
use Forms\Form;
use Sanitize;
use Users\ACL;

/**
* CLasse de torrent
* 
* Cette classe reprend une partie des propriétés de l'objet torrent transmis par la classe transmissionRPC
* 
* @see <https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt>
* @package Modules\Transmission
*/
Class Torrent{
	
	/**
	* ID du torrent
	* @var int
	*/
	protected $id = 0;
	
	/**
	* Nom du torrent
	* @var string
	*/
	protected $name = '';
	
	/**
	* Timestamp de date d'ajout du torrent dans le client bt
	* @var int
	*/
	protected $addedDate = 0;
	
	/**
	* Timestamp de date de fin de téléchargement
	* @var int
	*/
	protected $doneDate = 0;
	
	/**
	* Statut du torrent
	* @var int
	* 
	* 0: Arrêté (aucune activité)
  * 1: En attente de vérification
  * 2: En cours de vérification
  * 3: En attente de téléchargement
  * 4: En cours de téléchargement
  * 5: En attente de partage
  * 6: En cours de partage
	*/
	protected $status = 0;
	
	/**
	* Libellés du statut
	* @var array()
	*/
	protected $statusLabels = array(
		0 => 'Arrêté',
		1 => 'En attente de vérification',
  	2 => 'En cours de vérification',
		3 => 'En attente de téléchargement',
		4 => 'En cours de téléchargement',
		5 => 'En attente de partage',
		6 => 'En cours de partage'
	);
	
	/**
	* Classe CSS à affecter au statut
	* @var array
	*/
	protected $statusCSSClass = array(
		0 => 'label-default',
		1 => 'label-danger',
  	2 => 'label-danger',
		3 => 'label-primary',
		4 => 'label-primary',
		5 => 'label-warning',
		6 => 'label-warning'
	);
	
	/**
	* Taille totale en octets
	* @var int
	*/
	protected $totalSize = 0;
	
	/**
	* Répertoire réel de téléchargement
	* @var string
	*/
	protected $downloadDir = '';
	
	/**
	* Libellés des répertoires de téléchargements
	* @var array()
	* 
	*/
	protected $downloadDirs = array();
	
	/**
	* Nombre d'octets partagés (envoyés vers d'autres peers)
	* @var int
	*/
	protected $uploadedEver = 0;
	
	/**
	* Torrent terminé ou non
	* @var bool
	*/
	protected $isFinished = false;
	
	/**
	* Nombre d'octets avant la fin du téléchargement
	* @var int
	*/
	protected $leftUntilDone = 0;
	
	/**
	* Pourcentage de téléchargement en décimal (de 0 à 1)
	* @var float
	*/
	protected $percentDone = 0;
	
	/**
	* Limite max de ratio partage/téléchargement
	* @var float
	*/
	protected $ratioLimit = 1;
	
	/**
	* Pourcentage d'accomplissement du ratio partage/téléchargement en décimal (de 0 à 1)
	* @var float
	*/
	protected $ratioPercentDone = 0;
	
	/**
	* Fichiers téléchargés par le torrent
	* @var array
	*/
	protected $files = array();
	
	/**
	* Temps estimé avant la fin du téléchargement
	* @var int
	*/
	protected $eta = 0;
	
	/**
	* Pourcentage de partage en décimal (de 0 à 1)
	* @var float
	*/
	protected $uploadRatio = 0;
	
	/**
	* Commentaire du torrent
	* @var string
	*/
	protected $comment = '';
	
	/**
	* Image du torrent (si présente)
	* @var string
	*/
	protected $img = '';
	
	/**
	* NFO du torrent (fichier explicatif, si présent)
	* @var string
	*/
	protected $nfo = '';

	/**
	 * Construction de la classe
	 *
	 * @param object $RPCTorrent Objet de torrent renvoyé par la classe RPCTransmission
	 */
	public function __construct($RPCTorrent){
		$this->downloadDirs = Transmission::$downloadsDirs;
				
		$RPCprops = get_object_vars($RPCTorrent);

		foreach ($RPCprops as $prop => $value){
			if (isset($this->$prop)){
				$this->$prop = $value;
			}
		}
		$fileDesc = array();
		$torrentImg = array();
		$this->files = Sanitize::sortObjectList($this->files, 'name');
		foreach ($this->files as $file){
			$fileInfo = pathinfo($file->name);
			$level = count(explode('/', $fileInfo['dirname']));
			switch ($fileInfo['extension']){
				case 'nfo':
					if ((empty($fileDesc['source']) or $fileDesc['level'] > $level) and file_exists($this->downloadDir.'/'.$file->name)){
						$fileDesc['source'] = file_get_contents($this->downloadDir.'/'.$file->name);
						$fileDesc['level'] = $level;
					}
					break;
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'gif':
					if ((empty($torrentImg['source']) or $torrentImg['level'] > $level)  and file_exists($this->downloadDir.'/'.$file->name)){
						$torrentImg['source'] = $this->downloadDir.'/'.$file->name;
						$torrentImg['level'] = $level;
					}
					break;
			}
			$this->img = (!empty($torrentImg['source'])) ? urlencode($torrentImg['source']) : '';
			$this->nfo = (!empty($fileDesc['source'])) ? $fileDesc['source'] : '';
		}
	}
	
	/**
	* Permet d'accéder aux propriétés de la classe
	* @param string $prop Propriété
	* 
	* @return mixed
	*/
	public function __get($prop){
		return $this->get($prop);
	}

	public function __isset($prop){
		return isset($this->$prop);
	}
	
	/**
	* Met en forme et retourne les propriétés de la classe
	* 
	* Les propriétés de la classe étant privées, pour y accéder il suffit de demander la variable sans le préfixe '_'.
	* Ex : Pour obtenir la taille totale du torrent, qui est la propriété $totalSize, il suffit de demander $torrent->totalsize ou encore $this->get('totalSize') à l'intérieur de la classe
	* @param string $prop Propriété à retourner.
	* 
	* @return mixed
	*/
	protected function get($prop){
		switch ($prop){
			case 'addedDate':
			case 'doneDate':
				if ($this->$prop === 0){
					return 'Inconnu';
				}
				return Sanitize::date($this->$prop, 'dateTime');
			case 'rawDoneDate':
				return $this->doneDate;
			case 'totalSize':
			case 'leftUntilDone':
			case 'uploadedEver':
				return Sanitize::readableFileSize($this->$prop);
			case 'eta':
				return ($this->eta != -1) ? Sanitize::date($this->$prop, 'dateTime') : 'Inconnu';
			case 'isFinished':
				return ($this->isFinished or $this->percentDone === 1) ? true : false;
			case 'uploadRatio':
				return round($this->uploadRatio, 2);
			case 'percentDone':
				return ($this->percentDone != -1) ? round($this->percentDone*100, 1) : 0;
			case 'comment':
				return $msg = preg_replace('/((http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?)/', '<a href="\1" target="_blank">\1</a>', $this->comment);
			case 'id':
			case 'name':
			case 'nfo':
			case 'img':
				return $this->$prop;
			case 'files':
				return $this->files;
			case 'status':
				return $this->statusLabels[$this->status];
			case 'downloadDir':
				return (!array_search($this->downloadDir, $this->downloadDirs)) ? $this->downloadDir : array_search($this->downloadDir, $this->downloadDirs);
			case 'rawDownloadDir':
				return $this->downloadDir;
			case 'statusCSSClass':
				return $this->statusCSSClass[$this->status];
			case 'ratioPercentDone':
				return round(($this->uploadRatio/$this->ratioLimit)*100, 0);
			default:
				// Certaines propriétés étant des booléens, impossible de retourner false en cas de propriété inexistante.
				return 'Property not set !';
		}
	}
	
	/**
	* Affiche les information d'un torrent
	* 
	* @return void
	*/
	public function display($moduleId = 0){
		?>
		<div class="panel" id="torrent_<?php echo $this->id; ?>">
			<div class="panel-heading torrents">
				<h4><a data-toggle="collapse" data-parent="#torrent_<?php echo $this->id; ?>" href="#collapse_details_<?php echo $this->id; ?>"><?php echo $this->name; ?></a> <span class="label <?php echo $this->get('statusCSSClass'); ?>"><?php echo $this->get('status'); ?></span> <span class="label label-primary"><?php echo $this->get('downloadDir'); ?></span></h4>
				<div id="torrent-progress-bar-title_<?php echo $this->id; ?>" class="progress tooltip-bottom progress-torrents" title="Terminé à <?php echo $this->get('percentDone'); ?>%">
					<?php
					if ($this->get('percentDone') == 100){
						if ($this->get('ratioPercentDone') == 100){
							$barColor = 'default';
						}else{
							$barColor = 'warning';
						}
					?>
					<div id="torrent-progress-bar-seed_<?php echo $this->id; ?>" class="progress-bar progress-bar-<?php echo $barColor; ?>" role="progressbar" aria-valuenow="<?php echo $this->get('ratioPercentDone'); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $this->get('ratioPercentDone'); ?>%;">
						<span class="sr-only"><?php echo $this->get('ratioPercentDone'); ?>% Complete</span>
					</div>
					<?php }else{ ?>
					<div id="torrent-progress-bar-dl_<?php echo $this->id; ?>" class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="<?php echo $this->get('percentDone'); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $this->get('percentDone'); ?>%;">
						<span class="sr-only"><?php echo $this->get('percentDone'); ?>% Complete</span>
					</div>
					<?php } ?>
				</div>
			</div>
			<!-- Actions sur les téléchargements -->
			<div class="btn-group btn-group-sm">
				<?php /*
				$disabled = false;//($this->status == 3 or $this->status == 4) ? true : false;
				$form = new Form('torrentActions_'.$this->id, null, null, 'module', $moduleId, 'post', 'form-inline');
				$form->addField(new Select('moveTorrent', $this->downloadDir, null, '', null, false, 'modify', 'input-sm', $disabled, $this->downloadDirs, true));
				$form->addField(new Button('action', 'moveTorrent', 'Déplacer', 'modify', 'btn-xs', $disabled));
				$form->addField(new Button('action', 'delTorrent', 'Supprimer', 'modify', 'btn-xs'));
				$form->addField(new Hidden('torrent', $this->id));
				$form->display();*/
				?>
      </div>
			<div class="row panel-body torrents collapse" id="collapse_details_<?php echo $this->id; ?>">
				<ul class="col-md-11">
					<li>Début : <?php echo $this->get('addedDate'); ?>, fin <?php echo ($this->get('isFinished'))?': '.$this->get('doneDate'):'estimée dans <span id="torrent_estimated_end_'.$this->id.'">'.$this->get('eta').'</span>'; ?></li>
					<?php if ($this->get('isFinished')){ ?>
					<li>Ratio d'envoi/réception : <span id="torrent-ratio_<?php echo $this->id; ?>"><?php echo $this->get('uploadRatio').' ('.$this->get('uploadedEver').' envoyés, '.$this->get('ratioPercentDone').'% du ratio atteint)'; ?></span></li>
					<li>Taille : <?php echo $this->get('totalSize'); ?></li>
					<?php }else{ ?>
					<li>Reste à télécharger : <span id="torrent-leftuntildone_<?php echo $this->id; ?>"><?php echo $this->get('leftUntilDone').'/'.$this->get('totalSize'); ?></span></li>
					<?php } ?>
					<li>Téléchargé dans : <?php echo $this->get('downloadDir'); ?></li>
					<?php if (!empty($this->comment)){ ?>
					<li>Commentaire : <?php echo $this->get('comment'); ?></li>
					<?php } ?>
					<li>
						<a data-toggle="collapse" data-parent="#torrent_<?php echo $this->id; ?>" href="#collapse_<?php echo $this->id; ?>">Liste des fichiers</a>
						<ul class="collapse" id="collapse_<?php echo $this->id; ?>">
						<?php
						foreach ($this->files as $file){
							?><li><?php echo $file->name; ?></li><?php
						}
						?>
						</ul>
					</li>
					<?php if (!empty($this->nfo)){ ?>
					<li>
						<a data-toggle="collapse" data-parent="#torrent_<?php echo $this->id; ?>" href="#collapse_nfo_<?php echo $this->id; ?>">Informations sur le fichier principal</a>
						<ul class="collapse" id="collapse_nfo_<?php echo $this->id; ?>"><pre><?php echo $this->nfo; ?></pre></ul>
					</li>
					<?php } ?>
				</ul>
				<?php if (!empty($this->img)){ ?>
				<div class="col-md-1 hidden-sm text-right">
					<img class="img-responsive torrent-img" src="index.php?action=torrentImg&source=<?php echo $this->img; ?>" alt="<?php echo $this->get('name'); ?>"/>
				</div>
			<?php } ?>
			</div>
		</div>
		<?php
	}
	
	/**
	* Affiche le popover de déplacement de torrent
	* @param int $id ID du torrent
	* @param string $currentDir Répertoire actuel du torrent
	* 
	* @return void
	*/
	protected function displayPopoverMoveTorrent($id, $currentDir){
		?>
		<form id="moveTorrentForm-<?php echo $id ?>" class="form-inline popover-form moveTorrentForm" role="form" method="POST" data-id="<?php echo $id ?>">
			<div class="input-group">
				<select name="newDir" class="form-control input-sm">
					<?php
					foreach ($this->downloadDirs as $label => $downloadDir){
						?><option value="<?php echo $downloadDir; ?>"<?php echo ($label == $currentDir) ? ' selected' : ''; ?>><?php echo $label ?></option><?php 
					}
					?>
				</select>
				<span class="input-group-btn">
					<button type="submit" id="moveTorrent-<?php echo $id ?>" class="btn btn-default btn-sm moveTorrent">
						<span class="glyphicon glyphicon-share-alt tooltip-bottom" title="Déplacer"></span>
					</button>
				</span>
			</div>
			<input type="hidden" id="torrentId" name="torrentId" value="<?php echo $id ?>">
		</form>
		<?php
	}

	/**
	* Affiche le popover de suppression d'un torrent
	* @param int $id ID du torrent à supprimer
	* 
	* @return void
	*/
	protected function showDelTorrentPopover($id){
		?>
		<form class="form-inline popover-form delTorrentForm" role="form" method="POST"  data-id="<?php echo $id ?>">
			<input type="hidden" name="torrentId" value="<?php echo $id; ?>">
			<div class="btn-group">
				<button type="submit" name="action" value="delTorrent" class="btn btn-danger">Oui</button>
				<button class="btn btn-default close-popover" data-close-popover="del-popover_<?php echo $id; ?>">Non</button>
			</div>
			<div class="checkbox">
				<label class="tooltip-bottom" title="Par défaut, les Salsifis suppriment uniquement le téléchargement sans toucher aux fichiers téléchargés.">
					<input name="delLocalFiles" type="checkbox"> Supprimer également les fichiers
				</label>
			</div>
		</form>
		<?php
	}
}
?>