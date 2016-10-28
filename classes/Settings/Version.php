<?php
/**
 * Creator: Dric
 * Date: 19/05/2016
 * Time: 10:10
 */

namespace Settings;

use Components\Help;
use FileSystem\Fs;
use Front;
use Git\Git;
use Logs\Alert;
use Sanitize;
use Users\ACL;

class Version {
	protected static $dbVersion = '1.1';

	/**
	 * Liste des commandes SQL à passer pour mettre à jour le schéma de DB
	 *
	 * @return array
	 */
	protected static function getDbUpgradeQueries() {
		return array(
			'1.0' => array(
				'CREATE TABLE IF NOT EXISTS `global_settings` (`id` int(6) NOT NULL AUTO_INCREMENT, `setting` varchar(150) NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `setting` (`setting`)) ENGINE=InnoDB DEFAULT CHARSET=utf8',
				'INSERT INTO `global_settings` (`setting`, `value`) VALUES ("poulpe2DbVersion", "1.0") ON DUPLICATE KEY UPDATE `value` = "1.0"',
				'ALTER TABLE `modules` ADD `version` VARCHAR( 15 ) NOT NULL DEFAULT "0" COMMENT "Version du module"'
			),
			'1.1' => array(
				'ALTER TABLE `users` ADD `loginAttempts` INT(2) NOT NULL DEFAULT 0 COMMENT "Nombre de tentatives de connexions"',
				'ALTER TABLE `users` ADD `lastLogin` INT(11) NOT NULL COMMENT "Timestamp Unix de la dernière tentative de connexion"'
			)
		);
	}

	/**
	 * Compare la version encodée (au sein du code) de poulpe2 ou d'un module avec celle inscrite en base de données
	 *
	 * Renvoie `true` si la version est identique, `$false` sinon.
	 *
	 * @param int     $module ID d'un module
	 * @param string  $moduleEncodedVersion Version du module
	 * @param bool    $force Forcer la vérification en base de données
	 *
	 * @return bool
	 */
	public static function checkDbVersion($module = null, $moduleEncodedVersion = null, $force = false){
		global $db;
		if (!is_null($module)){
			if (!isset($_SESSION['modulesVersion'][$module]) or is_null($_SESSION['modulesVersion'][$module]) or $force){
				$dbVersion = $db->getVal('modules','version', array('id' => $module));
				$_SESSION['modulesVersion'][$module] = (empty($dbVersion) or !$dbVersion) ? 0 : $dbVersion;
			}
			return version_compare($moduleEncodedVersion, $_SESSION['modulesVersion'][$module], '=');
		}else{
			if (!isset($_SESSION['dbVersion']) or is_null($_SESSION['dbVersion']) or $force){
				$dbVersion = $db->getVal('global_settings','value', array('setting' => 'poulpe2DbVersion'));
				$_SESSION['dbVersion'] = (empty($dbVersion) or !$dbVersion) ? 0 : $dbVersion;
			}
			return version_compare(self::$dbVersion, $_SESSION['dbVersion'], '=');
		}
	}

	/**
	 * Met à jour le schéma de base de données
	 * Si $module est spécifié (via son ID), met à jour le schéma de BD du module
	 *
	 * @param int     $module ID de module
	 * @param string  $moduleEncodedVersion Version encodée du module
	 * @param array   $moduleUpgradeQueries Commandes SQL de mise à jour
	 *
	 * @return bool
	 */
	public static function updateDbSchema($module = null, $moduleEncodedVersion = null, Array $moduleUpgradeQueries = null) {
		global $db;
		if (self::checkDbVersion($module, $moduleEncodedVersion)) {
			new Alert('debug', '<code>Version::updateDbSchema</code> : Inutile de lancer la mise à jour du schéma de base de données !');
			return true;
		}
		$sqlQueries = (is_null($module)) ? self::getDbUpgradeQueries() : $moduleUpgradeQueries;
		$inDbVersion    = (is_null($module)) ? $_SESSION['dbVersion'] : $_SESSION['modulesVersion'][$module];
		$encodedVersion = (is_null($module)) ? self::$dbVersion : $moduleEncodedVersion;

		// Le module peut ne pas nécessiter de changements en base de données mais peut avoir eu des opérations à effectuer sur des données. dans ce cas, il faut juste mettre à jour le numéro de version.
		// Cette méthode ne se lance de toute façon que si les opérations de script du module ont bien été passées.
		if (!is_null($sqlQueries)) {
			new Alert('warning', 'Mise à jour du schéma de base de données nécessaire de la version <code>' . $inDbVersion . '</code> à la version <code>' . $encodedVersion . '</code>');
			foreach ($sqlQueries as $version => $versionSQLQueries) {
				if (version_compare($inDbVersion, $version, '<')) {
					$ret = $db->queryGroup($versionSQLQueries);
					if ($ret === false) {
						new Alert('error', 'Impossible de mettre à jour le schéma de base de données ' . ((is_null($module)) ? '' : 'du module ') . 'vers la version <code>' . $version . '</code>');
						return false;
					}
					new Alert('success', 'Schéma de base de données ' . ((is_null($module)) ? '' : 'du module ') . 'mis à jour en version <code>' . $version . '</code>');
				}
			}
		}else{
			new Alert('info', 'Aucune commande SQL de mise à jour, seul le numéro de version '.((is_null($module)) ? '' : 'du module ').'sera mis à jour.');
		}
		if (is_null($module)){
			$ret2 = $db->update('global_settings', array('value' => $encodedVersion), array('setting' => 'poulpe2DbVersion'));
		}else{
			$ret2 = $db->update('modules', array('version' => $encodedVersion), array('id' => $module));
		}
		if ($ret2 === false){
			new Alert('error', 'Impossible de mettre à jour la version dans la base de données en <code>'.$encodedVersion.'</code>');
			return false;
		}
		if (self::checkDbVersion($module, $moduleEncodedVersion, true)){
			new Alert('success', 'Mise à jour de version '.((is_null($module)) ? '' : 'du module ').'terminé !');
			return true;
		}
		// On met à jour la variable
		$inDbVersion = (is_null($module)) ? $_SESSION['dbVersion'] : $_SESSION['modulesVersion'][$module];
		new Alert('error', 'La mise à jour '.((is_null($module)) ? '' : 'du module ').'vers la version <code>'.$encodedVersion.'</code> a peut-être échoué car la version inscrite en base de données (<code>'.$inDbVersion.'</code>) est différente de celle attendue.');
		return false;
	}

	/**
	 * @return string
	 */
	public static function getDbVersion() {
		return self::$dbVersion;
	}

	/**
	 * Affiche un message à l'utilisateur si celui-ci est administrateur de Poulpe2 et s'il existe des mises à jour de code
	 *
	 * La vérification se fait tous les x jours, `x` étant défini par la constante Settings::UPDATE_CHECK_INTERVAL
	 *
	 */
	public static function checkUpdates(){
		if (ACL::can('admin', 0, null, 'admin')){
			$checkInterval = \Settings::UPDATE_CHECKS_INTERVAL * 3600 * 24;
			$dir = Front::getAbsolutePath();
			$fs = new Fs($dir);
			$timestampCore = ($fs->fileExists('cache/lastUpdateCheck-core.cache')) ? (int)$fs->readFile('cache/lastUpdateCheck-core.cache', 'string') : 0;
			$timeStampModules = ($fs->fileExists('cache/lastUpdateCheck-core.cache')) ? (int)$fs->readFile('cache/lastUpdateCheck-core.cache', 'string') : 0;
			if ((time() - $timestampCore > $checkInterval) or (time() - $timeStampModules > $checkInterval)){
				if (self::hasGitUpdates('core') or self::hasGitUpdates('modules')){
					new Alert('info', 'Des mises à jour sont disponibles.<br><br>Veuillez vous rendre dans l\'administration pour les visualiser et les appliquer.');
				}
			}
		}
	}

	/**
	 * Affiche la liste des mises à jours disponibles pour le composant demandé
	 *
	 * @param string $component Composant (`core` ou `modules`)
	 *
	 * @return bool
	 */
	public static function listGitUpdates($component = 'core'){
		if (!in_array($component, array('core', 'modules'))) return false;
		// On récupère la liste des mises à jour pour le composant
		$gitRepo = self::checkGitUpdates($component);
		$updatesRaw = $gitRepo['updates'];
		if (!empty($updatesRaw) and !empty($updatesRaw[0])) {
			echo '<ul>';
			foreach ($updatesRaw as $updateRaw) {
				list($updateFullHash, $updateShortHash, $updateTimestamp, $updateBody) = explode('+-+', $updateRaw);
				?>
				<li>
					<?php echo Sanitize::date($updateTimestamp, 'dateTime'); ?><?php Help::icon('clock-o', 'info', 'il y a ' . Sanitize::timeDuration(time() - $updateTimestamp)); ?> - <a href="<?php echo $gitRepo['GitRepo']->getOrigin() . '/commit/' . $updateFullHash; ?>"><?php echo $updateShortHash; ?></a> : <?php echo $updateBody; ?>
				</li>
				<?php
			}
			echo '</ul>';
		} else {
			?><div class="alert alert-success">Pas de nouvelles mises à jour pour Poulpe2 !</div><?php
		}
		return true;
	}

	/**
	 * Vérifie s'il existe des mises à jour pour le code
	 *
	 * @param string $component Composant (`core` ou `modules`)
	 *
	 * @return bool
	 */
	public static function hasGitUpdates($component = 'core'){
		if (!in_array($component, array('core', 'modules'))) return false;

		$gitRepo = self::checkGitUpdates($component);
		return (!empty($gitRepo['updates']) and substr($gitRepo['updates'][0], 0, 4) == '+@@+') ? true : false ;
	}

	/**
	 * Lance une commande Git pour récupérer les informations de mise à jour
	 *
	 * @param string $component Composant (`core` ou `modules`)
	 *
	 * @return array|bool
	 */
	protected static function checkGitUpdates($component = 'core'){
		if (!in_array($component, array('core', 'modules'))) return false;

		$dir = Front::getAbsolutePath();
		$fs = new Fs($dir);
		if ($component == 'modules') $dir .= DIRECTORY_SEPARATOR . \Settings::MODULE_DIR;
		$gitRepo = Git::open($dir);
		$gitRepo->fetch();
		if (!$fs->isWritable('cache')){
			new Alert('error', 'Le répertoire <code>cache</code> n\'est pas accessible en écriture !');
			$disabled['cache'] = true;
		}
		$lastCheckFile = 'cache/lastUpdateCheck-'.$component.'.cache';
		// On met à jour la date de dernière vérification
		$fs->writeFile($lastCheckFile, time(), false, false, true);
		return array('gitRepo' => $gitRepo, 'updates' => explode('+@@+', $gitRepo->logFileRevisionRange('master', 'origin/master', '+@@+%H+-+%h+-+%at+-+%B')));
	}

}