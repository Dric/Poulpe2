<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/04/14
 * Time: 11:56
 */

namespace Admin;

use Components\Help;
use Logs\Alert;
use Components\Item;
use Components\Menu;
use FileSystem\Fs;
use Front;
use Modules\Module;
use Modules\ModulesManagement;
use Settings\Field;
use Settings\Form;
use Settings\PostedData;
use Users\ACL;
use Users\UsersManagement;

/**
 * Classe d'administration du site
 *
 * Cette classe doit avoir des fonctions publiques similaires à celles des modules, car elle est appelée de la même façon que ces derniers.
 *
 * @package Admin
 */
class Admin extends Module {

	protected $name = 'Admin';
	protected $title = 'Administration';
	protected $type = 'admin';

	/**
	 * Gère le menu d'administration
	 */
	protected function moduleMenu(){
		$menu = new Menu($this->name, 'Administration', '', '', '');
		$menu->add(new Item('modules', 'Modules', $this->url.'&page=modules', 'Administration des modules', 'th-large'));
		$menu->add(new Item('acl', 'Autorisations', $this->url.'&page=ACL', 'Gestion des autorisations', 'lock'));
		$menu->add(new Item('config', 'Configuration', $this->url.'&page=config', 'Fichier de configuration', 'floppy-disk'));
		Front::setSecondaryMenus($menu);
	}

	protected function adminModules(){
		global $db;
		$activeModules = array();
		$modulesDb = ModulesManagement::getActiveModules();
		foreach ($modulesDb as $moduleDb){
			$activeModules[$moduleDb->name] = array(
				'id'    => $moduleDb->id,
			);
		}
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Modules de <?php echo SITE_NAME; ?></h1>
				</div>
				<?php
					$modules = array();
					$modulesPath = Front::getAbsolutePath().DIRECTORY_SEPARATOR.'Modules';
					$fs = new Fs($modulesPath, 'localhost');
					$files = $fs->getFilesInDir(null, 'php', true);
					foreach ($files as $file){
						if ($file != $modulesPath.DIRECTORY_SEPARATOR.'Module.php' and $file != $modulesPath.DIRECTORY_SEPARATOR.'ModulesManagement.php'){
							// On lit les 60 premières lignes de chaque fichier pour récupérer les infos nécessaires
							$lines=array();
							$className = $moduleName = $moduleTitle = '';
							$fp = fopen($file, 'r');
							while(!feof($fp)){
								$line = fgets($fp);
								$lines[] = $line;
								if (preg_match('/^class (\w*) extends Module/i', $line, $matches)){
									// On récupère le nom de la classe du module si c'est une extension de la classe Module
									$className = $matches[1];
								}elseif(!empty($className) and empty($moduleName) and preg_match('/protected \$name = \'(.*)\'/i', $line, $matches)){
									// On récupère le nom du module
									$moduleName = $matches[1];
								}elseif(!empty($className) and empty($moduleTitle) and preg_match('/protected \$title = \'(.*)\'/i', $line, $matches)){
									// On récupère la description du module
									$moduleTitle = $matches[1];
									$moduleTitle = str_replace("\'", '&#39', $moduleTitle);
								}
								if (count($lines) > 60) break;
							}
							fclose($fp);
							if (!empty($className) and !empty($moduleName)){
								$modules[$className] = array(
									'name'  => $moduleName,
								  'title' => $moduleTitle,
								  'path'  => str_replace(Front::getAbsolutePath().DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR, '', $file)
								);
							}
						}
					}
					ksort($modules);
				?>
				<form class="" method="post" role="form">
					<table class="table table-responsive table-striped">
						<thead>
						<tr>
							<th>Id</th>
							<th>Nom</th>
							<th>Description</th>
							<th>Chemin</th>
							<th>ACL</th>
							<th>Paramètres</th>
							<th>Activé</th>
						</tr>
						</thead>
						<tbody>
						<?php
						foreach ($modules as $className => $module){
							?>
							<tr class="<?php echo (isset($activeModules[$module['name']])) ? '' : 'info text-muted'; ?>">
								<td><?php echo (isset($activeModules[$module['name']])) ? $activeModules[$module['name']]['id'] : ''; ?></td>
								<td><?php echo $module['name']; ?></td>
								<td><?php echo str_replace('\\\'', '&#39;', $module['title']); ?></td>
								<td><?php echo $module['path']; ?></td>
								<?php if (isset($activeModules[$module['name']])){ ?>
								<td><a class="btn btn-default btn-xs" href="<?php echo MODULE_URL.$className.'&page=ACL'; ?>" title="Cliquez ici pour quitter cette page et vous rendre sur les autorisations du module">Autorisations</a></td>
								<td><a class="btn btn-default btn-xs" href="<?php echo MODULE_URL.$className.'&page=settings'; ?>" title="Cliquez ici pour quitter cette page et vous rendre sur les paramètres du module">Paramètres</a></td>
								<?php }else{ ?>
								<td></td>
								<td></td>
								<?php } ?>
								<td>
									<label>
										<input type="checkbox" class="checkbox-activation" id="enable_<?php echo $className; ?>_checkbox" name="enable_<?php echo $className; ?>_checkbox" value="1" <?php if (isset($activeModules[$module['name']])) echo 'checked'; ?>>
										<input type="hidden" id="enable_<?php echo $className; ?>_hidden" name="enable_<?php echo $className; ?>_hidden" value="0">
									</label>
								</td>
							</tr><?php
						}
						?>
						</tbody>
					</table>
					<button type="submit" class="btn btn-primary" id="" name="action" value="saveModules">Sauvegarder</button>
				</form>
				<?php

				?>
			</div>
		</div>
	<?php
	}

	protected function saveModules(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$modules = array();
		foreach ($_REQUEST as $request => $value){
			if (substr($request, 0, 7) == 'enable_'){
				list($dummy, $className, $type) = explode('_', $request);
				if (isset($modules[$className]) and $modules[$className] < $value){
					$modules[$className] = $value;
				}elseif (!isset($modules[$className])){
					$modules[$className] = $value;
				}
			}
		}
		$modulesDb = ModulesManagement::getActiveModules();
		$activatedModules = array();
		foreach ($modulesDb as $module){
			$activatedModules[] = $module->class;
		}
		foreach ($modules as $className => $value){
			$class = 'Modules\\'.$className.'\\'.$className;
			if (!in_array($class, $activatedModules) and $value == 1){
				$enableModule = new $class;
			}elseif(in_array($class, $activatedModules) and $value == 0){
				if (ModulesManagement::disableModule(new $class)){
					new Alert('success', 'Le module a été désactivé !');
				}
			}
		}
		ModulesManagement::getActiveModules(true);
	}

	protected function adminACL(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Autorisations de <?php echo SITE_NAME; ?></h1>
				</div>
				<?php ACL::adminACL('admin', 0, 'l\'administration du site'); ?>
			</div>
		</div>
		<?php
	}

	protected function adminConfig(){
		$dir = str_replace('/Admin', '/Settings', __DIR__);
		$share = new Fs($dir);
		$configFile = $share->readFile('config.php');
		$fileMeta = $share->getFileMeta('config.php');
		$readOnly = ($fileMeta->writable) ? false : true;
		$form = new Form('configFile', null, null, 'admin');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Configuration de <?php echo SITE_NAME; ?></h1>
				</div>
				<p>
					Cette page est générée automatiquement à partir du fichier <code>config.php</code>. Ne modifiez une valeur que si vous savez vraiment ce que vous faites.<br />
					Si vous vous plantez, il y a un risque non négligeable que vous ne puissez plus accéder au site ni à cette page. Dans ce cas, vous pouvez effacer <code>config.php</code> et renommer <code>config.php.backup</code> en <code>config.php</code>.
				</p>
				<?php if ($readOnly) { ?>
				<div class="alert alert-danger">Le fichier <code>config.php</code> n'est pas modifiable par le script ! (ce qui est une bonne chose en matière de sécurité, mais vous y perdez en souplesse d'utilisation)<br /> Pour pouvoir modifier ce fichier à partir de cette interface web, vérifiez que l'utilisateur linux <code><?php echo exec('whoami'); ?></code> a les droits pour modifier le fichier ainsi que le répertoire qui le contient (pour effectuer un backup du fichier).</div>
				<?php }else{ ?>
				<div class="alert alert-info">La modification des paramètres ne sera prise en compte qu'au rafraîchissement de la page.</div>
				<?php
				}
				foreach ($configFile as $key => $line){
					$data = array();
					if (stristr(strtolower($line), 'define')){
						$keyExplain = $key-1; //Il faut reculer de deux pointeurs car le foreach affecte la valeur de la ligne à $line et avance d'un pointeur'
						$explain = trim(trim($configFile[$keyExplain], '/'),'*'); //On récupère la ligne du dessus (définition de la constante)
						$ret = preg_match_all('/\(.+?\)/', $line, $define); //On récupère les chaînes entre guillemets
						$define = explode(',', $define[0][0], 2);
						$constantName = trim($define[0],'(\'');
						preg_match('/serialize\(array\((.*)\)/i', $define[1], $matches);
						if (isset($matches[1])){
							$constantValue = explode(', ', $matches[1]);
							$constantType = 'array';
							array_walk($constantValue, function(&$value, $key) {
								if (!is_int($value)) $value = trim($value, '\'');
							});
							$data = array('serialize' => true);
						}else{
							$constantValue = trim(rtrim($define[1],')'), " "); //On enlève d'abord les parenthèses, puis les espaces
							if (substr($constantValue,-1) == '\''){
								$constantValue =  trim($constantValue, '\'');
								if (count($tab = explode(', ', $constantValue)) > 1){
									$constantType = 'array';
									$constantValue = $tab;
								}else{
									$constantType = 'string';
								}
							}elseif(stristr($constantValue, 'true') or stristr($constantValue, 'false')){
								$constantType = 'bool';
								$data = array(
									'switch' => true,
								  'labelPosition' => 'left'
								);
							}else{
								$constantType = 'int';
							}
						}
						$form->addField(new Field($constantName, $constantType, 'global', $constantValue, $explain, null, $data, 'Paramètre '.$constantName, null, null, null, null, null, null, $readOnly));
					}
				}
				$form->addField(new Field('action', 'button', 'global', 'saveConfig', 'Sauvegarder', null, null, null, null, null, null, null, null, 'btn-primary', $readOnly));
				$form->display();
				?>
			</div>
		</div>
	<?php
	}

	/**
	 * Enregistre les modifications dans le fichier de configuration
	 * @return bool
	 */
	protected function saveConfig(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$req = PostedData::get();
		$dir = str_replace('/Admin', '/Settings', __DIR__);
		$share = new Fs($dir);
		$configFile = $share->readFile('config.php');
		foreach ($configFile as $key => &$line){
			if (stristr(strtolower($line), 'define')){
				preg_match_all('/\(.+?\)/', $line, $define); //On récupère les chaînes entre guillemets
				$define = explode(',', $define[0][0], 2);
				$constantName = trim($define[0],'(\'');
				$constantRet = str_replace('_', '-', trim($constantName));
				if (isset($req[$constantRet])){
					switch (gettype($req[$constantRet])){
						case 'string':
						default:
							$line = 'define(\''.$constantName.'\', \''.$req[$constantRet].'\');';
							break;
						case 'boolean':
							$bool = ($req[$constantRet]) ? 'true' : 'false';
							$line = 'define(\''.$constantName.'\', '.$bool.');';
							break;
						case 'int':
							$line = 'define(\''.$constantName.'\', '.$req[$constantRet].');';
							break;
						case 'array':
							if ($req[$constantRet]['serialize']){
								array_walk($req[$constantRet]['values'], function(&$value, $key) {
									$value = '\''.$value.'\'';
								});
								$line = 'define(\''.$constantName.'\', serialize(array('.\Sanitize::SanitizeForDb($req[$constantRet]['values'], false).')));';
							}else{
								$line = 'define(\''.$constantName.'\', '.\Sanitize::SanitizeForDb($req[$constantRet]['values']).');';
							}
							break;
					}
				}
			}
			$line .= PHP_EOL;
		}
		// On écrit dans le fichier
		$ret = $share->writeFile('config.php', $configFile, false, true);
		if (!$ret){
			new Alert('error', 'La modification des paramètres dans <code>config.php</code> n\'a pas été prise en compte !');
			return false;
		}
		new Alert('success', 'La modification des paramètres dans <code>config.php</code> a été prise en compte !');
		return true;
	}

	/**
	 * Affichage principal
	 */
	public function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Administration de <?php echo SITE_NAME; ?></h1>
				</div>
				<p>Utilisez le menu à gauche pour ouvrir les différentes rubriques de l'administration de ce site.</p>
				<div class="row">
					<div class="col-md-4">
						<h3>Ressources du serveur</h3>
						<?php $this->serverStatus(); ?>
					</div>
					<div class="col-md-4">
						<h3>Poulpe2</h3>
						<?php $this->poulpe2Status(); ?>
					</div>
					<div class="col-md-4">
						<h3>Logiciels</h3>
						<?php $this->softwareStatus(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Retourne un objet contenant l'utilisation en mémoire vive du serveur
	 * @return serverUsage
	 */
	protected function getServerMemory(){
		$free = (string)trim(shell_exec('free'));
		$freeArr = explode("\n", $free);
		$mem = explode(" ", $freeArr[1]);
		$mem = array_filter($mem);
		$mem = array_merge($mem);
		return new serverUsage((($mem[1]-$mem[2])*1024), ($mem[1]*1024), ($mem[2]/$mem[1]*100), true);
	}

	/**
	 * Retourne un objet contenant l'utilisation CPU du serveur
	 * @return serverUsage
	 */
	protected function getServerCPU(){
		$load = sys_getloadavg();
		return new serverUsage((100-$load[0]), 0, $load[0], false);
	}

	/**
	 * Retourne un objet contenant l'utilisation disque du serveur
	 * @return serverUsage
	 */
	protected function getServerDisk(){
		$free = disk_free_space(".");
		$total = disk_total_space(".");
		//$used = $total - $free;
		$usedPercent = (($total - $free)/$total)*100;
		return new serverUsage($free, $total, $usedPercent, true);
	}

	/**
	 * Affiche une barre de progression
	 * @param serverUsage $data Objet contenant les données à afficher
	 *
	 */
	protected function progressBar(serverUsage $data){
		if ($data->percent > 80){
			$level = 'danger';
		}elseif($data->percent < 50){
			$level = 'success';
		}else{
			$level = 'warning';
		}
		if ($data->total > 0){
			$libelle = $data->free.' libres sur '.$data->total;
		}else{
			$libelle = $data->free.' inoccupé';
		}
		?>
		<div class="progress tooltip-bottom" title="<?php echo $libelle; ?>">
			<div class="progress-bar progress-bar-<?php echo $level; ?>" role="progressbar" aria-valuenow="<?php echo $data->percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $data->percent; ?>%">
				<?php echo round($data->percent, 1).'%'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Affiche les ressources utilisées actuellement par le serveur
	 */
	protected function serverStatus(){
		?>
		<p>Occupation de l'espace disque :</p>
		<?php $this->progressBar($this->getServerDisk()); ?>
		<p>Occupation mémoire vive (RAM) :</p>
		<?php $this->progressBar($this->getServerMemory()); ?>
		<p>Occupation du système :</p>
		<?php $this->progressBar($this->getServerCPU()); ?>
		<?php
	}

	protected function softwareStatus(){
		global $db;
		list($phpVersion,) = explode('-', phpversion());
		list($mysqlVersion,) = explode('-', $db->query("SELECT VERSION() as mysql_version", 'val'));
		$linuxDistro = 'Indéfinie';
		@exec('cat /etc/*-release', $versionArr);
		if (count($versionArr) > 1){
			foreach ($versionArr as $line){
				if (strstr($line, '=')){
					list($key, $value) = explode('=', $line);
					if ($key == 'DISTRIB_DESCRIPTION') $linuxDistro = trim($value, '"');
				}
			}
		}else{
			$linuxDistro = $versionArr[0];
		}
		?>
		<ul>
			<li>Version de php : <strong class="text-<?php echo ((float)$phpVersion >= 5.4) ? 'success' : 'danger'; ?>"><?php echo $phpVersion; ?></strong></li>
			<li>Version de MySQL : <strong class="text-<?php echo ((float)$mysqlVersion >= 5.5) ? 'success' : 'danger';?>"><?php echo $mysqlVersion; ?></strong></li>
			<li>Distribution serveur : <strong><?php echo $linuxDistro; ?></strong></li>
			<li>Serveur Web : <strong><?php echo $_SERVER['SERVER_SOFTWARE']; ?></strong></li>
			<li>Répertoire racine : <strong><?php echo $_SERVER['DOCUMENT_ROOT']; ?></strong></li>
		</ul>
		<?php
	}

	protected function poulpe2Status(){
		global $db;
		$dbStatus = $db->query('SHOW TABLE STATUS');
		//var_dump($dbStatus);
		$dbSize = 0;
		$nbTables = count($dbStatus);
		foreach ($dbStatus as $table){
			$dbSize += $table->Data_length + $table->Index_length;
		}
		?>
		<ul>
			<li>Utilisateurs : <strong><?php echo count(UsersManagement::getUsersList()); ?></strong></li>
			<li>Modules actifs : <strong><?php echo count(ModulesManagement::getActiveModules()); ?></strong></li>
			<li>Base de données : <strong><?php echo DB_NAME; ?></strong></li>
			<li>Taille de la base de données : <strong><?php echo \Sanitize::readableFileSize($dbSize); ?></strong></li>
			<li>Nombre de tables dans la base : <strong><?php echo $nbTables; ?></strong></li>
			<li>Mode d'authentification : <strong><?php echo AUTH_MODE; ?></strong></li>
		</ul>
		<?php
	}
} 