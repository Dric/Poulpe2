<?php
/**
 * Creator: Dric
 * Date: 07/07/2016
 * Time: 15:50
 */

namespace Modules\LogFiles\LogFileTypes;

use Sanitize;

class LogFileType {
	protected $name = 'Défaut';
	protected $orderDesc = false;
	protected $file = array();
	
	public function __construct(Array $file, $orderDesc){
		$this->file = $file;
		$this->orderDesc = $orderDesc;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Teste la concordance d'un texte avec le type de fichier de logs
	 * @param string[] $file
	 */
	public static function testPattern(Array $text){
		return true;
	}
	
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