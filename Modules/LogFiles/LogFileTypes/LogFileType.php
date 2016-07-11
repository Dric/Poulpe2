<?php
/**
 * Creator: Dric
 * Date: 07/07/2016
 * Time: 15:50
 */

namespace Modules\LogFiles\LogFileTypes;

/**
 * Type de logs par défaut
 *
 * @package Modules\LogFiles\LogFileTypes
 */
class LogFileType {
	/** @var string Nom du type de logs */
	protected $name = 'Défaut';
	/** @var bool Ordre d'affichage des logs */
	protected $orderDesc = false;
	/** @var array Contenu du fichier */
	protected $file = array();
	
	public function __construct(Array $file, $orderDesc){
		$this->file = $file;
		$this->orderDesc = $orderDesc;
	}

	/**
	 * Retourne le nom du type de logs
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Teste la concordance d'un texte avec le type de fichier de logs
	 *
	 * @param string[] $text
	 *
	 * @return bool
	 */
	public static function testPattern(Array $text){
		return true;
	}

	/**
	 * Affiche les logs
	 */
	public function display(){
		if ($this->orderDesc) $this->file = array_reverse($this->file);
	?>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th>Evénement</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($this->file as $row){
					?>
					<tr>
						<td><small><?php echo ltrim($row,':*'); ?></small></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	<?php
	}
}