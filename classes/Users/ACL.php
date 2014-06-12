<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/04/14
 * Time: 12:31
 */

namespace Users;


use Logs\Alert;
use Components\Help;
use Get;
use Sanitize;

/**
 * Classe de gestion des ACL
 *
 * Permet de définir des droits sur des composants du site pour des utilisateurs.
 * l'ID d'utilisateur 0 signifie 'tout le monde' et sert à définir des ACL par défaut pour un composant.
 *
 * Pour gérer les ACL de tout composant, on essaie de faire un tableau associatif de la forme $ACL[$component][$id][$user][$type] = $value
 * Pour les ACL d'un utilisateur, on aura ainsi $userACL[$component][$id][$type] = $value
 * Pour les ACL d'un module, on aura $moduleACL[$user][$type] = $value
 *
 * @package Users
 */
class ACL {

	/**
	 * Types d'autorisations
	 * @var array
	 */
	protected static $typesLabels = array(
		'access' => 'Permet d\'accéder à la ressource',
		'modify' => 'Permet la modification d\'éléments dans la ressource',
	  'admin'  => 'Permet d\'administrer la ressource'
	);

	/**
	 * Retourne les autorisations d'un utilisateur
	 *
	 * @param int $user ID d'un utilisateur
	 *
	 * @return bool|array
	 */
	public static function getUserACL($user){
		global $db;
		if (!is_int($user)){
			new Alert('debug', '<code>ACL::getUserACL()</code> : <code>$user</code> n\'est pas un nombre entier ! '. Get::varDump($user));
			return false;
		}
		$dbACL = $db->get('ACL', '*', array('user'=>$user));
		if (!$dbACL) return false;
		$retACL = array();
		foreach ($dbACL as $acl){
			$acl->value = ($acl->value == 1) ? true : false;
			$retACL[$acl->component][$acl->id][$acl->type] = $acl->value;
		}
		$dbACL = $db->get('ACL', '*', array('user'=>10000));
		if (!$dbACL) return false;
		foreach ($dbACL as $acl){
			$acl->value = ($acl->value == 1) ? true : false;
			if (!isset($retACL[$acl->component][$acl->id][$acl->type])) $retACL[$acl->component][$acl->id][$acl->type] = $acl->value;
		}
		return $retACL;
	}

	/**
	 * Détermine si l'utilisateur a le droit d'administrer un composant
	 * @param string $component Composant (module, site,...)
	 * @param int $componentId Id du composant
	 * @param int $userId Id de l'utilisateur (utilisateur courant si vide)
	 *
	 * @return bool
	 */
	public static function canAdmin($component, $componentId, $userId = null){
		if (self::can('admin', 0, $userId, 'admin')) return true;
		return self::can($component, $componentId, $userId, 'admin');
	}

	/**
	 * Détermine si l'utilisateur a le droit de modifier un composant
	 * @param string $component Composant (module, site,...)
	 * @param int $componentId Id du composant
	 * @param int $userId Id de l'utilisateur (utilisateur courant si vide)
	 *
	 * @return bool
	 */
	public static function canModify($component, $componentId, $userId = null){
		if (self::can('admin', 0, $userId, 'admin')) return true;
		return self::can($component, $componentId, $userId, 'modify');
	}

	/**
	 * Détermine si l'utilisateur a le droit d'accéder à un composant
	 * @param string $component Composant (module, site,...)
	 * @param int $componentId Id du composant
	 * @param int $userId Id de l'utilisateur (utilisateur courant si vide)
	 *
	 * @return bool
	 */
	public static function canAccess($component, $componentId, $userId = null){
		if (self::can('admin', 0, $userId, 'admin')) return true;
		return self::can($component, $componentId, $userId, 'access');
	}

	/**
	 * Détermine si l'utilisateur a le droit d'effectuer une action $type sur un composant
	 * @param string $component Composant (module, site,...)
	 * @param int $componentId Id du composant
	 * @param int $userId Id de l'utilisateur (utilisateur courant si vide)
	 * @param string $type Type d'ACL (admin, access)
	 *
	 * @return bool
	 */
	public static function can($component, $componentId, $userId = null, $type){
		global $db, $cUser;
		if (!in_array($type, array_keys(self::$typesLabels))){
			New Alert('debug', '<code>ACL::can()</code> : <code>$type='.$type.'</code> ne fait pas partie des types autorisés !');
			return false;
		}
		if (empty($userId) or $userId == $cUser->getId()){
			// On demande donc une autorisation pour l'utilisateur courant. Comme on a déjà la liste de ses autorisations, on va la chercher dans son objet
			$userACL = $cUser->getACL();
			return (isset($userACL[$component][$componentId][$type])) ? $userACL[$component][$componentId][$type] : false;
		}else{
			$where = array(
				'component' => $component,
			  'id'        => $componentId,
			  'user'      => $userId,
			  'type'      => $type
			);
			$ret = $db->getVal('ACL', 'value', $where);
			switch ($ret){
				case null:
					// On regarde s'il existe une valeur pour tous les utilisateurs (10000 = tout le monde)
					$where['user'] = 10000;
					$ret = $db->getVal('ACL', 'value', $where);
					if (!is_null($ret) or $ret) return true;
					break;
				case 0:
				case false:
					return false;
				case 1:
				case true:
					return true;
			}
		}
		return false;
	}

	/**
	 * Définit une règle $type sur un composant
	 *
	 * @param string $component Composant (module, site,...)
	 * @param int    $componentId Id du composant
	 * @param int    $userId Id de l'utilisateur
	 * @param string $type Type d'ACL (admin, access)
	 * @param bool   $value Valeur de l'ACL (true : permis, false : refusé)
	 *
	 * @return bool
	 */
	public static function set($component, $componentId, $userId, $type, $value){
		global $db;
		if (!in_array($type, array_keys(self::$typesLabels))){
			New Alert('debug', '<code>ACL::set()</code> : <code>$type='.$type.'</code> ne fait pas partie des typesLabels autorisés !');
			return false;
		}
		$value = Sanitize::SanitizeForDb($value);
		$types = array_flip(array_keys(self::$typesLabels));
		$ret = false;
		$sql = '';
		if ($value == 1){
			// On inscrit aussi les permissions inférieures si c'est une autorisation
			for ($i = 0; $i <= $types[$type]; $i++){
				$typeI = array_search($i, $types);
				$sql = 'INSERT INTO `ACL` (`component`, `id`, `user`, `type`, `value`) VALUES ("'.$component.'", '.$componentId.', '.$userId.', "'.$typeI.'", '.$value.') ON DUPLICATE KEY UPDATE value = '.$value;
				$ret = $db->query($sql);
				if ($ret === false){
					new Alert('error', 'Impossible de sauvegarder les permissions !<br>'.$sql);
					return false;
				}
			}
		}else{
			// On inscrit aussi les permissions supérieures si c'est un refus
			for ($i = 2; $i > $types[$type]; $i--){
				$typeI = array_search($i, $types);
				$sql = 'INSERT INTO `ACL` (`component`, `id`, `user`, `type`, `value`) VALUES ("'.$component.'", '.$componentId.', '.$userId.', "'.$typeI.'", '.$value.') ON DUPLICATE KEY UPDATE value = '.$value;
				$ret = $db->query($sql);
				if ($ret === false){
					new Alert('error', 'Impossible de sauvegarder les permissions !<br>'.$sql);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Définit des règles $type sur des composants
	 *
	 * Cette fonction fait la même chose que ACL::set(), mais avec un tableau d'ACL.
	 * @param array $ACLArr Tableau d'ACL de la forme $array[(string)$component][(int)$componentId][(int)$user][(string)$type] = (bool)$value
	 *
	 * @return bool
	 */
	public static function setArray(array $ACLArr){
		global $db;
		$types = array_flip(array_keys(self::$typesLabels));
		$setACLArray = array();
		foreach ($ACLArr as $component => $ACLComponent){
			foreach ($ACLComponent as $componentId => $ACLCompId){
				foreach ($ACLCompId as $user => $ACLUser){
					/*
					 * Si on donne l'autorisation d'admin sur une ressource, il faut également autoriser les accès plus restrictifs.
					 * Ex : Si on donne le droit d'admin sur une ressource, il faut également accorder les droits access et modif.
					 */
					$max = 0;
					$valueMax = 0;
					foreach ($ACLUser as $type => $value){
						if ($types[$type] >= $max and $value === true) {
							$valueMax = Sanitize::SanitizeForDb($value);
							$max = $types[$type];
						}
					}
					foreach ($ACLUser as $type => $value){
						$setACLArray[] = '("'.$component.'", '.$componentId.', '.$user.', "'.$type.'", '.(($types[$type] <= $max) ? $valueMax : Sanitize::SanitizeForDb($value)).')';
					}
				}
			}
		}
		$ret = $db->query('INSERT INTO `ACL` (`component`, `id`, `user`, `type`, `value`) VALUES '.implode(', ', $setACLArray).' ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
		return ( $ret === false) ? false : true;
	}

	/**
	 * Récupère l'envoi d'un formulaire pour sauvegarder des ACL
	 * @return bool
	 */
	public static function requestACLSave(){
		$ACLArr = array();
		foreach ($_REQUEST as $key => $value){
			if (substr($key, 0, 4) == 'ACL_'){
				list($dummy, $component, $componentId, $user, $type, $field) = explode('_', $key);
				if (!in_array($type, array_keys(self::$typesLabels))){
					New Alert('debug', '<code>ACL::requestACLSave()</code> : <code>$type='.$type.'</code> ne fait pas partie des typesLabels autorisés !');
					return false;
				}
				$ACLArr[$component][$componentId][$user][$type][$field] = ($value == 1) ? true : false;
			}
		}
		foreach ($ACLArr[$component][$componentId] as $user => $ACLUser){
			foreach ($ACLUser as $type => $ACLType){
				$ACLArr[$component][$componentId][$user][$type] = (isset($ACLType['checkbox'])) ? $ACLType['checkbox'] : $ACLType['hidden'];
			}
		}
		if ($ret = self::setArray($ACLArr)){
			new Alert('success', 'Les autorisations ont été sauvegardées !');
		}else{
			new Alert('error', 'Les autorisations n\'ont pas été sauvegardées !');
		}
		return $ret;
	}

	/**
	 * Supprime des ACL
	 *
	 * Si $component est vide, alors on supprime tout (si $type est vide) ou une partie (si $type est renseigné) des ACL d'un utilisateur
	 * Si $userId est vide, on réinitialise les ACL pour un composant (ou une partie si $type est renseigné)
	 *
	 * @param string $component Composant (module, site,...)
	 * @param int $componentId Id du composant
	 * @param int $userId Id de l'utilisateur
	 * @param string $type Type d'ACL (admin, access)
	 *
	 * @return bool
	 */
	public static function delete($component = null, $componentId = null, $userId = null, $type = null){
		global $db;
		$where = array();
		$format = array(
			'component' => 'string',
		  'id'        => 'int',
		  'user'      => 'int',
		  'type'      => 'string'
		);
		if (!is_null($component)){
			if (is_null($componentId)) {
				new Alert('debug', '<code>ACL::delete()</code> : $componentId est vide alors que $component est renseigné !');
				return false;
			}
			$where['component'] = $component;
			$where['id'] = $componentId;
		}
		if (!is_null($userId)){
			$where['user'] = $userId;
		}
		if (!is_null($type)){
			if (!in_array($type, array_keys(self::$typesLabels))){
				New Alert('debug', '<code>ACL::delete()</code> : <code>$type='.$type.'</code> ne fait pas partie des typesLabels autorisés !');
				return false;
			}
			$where['type'] = $type;
		}
		if (empty($where) or (isset($where['type']) and count($where) == 1)){
			// Si aucun paramètre (ou seulement le type) n'est renseigné, on annule tout
			new Alert('debug', '<code>ACL::delete()</code> : Aucun paramètre n\'est renseigné !');
			return false;
		}
		if ($ret = $db->delete('ACL', $where, $format)){
			new Alert('error', 'Impossible de supprimer les autorisations !');
			return false;
		}
		new Alert('success', 'Les autorisations ont été correctement supprimées !');
		return true;
	}

	/**
	 * Affiche le formulaire de réglage des ACL
	 *
	 * @param string $component Type de composant (module, admin, etc.)
	 * @param string $componentId ID du composant
	 * @param string $title Nom du composant administré (facultatif)
	 */
	public static function adminACL($component, $componentId, $title = null){
		global $db;
		$users = UsersManagement::getDBUsers();
		$acls = $db->query('SELECT user, type, value FROM `ACL` WHERE `component` = "'.$component.'" AND `id` = '.$componentId.' ORDER BY `user`');
		$adminUsers = $db->query('SELECT user FROM `ACL` WHERE `component` = "admin" and `type` = "admin" AND `value` = 1 ORDER BY `user`');
		$admins = array();
		foreach ($adminUsers as $adminUser){
			$admins[] = $adminUser->user;
		}
		$types = array_flip(array_keys(self::$typesLabels));
		$ACLArr = array();
		foreach ($acls as $acl){
			$ACLArr[$acl->user][$acl->type] = ($acl->value == 1) ? true : false;
			if ($acl->user == 10000){
				$ACLArr['default'][$acl->type] = ($acl->value == 1) ? true : false;
			}
		}
		?>
		<div class="row">
			<div class="col-md-12">
				<?php if (!is_null($title)) { ?><h2>Droits sur <?php echo $title; ?></h2><?php } ?>
				<form id="adminACL" class="" method="post" role="form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<table class="table">
						<thead>
							<tr>
								<th>Utilisateur</th>
								<?php
								foreach (self::$typesLabels as $type => $label){
								?>
								<th><?php echo $type; ?> <?php Help::iconHelp($label); ?></th>
								<?php
								}
								?>
								<th>Remarques</th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ($users as $user){

							?>
							<tr id="ACL_<?php echo $component.'_'.$componentId.'_'.$user->id; ?>">
								<td><?php echo $user->name; ?></td>
								<?php
								$inherited = true;
								foreach(self::$typesLabels as $type => $label){
									$checked = false;
									if (isset($ACLArr[$user->id][$type])){
										if ($ACLArr[$user->id][$type]) {
											$checked = true;
										}
										$inherited = false;
									}elseif(isset($ACLArr['default'][$type]) and $ACLArr['default'][$type]){
										$checked = true;
									}elseif(in_array($user->id, $admins)){
										$checked = true;
									}
									?>
									<td>
										<input type="checkbox" class="form-control checkbox-ACL" id="ACL_<?php echo $component.'_'.$componentId.'_'.$user->id.'_'.$type; ?>_checkbox" name="ACL_<?php echo $component.'_'.$componentId.'_'.$user->id.'_'.$type; ?>_checkbox" data-type-value="<?php echo $types[$type]; ?>" data-tr-id="#ACL_<?php echo $component.'_'.$componentId.'_'.$user->id; ?>" value="1" <?php if ($checked) echo 'checked'; ?>>
										<input type="hidden" id="ACL_<?php echo $component.'_'.$componentId.'_'.$user->id.'_'.$type; ?>_hidden" name="ACL_<?php echo $component.'_'.$componentId.'_'.$user->id.'_'.$type; ?>_hidden" value="0">
									</td>
								<?php
								}
								?>
								<td>
									<i>
									<?php
									if (in_array($user->id, $admins)){
										echo 'Administrateur global';
									}elseif ($inherited) {
										echo 'Hérité des ACL par défaut';
                  }
									?>
									</i>
								</td>
							</tr>
						<?php
						}
						?>
							<tr id="ACL_<?php echo $component.'_'.$componentId.'_10000'; ?>">
									<td>Tout le monde</td>
							<?php
							foreach(self::$typesLabels as $type => $label){
								?>
								<td>
									<input type="checkbox" class="form-control checkbox-ACL" id="ACL_<?php echo $component.'_'.$componentId.'_10000_'.$type; ?>_checkbox" name="ACL_<?php echo $component.'_'.$componentId.'_10000_'.$type; ?>_checkbox" data-type-value="<?php echo $types[$type]; ?>" data-tr-id="#ACL_<?php echo $component.'_'.$componentId.'_10000'; ?>" value="1" <?php if (isset($ACLArr['default'][$type]) and $ACLArr['default'][$type]) echo 'checked'; ?>>
									<input type="hidden" id="ACL_<?php echo $component.'_'.$componentId.'_10000_'.$type; ?>_hidden" name="ACL_<?php echo $component.'_'.$componentId.'_10000_'.$type; ?>_hidden" value="0">
								</td>
							<?php
							}
							?>
							<td></td>
							</tr>
						</tbody>
					</table>
					<button class="btn btn-primary" type="submit" name="action" value="saveACL">Sauvegarder</button>
				</form>
			</div>
		</div>
		<?php
	}
} 