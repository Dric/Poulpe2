<?php
/**
 * Created by PhpStorm.
 * User: Dric
 * Date: 02/08/14
 * Time: 13:30
 */

namespace Components;


/**
 * Oobject de ressource matérielle
 *
 * @package Components
 */
class serverResource {

	/**
	 * Type de ressource
	 * @var string
	 */
	protected $type = 'disk';
	/**
	 * Types possibles de ressources
	 * @var array
	 */
	protected $types = array(
		'disk',
		'cpu',
		'mem'
	);
	/**
	 * Point de montage de la partition disque
	 * @var null|string
	 */
	protected $partitions = array();
	/**
	 * Charge de la ressource
	 * @var int|float|array
	 */
	protected $load = null;
	/**
	 * Total de la ressource
	 * @var int|float|array
	 */
	protected $total = null;

	/**
	 * Déclaration de ressource matériel
	 * @param string $type Type de ressource : `cpu`, `mem` ou `disk`
	 * @param string $partitions Point de montage de la partition disque
	 */
	public function __construct($type, $partitions = null){
		$this->type = (in_array($type, $this->types)) ? $type : 'disk';
		if (!empty($partitions)){
			if (is_array($partitions)){
				$this->partitions = $partitions;
			} else{
				$this->partitions = array($partitions);
			}
		}else{
			// On récupère la liste des partitions
			exec('df -h | grep ^/dev', $out);
			foreach ($out as $line){
				echo \Get::varDump(explode(' ', $line));
				list(,,,,,$partition) = explode(' ', $line);
				$this->partitions[] = $partition;
			}
		}
	}

	/**
	 * Calcul de la charge
	 */
	protected function retrieveData(){
		switch ($this->type){
			case 'cpu':
				$this->$load = sys_getloadavg();
				$this->total = 100;
				break;
			case 'mem':
				exec('free -b', $out);
				$this->total = explode(' ', $out[0])[0];
				list(,,$this->load,) = explode(' ', $out[1]);
				break;
			case 'disk':
				foreach ($this->partitions as $partition){
					$this->total[$partition] = disk_total_space($partition);
					$this->total[$partition] = $this->total[$partition] - disk_free_space($partition);
				}
				break;
		}
	}

	/**
	 * Valeurs de la ressource
	 *
	 * @param bool $realValue Retourner les valeurs non mises en forme
	 * @return \StdCLass
	 */
	public function get($realValue = false){
		$obj = new \StdCLass;
		switch ($this->type){
			case 'cpu':
				$obj->percentLoad = $this->load;
				$obj->percentFree = 100 - $this->load;
				break;
			case 'mem':
				if ($realValue){
					$obj->load  = $this->load;
					$obj->total = $this->total;
					$obj->free  = $this->total - $this->load;
				}else{
					$obj->load  = \Sanitize::readableFileSize($this->load);
					$obj->total = \Sanitize::readableFileSize($this->total);
					$obj->free  = \Sanitize::readableFileSize($this->total - $this->load);
				}
				$obj->percentLoad = round($this->load / $this->total, 1)*100;
				$obj->percentFree = 100 - $obj->percentLoad;
				break;
			case 'disk':
				foreach($this->partitions as $partition){
					if ($realValue){
						$obj->load[$partition]  = $this->load[$partition];
						$obj->total[$partition] = $this->total[$partition];
						$obj->free[$partition]  = $this->total[$partition] - $this->load[$partition];
					}else{
						$obj->load[$partition]  = \Sanitize::readableFileSize($this->load[$partition]);
						$obj->total[$partition] = \Sanitize::readableFileSize($this->total[$partition]);
						$obj->free[$partition]  = \Sanitize::readableFileSize($this->total[$partition] - $this->load[$partition]);
					}
					$obj->percentLoad[$partition] = round($this->load[$partition] / $this->total[$partition], 1)*100;
					$obj->percentFree[$partition] = 100 - $obj->percentLoad[$partition];
					}
				break;
		}
		return $obj;
	}

	/**
	 * Affichage d'une barre de progression illustrant l'occupation de la ressource
	 */
	public function displayBar(){
		$data = $this->get();
		if (isset($data->total)){
			if (is_array($data->total)){
				foreach ($data->total as $partition => $total){
					$libelle[$partition] = $data->free[$partition].' libres sur '.$total;
				}
			}else{
				$libelle = $data->free.' libres sur '.$data->total;
			}
		}else{
			$libelle = $data->free.' inoccupé';
		}
		if (is_array($data->percentLoad)){
			foreach ($data->percentLoad as $partition => $percentLoad){
				if ($percentLoad > 80){
					$level[$partition] = 'danger';
				}elseif($percentLoad < 50){
					$level[$partition] = 'success';
				}else{
					$level[$partition] = 'warning';
				}
			}
		}else{
			if ($data->percentLoad > 80){
				$level = 'danger';
			}elseif($data->percentLoad < 50){
				$level = 'success';
			}else{
				$level = 'warning';
			}
		}
		if (is_array($libelle)){
			foreach ($libelle as $partition => $partLabel){
				?>
				<div class="progress tooltip-bottom" title="<?php echo $partLabel; ?>">
					<div class="progress-bar progress-bar-<?php echo $level[$partition]; ?>" role="progressbar" aria-valuenow="<?php echo $data->percent[$partition]; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $data->percent[$partition]; ?>%">
						<?php echo $partition.' : '.round($data->percent[$partition], 1).'%'; ?>
					</div>
				</div>
				<?php
			}
		}
		?>
		<div class="progress tooltip-bottom" title="<?php echo $libelle; ?>">
			<div class="progress-bar progress-bar-<?php echo $level; ?>" role="progressbar" aria-valuenow="<?php echo $data->percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $data->percent; ?>%">
				<?php echo round($data->percent, 1).'%'; ?>
			</div>
		</div>
	<?php
	}
} 