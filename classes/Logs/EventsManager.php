<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 07/05/14
 * Time: 15:17
 */

namespace Logs;


use Get;
use Sanitize;

/**
 * Classe de gestion des événements
 *
 * @package Logs
 */
class EventsManager {

	/**
	 * Ajoute un événement dans la table SQL des logs
	 *
	 * @param EventLog $event Evénement à ajouter aux logs
	 *
	 * @return bool
	 */
	public static function addToLogs(EventLog $event){
		global $db;
		$insert = array(
			'user'        => $event->getUser(),
		  'component'   => $event->getComponent(),
		  'type'        => $event->getType(),
		  'data'        => $event->getData(),
		  'time'        => $event->getTime()
		);
		$format = array(
			'user'        => 'int',
			'component'   => 'string',
			'type'        => 'string',
			'data'        => 'string',
			'time'        => 'int'
		);
		$ret = $db->insert('logs', $insert, $format);
		return ($ret === false) ? false : true;
	}

	/**
	 * Affiche ou renvoie un log
	 *
	 * @param string|array $types Filtre par type(s) d'événements. Pour récupérer plusieurs types, il suffit de les passer dans un tableau (facultatif)
	 * @param string|array $users Filtre par utilisateur(s). Pour récupérer plusieurs utilisateurs, il suffit de les passer dans un tableau (facultatif)
	 * @param string|array $components Filtre par composant(s). Pour récupérer plusieurs composants, il suffit de les passer dans un tableau (facultatif)
	 * @param array $timeRange Plage de dates (facultatif), de la forme array(dateDébut, dateFin) (facultatif)
	 * @param bool  $returnArray Retourne un tableau contenant els logs si true, affiche le tableau sinon. (facultatif)
	 *
	 * @return bool|object
	 */
	public static function displayLogs($types = null, $users = null, $components = null, $timeRange = array(), $returnArray = false){
		global $db;

		if (!empty($types) and !is_array($types)) $types = array($types);
		if (!empty($users) and !is_array($users)) $users = array($users);
		if (!empty($components) and !is_array($components)) $components = array($components);
		if (!empty($timeRange) and count($timeRange) != 2){
			new Alert('debug', '<code>Logs\EventsManager::displayLogs()</code> : Erreur : <code>$timeRange</code> n\'est pas défini comme <code>array(\'dateHeureDébut\', \'dateHeureFin\')</code><br>'. Get::varDump($timeRange));
			return false;
		}
		$where = array();
		if (!empty($types)) $where['type'] = $types;
		if (!empty($users)) $where['user'] = $users;
		if (!empty($components)) $where['component'] = $components;
		$eventsDb = $db->get('logs', null, $where, array('time' => 'DESC'));
		if (empty($eventsDb) and !$returnArray){
			?><div class="alert alert-warning">Aucun événement</div><?php
		}else{
			if (!empty($timeRange)){
				foreach ($eventsDb as $key => $event){
					if ($event->time < $timeRange[0] or $event->time > $timeRange[1]){
						unset($eventsDb[$key]);
					}
				}
			}
			if ($returnArray){
				return $eventsDb;
			}else{
				$usersList = array();
				$usersDb = $db->get('users', array('id', 'name'));
				foreach ($usersDb as $user){
					$usersList[$user->id] = $user->name;
				}
				if (empty($eventsDb)){
					?><div class="alert alert-warning">Aucun événement dans la plage horaire demandée.</div><?php
				}else{
					?>
					<table class="table">
						<thead>
							<tr>
								<th>Utilisateur</th>
								<th>Composant</th>
								<th>Type</th>
								<th>Données</th>
								<th>Horodatage</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($eventsDb as $event){
								?>
								<tr>
									<td><?php echo (isset($usersList[$event->user])) ? $usersList[$event->user] : 'Utilisateur supprimé (id <code>'.$event->user.'</code>)'; ?></td>
									<td><?php echo $event->component; ?></td>
									<td><?php echo $event->type; ?></td>
									<td><?php echo $event->data; ?></td>
									<td><?php echo Sanitize::date($event->time, 'dateTime'); ?></td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<?php
				}
			}
		}
		return true;
	}
} 