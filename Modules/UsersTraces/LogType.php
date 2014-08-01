<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/06/14
 * Time: 13:44
 */

namespace Modules\UsersTraces;


class LogType {
	protected $type = '';
	protected $servers = array();
	protected $events = array();

	public function __construct($type){
		$this->type = $type;
	}

	public function __get($prop){
		if (isset($this->$prop)) return $this->$prop;
		return false;
	}

	/**
	 * Ajoute un serveur à la liste
	 * @param Server $server
	 */
	public function add(Server $server) {
		$this->servers[] = $server;
	}

	public function display(){
		foreach ($this->servers as $server){
			$this->events = array_merge($this->events, $server->getLogs());
		}
		$this->events = \Sanitize::sortObjectList($this->events, 'dateTime', 'DESC');
		//var_dump($this->events);
		?>
		<table class="table table-striped table-bordered" id="logTable">
			<thead>
				<tr>
					<th>Date et heure</th>
					<th>Prénom</th>
					<th>Nom</th>
					<th>Client</th>
					<th>Serveur</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($this->events as $event){
					?>
					<tr>
						<td><?php echo \Sanitize::date($event->dateTime, 'fullDateTime'); ?></td>
						<td><?php echo \Sanitize::ucname($event->nickName); ?></td>
						<td><?php echo $event->name; ?></td>
						<td><?php echo $event->client; ?></td>
						<td><?php echo $event->server; ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

} 