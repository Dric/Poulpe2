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

	/** @var bool Etat en erreur de la ressource */
	protected $inError = false;

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
		}elseif ($this->type == 'disk'){
			// On récupère la liste des partitions (avec un timeout, ce qui évite de bloquer toute la page si jamais il n'est pas possible de récupérer les données
			exec('timeout -k 2 2 df -h | grep ^/dev', $out);
			if (!empty($out) and !preg_match('/Complété/i', $out[0])){
				foreach ($out as $line){
					$line = preg_replace('/\s+/', ' ',$line);
					$tab = explode(' ', $line);
					$partition = end($tab);
					$this->partitions[] = $partition;
				}
			}else{
				$this->inError = true;
			}
		}
		if (!$this->inError) $this->retrieveData();
	}

	/**
	 * Calcul de la charge
	 */
	protected function retrieveData(){
		switch ($this->type){
			case 'systemLoad':
				$this->load = sys_getloadavg()[1]*100;
				$this->total = 100;
				break;
			case 'cpu':
				exec("grep 'cpu ' /proc/stat | awk '{usage=($2+$3+$4)*100/($2+$3+$4+$5)} END {print usage}'", $out);
				$this->load = (float)$out[0];
				$this->total = 100;
				break;
			case 'mem':
				$data = explode("\n", file_get_contents("/proc/meminfo"));
				$used = 0;
				$usedTypes = array('memfree', 'buffers', 'cached');
				foreach ($data as $line) {
					preg_match('/(\w+):\s+(\d+)/i', $line, $matches);
					if ($matches) {
						if (strtolower($matches[1]) == 'memtotal') {
							$this->total = $matches[2] * 1024;
						} elseif (in_array(strtolower($matches[1]), $usedTypes)) {
							$used += $matches[2] * 1024;
							unset($usedTypes[strtolower($matches[1])]);
						}
					}
					if (empty($usedTypes)) break;
				}
				$this->load = $this->total - $used;
				break;
			case 'disk':
				foreach ($this->partitions as $partition){
					$this->total[$partition] = disk_total_space($partition);
					$this->load[$partition] = $this->total[$partition] - disk_free_space($partition);
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
				$obj->percentLoad = round($this->load, 2);
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
				$obj->percentLoad = round($this->load / $this->total, 2)*100;
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
					$obj->percentLoad[$partition] = round($this->load[$partition] / $this->total[$partition], 2)*100;
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
		if ($this->inError) {
			echo '<div class="alert alert-warning">Pas de données affichables.</div>';
		}else {
			$data = $this->get();
			if (isset($data->total)) {
				if (is_array($data->total)) {
					foreach ($data->total as $partition => $total) {
						$label[$partition] = $data->free[$partition] . ' libres sur ' . $total;
					}
				} else {
					$label = $data->free . ' libres sur ' . $data->total;
				}
			} else {
				$label = round($data->percentFree, 1) . '% inoccupé';
			}
			if (is_array($data->percentLoad)) {
				foreach ($data->percentLoad as $partition => $percentLoad) {
					if ($percentLoad > 80) {
						$level[$partition] = 'danger';
					} elseif ($percentLoad < 50) {
						$level[$partition] = 'success';
					} else {
						$level[$partition] = 'warning';
					}
				}
			} else {
				if ($data->percentLoad > 80) {
					$level = 'danger';
				} elseif ($data->percentLoad < 50) {
					$level = 'success';
				} else {
					$level = 'warning';
				}
			}
			if (is_array($label)) {
				foreach ($label as $partition => $partLabel) {
					?><?php if (count($label) > 1) { ?>
						<h5><?php echo ($partition == '/') ? 'Système' : $partition; ?></h5>
					<?php } ?>
					<div class="progress tooltip-bottom" title="<?php echo $partLabel; ?>">
						<div class="progress-bar progress-bar-<?php echo $level[$partition]; ?>" role="progressbar" aria-valuenow="<?php echo $data->percentLoad[$partition]; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $data->percentLoad[$partition]; ?>%">
							<?php echo round($data->percentLoad[$partition], 1) . '%'; ?>
						</div>
					</div>
					<?php
				}
			} else {
				?>
				<div class="progress tooltip-bottom" title="<?php echo $label; ?>">
					<div class="progress-bar progress-bar-<?php echo $level; ?>" role="progressbar" aria-valuenow="<?php echo $data->percentLoad; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $data->percentLoad; ?>%">
						<?php echo round($data->percentLoad, 1) . '%'; ?>
					</div>
				</div>
				<?php
			}
		}
	}
} 