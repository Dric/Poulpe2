<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 10/04/14
 * Time: 11:56
 */

namespace Admin;

use Components\Avatar;
use Components\Help;
use Components\serverResource;
use Forms\Fields\BoolField;
use Forms\Fields\Button;
use Forms\Fields\Email;
use Forms\Fields\Hidden;
use Forms\Fields\IntField;
use Forms\Fields\Password;
use Forms\Fields\Select;
use Forms\Fields\StringField;
use Forms\Fields\ValuesArray;
use Forms\JSSwitch;
use Forms\Pattern;
use Git\Git;
use Logs\Alert;
use Components\Item;
use Components\Menu;
use FileSystem\Fs;
use Front;
use Michelf\MarkdownExtra;
use Modules\Module;
use Modules\ModulesManagement;
use Forms\Form;
use Sanitize;
use Settings\Version;
use Users\ACL;
use Users\UsersManagement;

/**
 * Classe d'administration du site
 *
 * @package Admin
 */
class Admin extends Module {

	/**
	 * Nom du module
	 * @var string
	 */
	protected $name = 'Admin';

	/**
	 * Titre du module
	 * @var string
	 */
	protected $title = 'Administration';
	/**
	 * Type de module
	 * @var string
	 */
	protected $type = 'admin';

	/**
	 * Gère le menu d'administration
	 */
	protected function moduleMenu(){
		$menu = new Menu($this->name, 'Administration', '', '', '');
		$menu->add(new Item('modules', 'Modules', $this->url.'&page=modules', 'Administration des modules', 'puzzle-piece'));
		$menu->add(new Item('acl', 'Administrateurs', $this->url.'&page=ACL', 'Gestion des administrateurs', 'unlock-alt'));
		$menu->add(new Item('acl', 'Droits d\'accès', $this->url.'&page=userACL', 'Gestion des autorisations', 'lock'));
		$menu->add(new Item('config', 'Configuration', $this->url.'&page=config', 'Fichier de configuration', 'floppy-o'));
		$menu->add(new Item('users', 'Utilisateurs', $this->url.'&page=users', 'Gestion des utilisateurs', 'users'));
		Front::setSecondaryMenus($menu);
	}

	/**
	 * Administration des modules
	 */
	protected function adminModules(){
		global $db;
		$activeModules = array();
		$modulesDb = ModulesManagement::getActiveModules();
		foreach ($modulesDb as $moduleDb){
			$activeModules[$moduleDb->name] = array(
				'id'    => $moduleDb->id
			);
		}
		$modules = array();
		$modulesPath = Front::getAbsolutePath().DIRECTORY_SEPARATOR.\Settings::MODULE_DIR;
		$fs = new Fs($modulesPath, 'localhost');
		$files = $fs->getRecursiveFilesInDir(null, 'php', true);
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
					  'path'  => str_replace(Front::getAbsolutePath().DIRECTORY_SEPARATOR.\Settings::MODULE_DIR.DIRECTORY_SEPARATOR, '', $file)
					);
				}
			}
		}
		ksort($modules);
	// Si on veut les détails d'un module, il est temps d'aller les afficher
		if (isset($_REQUEST['name']) and isset($modules[$_REQUEST['name']])){
			$requestedModule = $modules[$_REQUEST['name']];
			$requestedModule['className'] = $_REQUEST['name'];
			$requestedModule['id'] = (isset($activeModules[$requestedModule['name']])) ? $activeModules[$requestedModule['name']]['id'] : null;
			$this->adminModuleInfo($requestedModule);
		}else {
			?>
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="page-header">
						<h1>Modules de <?php echo \Settings::SITE_NAME; ?></h1>
					</div>
					<form class="" method="post" role="form">
						<table class="table table-responsive table-striped">
							<thead>
							<tr>
								<th>Id</th>
								<th>Nom</th>
								<th>Description</th>
								<!--<th>Chemin</th>-->
								<th>ACL</th>
								<th>Paramètres</th>
								<th>Activé</th>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($modules as $className => $module) {
								?>
							<tr class="<?php echo (isset($activeModules[$module['name']])) ? '' : 'info text-muted'; ?>">
								<td><?php echo (isset($activeModules[$module['name']])) ? $activeModules[$module['name']]['id'] : ''; ?></td>
								<td><a href="<?php echo $this->url . '&page=modules&name=' . $className; ?>"><?php echo $module['name']; ?></a></td>
								<td><?php echo str_replace('\\\'', '&#39;', $module['title']); ?></td>
								<!--<td><?php echo $module['path']; ?></td>-->
								<?php if (isset($activeModules[$module['name']])) { ?>
									<td><a class="btn btn-default btn-xs" href="<?php echo Front::getModuleUrl() . $className . '&page=ACL'; ?>" title="Cliquez ici pour quitter cette page et vous rendre sur les autorisations du module">Autorisations</a></td>
									<td><a class="btn btn-default btn-xs" href="<?php echo Front::getModuleUrl() . $className . '&page=settings'; ?>" title="Cliquez ici pour quitter cette page et vous rendre sur les paramètres du module">Paramètres</a></td>
								<?php } else { ?>
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
	}

	/**
	 * Affiche les infos d'un module
	 *
	 * Cette fonction ne doit être accédée que via le panneau d'admin des modules
	 *
	 * @param array $module Tableau recensant le chemin, la classe, le nom et la description du module.
	 */
	protected function adminModuleInfo(array $module){
		//var_dump($module);
		$fs = new Fs('Modules' . DIRECTORY_SEPARATOR . $module['className']);
		$readMe = $fs->fileExists('readme.md');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Module <?php echo $module['name']; ?> <?php if (!empty($module['id'])){	?><span class="label label-success">Activé</span><?php }else{	?><span class="label label-danger">Désactivé</span><?php } ?></h1>
				</div>
				<h3>Description</h3>
				<?php
				if ($readMe !== false) {
					$text = $fs->readFile($readMe, 'string');
					?>
					<div class="well">
						<?php
							echo MarkdownExtra::defaultTransform($text);
						?>
					</div>
				<?php	}	?>
			</div>
		</div>
		<?php
	}

	/**
	 * Sauvegarde des modifications sur les modules
	 *
	 * @see self::adminModules()
	 * @return bool
	 */
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
		return true;
	}

	/**
	 * Administration des ACL (permissions)
	 * @see ACL
	 */
	protected function adminACL(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Droits d'accès de <?php echo \Settings::SITE_NAME; ?></h1>
				</div>
				<?php ACL::adminACL('admin', 0, 'l\'administration du site'); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Administration des droits d'accès d'un utilisateur
	 * @see ACL
	 */
	protected function adminUserACL(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$req = $this->postedData;
		if (isset($req['user'])){
			$userSearched = $req['user'];
		}elseif(isset($_REQUEST['user'])){
			$userSearched = $_REQUEST['user'];
		}
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Droits d'accès des utilisateurs</h1>
				</div>
				<?php
				if (isset($userSearched) and is_numeric($userSearched)) {
					ACL::adminUserACL($userSearched);
				}else{
					?>
					<ul>
					<?php
					$usersDb = UsersManagement::getDBUsers();
					$users = array();
					foreach ($usersDb as $user){
						$users[$user->id] = $user->name;
					}
					$users[10000] = 'Droits par défaut';
					$form = new Form('UsersACL', null, null, 'admin', 0, 'post', 'form-inline');
					$form->addField(new Select('user', null, 'Utilisateur', null, false, 'modify', null, false, $users, true));
					$form->addField(new Button('page', 'userACL', 'Voir/Modifier', 'modify', 'btn-sm'));
					$form->display();
					?>
					</ul>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Administration des utilisateurs
	 */
	protected function adminUsers(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$users = UsersManagement::getDBUsers();
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Utilisateurs de <?php echo \Settings::SITE_NAME; ?></h1>
				</div>
				<p>Pour modifier ou supprimer un compte, il vous suffit de cliquer sur son bouton <code>Modifier</code>.</p>
				<table class="table table-responsive table-striped">
					<thead>
						<tr>
							<th>Id</th>
							<th>Nom</th>
							<th>Avatar</th>
							<?php if (strtolower(\Settings::AUTH_MODE) == 'sql') { ?><th>Email</th><?php } ?>
							<th>Dernière connexion <?php echo Help::iconHelp('Ceci est en réalité la dernière tentative de connexion effectuée, qu\'elle ait échoué ou non.'); ?></th>
							<th>Droits d'accès</th>
							<th>Profil/Compte</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($users as $user){
							switch ($user->avatar){
								case 'ldap':
									$ldapUser = UsersManagement::getLDAPUser($user->name);
									$avatar = $ldapUser->avatar;
									break;
								case 'gravatar':
									$avatar = $user->email;
									break;
								default:
									$avatar = $user->avatar;
							}
							?>
							<tr>
								<td><?php echo $user->id; ?></td>
								<td><?php echo $user->name; ?></td>
								<td><?php echo Avatar::display($avatar, 'Avatar de '.$user->name); ?></td>
								<?php if (strtolower(\Settings::AUTH_MODE) == 'sql') { ?><td><?php echo $user->email; ?></td><?php } ?>
								<td><?php if (isset($user->lastLogin) and $user->lastLogin > 0) { echo Sanitize::date($user->lastLogin, 'dateAtTime'); }else{ echo 'Inconnue'; } ?></td>
								<td><a class="btn btn-default" href="<?php echo $this->buildArgsURL(array('page' => 'userACL', 'user' => $user->id)); ?>">Modifier</a></td>
								<td><a class="btn btn-default" href="<?php echo Front::getModuleUrl(); ?>profil&user=<?php echo $user->id; ?>">Modifier</a></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php
					if (strtolower(\Settings::AUTH_MODE) == 'sql'){
						?><h3>Créer un utilisateur</h3><?php
						$form = new Form('createUser');
						$form->addField(new StringField('name', null, 'Nom/Pseudo', 'Veuillez saisir un nom ou un pseudonyme', null, new Pattern('text', true, 4, 150), true));
						$form->addField(new Email('email', null, 'Adresse email', 'nom@domaine.extension', null, new Pattern('email', true, 0, 250), true));
						$form->addField(new Password('pwd', null, 'Mot de passe', 'Mot de passe de '.\Settings::PWD_MIN_SIZE.' caractères minimum', null, new Pattern('password', true, \Settings::PWD_MIN_SIZE, 100), true));
						$form->addField(new Button('action', 'createUser', 'Créer l\'utilisateur'));
						$form->display();
					}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Crée un utilisateur dans la base de données
	 *
	 * @use \UsersManagement::createUser()
	 * @return bool
	 */
	protected function createUser(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$req = $this->postedData;
		if (!isset($req['name'])){
			new Alert('error', 'Vous n\'avez pas indiqué le nom d\'utilisateur !');
			return false;
		}
		if (!isset($req['email'])){
			new Alert('error', 'Vous n\'avez pas indiqué l\'adresse email !');
			return false;
		}
		if (!isset($req['pwd'])){
			new Alert('error', 'Le mot de passe est vide !');
			return false;
		}
		$name = htmlspecialchars($req['name']);
		if (UsersManagement::getDBUsers($name) != null){
			new Alert('error', 'Ce nom d\'utilisateur est déjà pris !');
			return false;
		}
		$email = $req['email'];
		if (!\Check::isEmail($email)){
			new Alert('error', 'Le format de l\'adresse email que vous avez saisi est incorrect !');
			return false;
		}
		$pwd = $req['pwd'];
		// On vérifie que le nouveau mot de passe comporte bien le nombre minimum de caractères requis
		if (strlen($pwd) < \Settings::PWD_MIN_SIZE){
			new Alert('error', 'Le mot de passe doit comporter au moins '.\Settings::PWD_MIN_SIZE.' caractères !');
			return false;
		}
		if (UsersManagement::createDBUser($name, $email, $pwd)){
			new Alert('success', 'L\'utilisateur <code>'.$name.'</code> a été créé ! It\'s alive !');
			return true;
		}else{
			new Alert('error', 'Impossible de créer l\'utilisateur <code>'.$name.'</code> !');
			return false;
		}
	}

	/**
	 * Gère la configuration du site
	 *
	 * Le fichier config.php est mis en lecture seule
	 */
	protected function adminConfig(){
		$dir = Front::getAbsolutePath().DIRECTORY_SEPARATOR.'classes';
		$share = new Fs($dir);
		$configFile = $share->readFile('Settings.php', 'array', true, true);
		$defaultConfigFile = $share->readFile('DefaultSettings.php', 'array', true, true);
		$fileMeta = $share->getFileMeta('Settings.php');
		$readOnly = ($fileMeta->writable) ? false : true;
		if ($readOnly){
			// On essaie de donner les droits d'écriture au script sur le fichier de config
			$ret = $share->setChmod('Settings.php', 777);
			if ($ret){
				$fileMeta = $share->getFileMeta('Settings.php', 'writable');
				$readOnly = ($fileMeta->writable) ? false : true;
			}
			// On remet en lecture seule
			//$share->setChmod('config.php', 644);
		}
		$form = new Form('configFile', null, null, 'admin');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Configuration de <?php echo \Settings::SITE_NAME; ?></h1>
				</div>
				<p>
					Cette page est générée automatiquement à partir du fichier <code>Settings.php</code>. Ne modifiez une valeur que si vous savez vraiment ce que vous faites.<br />
					Si vous vous plantez, il y a un risque non négligeable que vous ne puissiez plus accéder au site ni à cette page. Dans ce cas, vous pouvez effacer <code>Settings.php</code> et renommer <code>Settings.php.backup</code> en <code>Settings.php</code>.
				</p>
				<p>Les valeurs grisées correspondent aux valeurs par défaut. Si vous n'avez pas besoin de les changer, ne complétez pas les champs.</p>
				<?php if ($readOnly) { ?>
				<div class="alert alert-danger">Le fichier <code>Settings.php</code> n'est pas modifiable par le script ! (ce qui est une bonne chose en matière de sécurité, mais vous y perdez en souplesse d'utilisation)<br /> Pour pouvoir modifier ce fichier à partir de cette interface web, vérifiez que l'utilisateur linux <code><?php echo exec('whoami'); ?></code> a les droits pour modifier le fichier ainsi que le répertoire qui le contient (pour effectuer un backup du fichier).</div>
				<?php }else{ ?>
				<div class="alert alert-info">Les paramètres ne seront effectifs qu'au deuxième rafraîchissement de la page.</div>
				<?php
				}
				$defaultFields = $this->analyzeSettingsFromFile($defaultConfigFile, $readOnly);
				$setFields = $this->analyzeSettingsFromFile($configFile, $readOnly, false);
				foreach ($defaultFields as $name => $field){
					if (isset($setFields[$name])){
						$form->addField($setFields[$name]['field']);
					}else {
						$form->addField($field['field']);
					}
					$form->addField($field['explain']);
				}
				$form->addField(new Button('action', 'saveConfig', 'Sauvegarder', null, 'btn-primary', $readOnly));
				$form->display();
				?>
			</div>
		</div>
	<?php
	}

	/**
	 * Analyse le contenu d'un fichier de paramétrage et construit les champs de formulaire avec les paramètres trouvés
	 *
	 * @param array $content    Contenu d'un fichier de paramètres
	 * @param bool  $readOnly   Fichier en lecteure seule ou non
	 * @param bool  $isDefault  Paramètres de configuration par défaut
	 *
	 * @return array Champs de formulaire
	 */
	protected function analyzeSettingsFromFile(Array $content, $readOnly, $isDefault = true){
		$fields = array();
		foreach ($content as $key => $line){
			$data = array();
			if (stristr(strtolower($line), 'const ')){
				$keyExplain = $key-1; //Il faut reculer de deux pointeurs car le foreach affecte la valeur de la ligne à $line et avance d'un pointeur'
				preg_match('/\/\*\* (.*) \*\//', $content[$keyExplain], $match);
				$explain = $match[1]; //On récupère la ligne du dessus (définition de la constante)
				//$ret = preg_match_all('/\(.+?\)/', $line, $define); //On récupère les chaînes entre guillemets
				preg_match('/const (.+?) = (.*);/', $line, $matchesLine);
				$constantName = trim($matchesLine[1], " ");
				preg_match('/array\((.*)\)/i', $matchesLine[2], $matches);
				if (isset($matches[1])){
					if (!empty($matches[1])){
						$constantValue = explode(', ', $matches[1]);
						array_walk($constantValue, function (&$value, $key) {
							if (!is_int($value)) $value = trim($value, '\'');
						});
					} else {
						$constantValue = null;
					}
					if ($isDefault){
						$fields[$constantName]['field'] = new ValuesArray($constantName, array(), $explain, $constantValue, 'Paramètre ' . $constantName, null, true, null, null, $readOnly, true);
					} else {
						$fields[$constantName]['field'] = new ValuesArray($constantName, $constantValue, $explain, null, 'Paramètre ' . $constantName, null, true, null, null, $readOnly, true);
					}
				}else{
					$constantValue = trim($matchesLine[2], " ");
					if (substr($constantValue,-1) == '\''){
						$constantValue =  trim($constantValue, '\'');
						// Pour le choix du module en page d'accueil, on crée une liste avec les modules actifs. de cette façon, on limite les risques d'erreur.
						if ($constantName == 'HOME_MODULE'){
							$activeModules = ModulesManagement::getActiveModules();
							$homeModulesChoice = array('home' => 'Page d\'accueil de base');
							foreach ($activeModules as $module){
								$tab = explode('\\', $module->class);
								if (isset($tab[2])){
									$homeModulesChoice[$tab[2]] = $module->name.' ('.$tab[2].')';
								}
							}
							$fields[$constantName]['field'] = new Select('HOME_MODULE', $constantValue, $explain, 'Paramètre '.$constantName, true, null, null, $readOnly, $homeModulesChoice);
						}else{
							if ($isDefault) {
								$fields[$constantName]['field'] = new StringField($constantName, null, $explain, $constantValue, 'Paramètre ' . $constantName, null, true, null, null, $readOnly);
							} else {
								$fields[$constantName]['field'] = new StringField($constantName, $constantValue, $explain, null, 'Paramètre ' . $constantName, null, true, null, null, $readOnly);
							}
						}
					}elseif(stristr($constantValue, 'true') or stristr($constantValue, 'false')){
						$fields[$constantName]['field'] = new BoolField($constantName, $constantValue, $explain, 'Paramètre '.$constantName, null, true, null, null, $readOnly, new JSSwitch(null, 'left'));
					}else{
						if ($isDefault) {
							$fields[$constantName]['field'] = new IntField($constantName, null, $explain, $constantValue, 'Paramètre ' . $constantName, null, true, null, null, $readOnly);
						} else {
							$fields[$constantName]['field'] = new IntField($constantName, $constantValue, $explain, null, 'Paramètre ' . $constantName, null, true, null, null, $readOnly);
						}
					}
				}
				$fields[$constantName]['explain'] = new Hidden($constantName.'_explain', $explain);
			}
		}
		return $fields;
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
		$req = $this->postedData;
		$dir = Front::getAbsolutePath().DIRECTORY_SEPARATOR.'classes';		$share = new Fs($dir);
		$fileMeta = $share->getFileMeta('Settings.php');
		$readOnly = ($fileMeta->writable) ? false : true;
		if ($readOnly){
			$ret = $share->setChmod('Settings.php', 777);
			if (!$ret){
				new Alert('error', 'Le fichier <code>config.php</code> n\'est pas accessible en écriture !');
				return false;
			}
		}
		$fileContent = '<?php
    
/**
* Paramètres de poulpe2
*
* Importez les constantes de `classes/DefaultSettings` et modifiez-les pour adapter les paramètres à votre instance
*/
class Settings extends DefaultSettings {

';
		foreach ($req as $setting => $value){
			// On ne traite que les champs, pas les explications
			if (strpos($setting, '-explain') === false and $setting != 'action'){
				// Les booléens définis à `false` sont vu comme vides par PHP. Il faut donc faire une exception pour leur traitement
				if (!empty($value) or is_bool($value)){
					if (is_array($value)) {
						if (!empty($value['values'])){
							$stringValue = 'array(';
							foreach ($value['values'] as $subValue) {
								$stringValue .= (is_int($subValue)) ? $subValue : '\'' . $subValue . '\', ';
							}
							$value = rtrim($stringValue, ', ') . ')';
						} else {
							continue;
						}
					}elseif (is_bool($value)) {
						$value = ($value) ? 'true' : 'false';
					}else{
						$value = (is_int($value)) ? $value : '\''.$value.'\'';
					}
					$fileContent .= "\n\t".'/** '.$req[$setting.'-explain'].' */';
					$fileContent .= "\n\t".'const ' . str_replace('-', '_', $setting) . ' = '.$value.';'."\n";
				}
			}
		}
		$fileContent .= '}';
		// On écrit dans le fichier
		$ret = $share->writeFile('Settings.php', $fileContent, false, true);
		if (!$ret){
			new Alert('error', 'La modification des paramètres dans <code>Settings.php</code> n\'a pas été prise en compte !');
			// On remet en lecture seule
			//$share->setChmod('Settings.php', 644);
			return false;
		}
		new Alert('success', 'La modification des paramètres dans <code>Settings.php</code> a été prise en compte !');
		// On remet en lecture seule
		//$share->setChmod('Settings.php', 644);
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
					<h1>Administration de <?php echo \Settings::SITE_NAME; ?></h1>
				</div>
				<p>Utilisez le menu à gauche pour ouvrir les différentes rubriques de l'administration de ce site.</p>
				<div class="row">
					<div class="col-md-4">
						<h3>Logiciels/Serveur</h3>
						<?php $this->softwareStatus(); ?>
					</div>
					<div class="col-md-4">
						<h3>Poulpe2</h3>
						<?php $this->poulpe2Status(); ?>
					</div>
					<div class="col-md-4">
						<h3>Versions</h3>
						<?php $this->poulpe2Versions(); ?>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<h3>Ressources du serveur</h3>
						<?php $this->serverStatus(); ?>
					</div>
					<div class="col-md-4">
						<h3>Mises à jour</h3>
						<?php $this->poulpe2Update(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Affiche les ressources utilisées actuellement par le serveur
	 */
	protected function serverStatus(){
		$serverDisks  = new serverResource('disk');
		$serverMemory = new serverResource('mem');
		$serverCpu    = new serverResource('cpu');
		?>
		<p>Occupation de l'espace disque :</p>
		<?php $serverDisks->displayBar(); ?>
		<p>Occupation mémoire vive (RAM) :</p>
		<?php $serverMemory->displayBar(); ?>
		<p>Occupation CPU :</p>
		<?php $serverCpu->displayBar(); ?>
		<?php
	}

	/**
	 * Affiche les composants logiciels et les répertoires utilisés par Poulpe2
	 */
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
		@exec('getconf LONG_BIT', $architecture);
		$architecture = (!isset($architecture[0]) or empty($architecture)) ? 'Inconnue' : $architecture[0];
		// Récupération de l'adresse IP publique
		$publicIP = @file_get_contents("http://ipecho.net/plain");
		if ($publicIP === false){
			// Si ipecho.net ne répond pas, on passe sur ip4.me
			preg_match("/.*\\+3>(.+?)</mi", file_get_contents('http://ip4.me/'), $match);
			if (isset($match[1])) $publicIP = $match[1];
		}
		?>
		<ul>
			<li>Version de php : <strong class="text-<?php echo ((float)$phpVersion >= 5.4) ? 'success' : 'danger'; ?>"><?php echo $phpVersion; ?></strong></li>
			<li>Version de MySQL/MariaDB : <strong class="text-<?php echo ((float)$mysqlVersion >= 5.5) ? 'success' : 'danger';?>"><?php echo $mysqlVersion; ?></strong></li>
			<li>Distribution serveur : <strong><?php echo $linuxDistro; ?></strong></li>
			<li>Architecture serveur : <strong><?php echo $architecture; ?></strong> bits</li>
			<li>Serveur Web : <strong><?php echo $_SERVER['SERVER_SOFTWARE']; ?></strong></li>
			<li>Répertoire racine : <strong><?php echo $_SERVER['DOCUMENT_ROOT']; ?></strong></li>
			<li>Adresse IP publique : <strong><?php echo $publicIP; ?></strong><?php echo Help::iconHelp('Cette adresse IP est celle que vous avez sur Internet.'); ?></li>
		</ul>
		<?php
	}

	/**
	 * Affiche le statut de Poulpe2
	 */
	protected function poulpe2Status(){
		global $db;
		$dbStatus = $db->query('SHOW TABLE STATUS');
		//var_dump($dbStatus);
		$dbSize = 0;
		$nbTables = count($dbStatus);
		foreach ($dbStatus as $table){
			$dbSize += $table->Data_length + $table->Index_length;
		}
		$gitRepo = Git::open(Front::getAbsolutePath());
		$lastCommit = $gitRepo->getLastCommit();
		$OriginUrl = $gitRepo->getOrigin();
		preg_match('/http(?:s|):\/\/(.+?)\/(?:.*)\/(.+?)(?:\.git|)$/i', $OriginUrl, $matches);

		?>
		<ul>
			<li>Utilisateurs : <strong><?php echo count(UsersManagement::getUsersList()); ?></strong></li>
			<li>Modules actifs : <strong><?php echo count(ModulesManagement::getActiveModules()); ?></strong></li>
			<li>Base de données : <strong><?php echo \Settings::DB_NAME; ?></strong> sur <?php echo \Settings::DB_HOST; ?></li>
			<li>Taille de la base de données : <strong><?php echo \Sanitize::readableFileSize($dbSize); ?></strong></li>
			<li>Nombre de tables dans la base : <strong><?php echo $nbTables; ?></strong></li>
			<li>Mode d'authentification : <strong><?php echo \Settings::AUTH_MODE; ?></strong> <?php if (strtolower(\Settings::AUTH_MODE) == 'ldap') { ?><small>(<?php echo \Settings::LDAP_DOMAIN; ?>)</small><?php } ?></li>
			<li>Version de base de données de Poulpe2 : <strong><?php echo Version::getDbVersion(); ?></strong></li>
			<li>Répertoire des modules : <strong><?php echo \Settings::MODULE_DIR; ?></strong></li>
		</ul>
		<?php
	}

	protected function poulpe2Versions(){
		$coreGitRepo = Git::open(Front::getAbsolutePath());
		$coreLastCommit = $coreGitRepo->getLastCommit();
		$coreOriginUrl = $coreGitRepo->getOrigin();
		preg_match('/http(?:s|):\/\/(.+?)\/(?:.*)\/(.+?)(?:\.git|)$/i', $coreOriginUrl, $coreMatches);
		$modulesGitRepo = Git::open(Front::getAbsolutePath().DIRECTORY_SEPARATOR.\Settings::MODULE_DIR);
		$modulesLastCommit = $modulesGitRepo->getLastCommit();
		$modulesOriginUrl = $modulesGitRepo->getOrigin();
		preg_match('/http(?:s|):\/\/(.+?)\/(?:.*)\/(.+?)(?:\.git|)$/i', $modulesOriginUrl, $modulesMatches);
		?>
		<ul>
			<li>
				Core :
				<ul>
					<li>Version : <a href="<?php echo $coreLastCommit->url; ?>"><?php echo $coreLastCommit->hash; ?></a> du <?php echo Sanitize::date($coreLastCommit->date, 'dateTime'); ?></li>
					<li>Origine : <a href="<?php echo $coreOriginUrl; ?>"><?php echo $coreMatches[1]; ?></a></li>
					<li>Nom du dépôt : <a href="<?php echo $coreOriginUrl; ?>"><?php echo $coreMatches[2]; ?></a></li>
				</ul>
			</li>
			<li>
				Modules :
				<ul>
					<li>Version : <a href="<?php echo $modulesLastCommit->url; ?>"><?php echo $modulesLastCommit->hash; ?></a> du <?php echo Sanitize::date($modulesLastCommit->date, 'dateTime'); ?></li>
					<li>Origine : <a href="<?php echo $modulesOriginUrl; ?>"><?php echo $modulesMatches[1]; ?></a></li>
					<li>Nom du dépôt : <a href="<?php echo $coreOriginUrl; ?>"><?php echo $modulesMatches[2]; ?></a></li>
				</ul>
			</li>
		</ul>
		<?php
	}

	protected function poulpe2Update(){
		if (!ACL::canModify('admin', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$fs = new Fs(Front::getAbsolutePath());
		$disabled = array(
			'cache'   => false,
			'core'    => false,
			'modules' => false
		);
		if (!$fs->isWritable('cache')){
			new Alert('error', 'Le répertoire <code>cache</code> n\'est pas accessible en écriture !');
			$disabled['cache'] = true;
		}
		$disabled['core'] = !$fs->isWritable();
		$disabled['modules'] = !$fs->isWritable(\Settings::MODULE_DIR);

		$lastCheckFile = 'cache/lastUpdateCheck-core.cache';
		$timestamp = ($fs->fileExists($lastCheckFile)) ? (int)$fs->readFile($lastCheckFile, 'string') : 0;


		$disabledForm = (!$disabled['cache'] and !$disabled['core'] and !$disabled['modules']) ? false : true;
		if ($disabledForm){
			?>
			<p>Vous ne pourrez pas effectuer de vérification ou de mise à jour tant que les répertoires suivants ne seront pas accessibles en écriture :</p>
			<ul>
				<?php
				if ($disabled['cache']) { ?><li>Répertoire <code>cache</code></li><?php }
				if ($disabled['core']) { ?><li>Répertoire racine de poulpe2</li><?php }
				if ($disabled['modules']) { ?><li>Répertoire des modules <code><?php echo \Settings::MODULE_DIR; ?></code></li><?php }
				?>
			</ul>
			<?php
		}
		$req = $this->postedData;
		if ((bool)$req['checkUpdates'] and !$disabledForm) {

			?><h4>Mises à jour du core</h4><?php
			$coreUpdates = Version::listGitUpdates('core');

			?><h4>Mises à jour des modules</h4><?php
			$modulesUpdates = Version::listGitUpdates('modules');

			$disabledForm = ((!$coreUpdates and !$modulesUpdates) or $disabledForm) ? true : false;

			$form = new Form('update', null, null, 'admin');
			$form->addField(new Hidden('doUpdates', true, 'admin', $disabledForm));
			$form->addField(new Button('doUpdatesButton', 'go', 'Appliquer les mises à jour', 'admin', null, $disabledForm));
			$form->display();
		} elseif ((bool)$req['doUpdates']){

			$coreGitRepo    = Git::open(Front::getAbsolutePath());
			$modulesGitRepo = Git::open(Front::getAbsolutePath() . DIRECTORY_SEPARATOR . \Settings::MODULE_DIR);
			$retCore = $coreGitRepo->pull('origin', 'master');
			$retModules = $modulesGitRepo->pull('origin', 'master');
			// From http://srv-glpitest/git/Informatique-CHGS/poulpe2 * branch master -> FETCH_HEAD Updating a4c9af5..9c44480 Fast-forward install.php | 2 +- 1 file changed, 1 insertion(+), 1 deletion(-)
			if (preg_match('/error: (.+?) Aborting/mi', $retCore, $coreMatches)){
				new Alert('error', 'Impossible de faire la mise à jour du <code>core</code> :<br>'.$coreMatches[1]);
			}elseif(preg_match('/(?<changed>\d{1,}) file(?:s|) changed, (?<add>\d{1,}) insertion(?:s|)\(\+\), (?<del>\d{1,}) deletion(?:s|)\(\-\)/mi', $retCore, $coreMatches)) {
				new Alert('success', 'Mise à jour du <code>core</code> effectuée :<ul><li><code>' . $coreMatches['changed'] . '</code> fichiers modifiés</li><li><code>' . $coreMatches['add'] . '</code> insertions</li><li><code>' . $coreMatches['del'] . '</code> suppressions</li></ul>');
			}elseif(strpos($retCore, 'Already up-to-date') === true){
			      new Alert('success', 'Mise à jour du <code>core</code> : Le core est déjà dans la version la plus récente.');
			}else{
				new Alert('info', 'Mise à jour du <code>core</code> :<br>'.$retCore);
			}
			if (preg_match('/error: (.+?) Aborting/mi', $retModules, $modulesMatches)) {
				new Alert('error', 'Impossible de faire la mise à jour des <code>modules</code> :<br>' . $modulesMatches[1]);
			}elseif(preg_match('/(?<changed>\d{1,}) file(?:s|) changed, (?<add>\d{1,}) insertion(?:s|)\(\+\), (?<del>\d{1,}) deletion(?:s|)\(\-\)/mi', $retModules, $modulesMatches)) {
				new Alert('success', 'Mise à jour des <code>modules</code> effectuée :<ul><li><code>' . $modulesMatches['changed'] . '</code> fichiers modifiés</li><li><code>' . $modulesMatches['add'] . '</code> insertions</li><li><code>' . $modulesMatches['del'] . '</code> suppressions</li></ul>');
			}elseif(strpos($retModules, 'Already up-to-date') === true){
				new Alert('success', 'Mise à jour des <code>modules</code> : Les modules sont déjà dans la version la plus récente.');
			}else {
				new Alert('info', 'Mise à jour des <code>modules</code> :<br>'.$retModules);
			}
			?>
			<p>Mise à jour lancée, veuillez cliquer sur le bouton ci-dessous :</p>
			<div class="text-center"><a class="btn btn-primary" href="<?php echo $this->url; ?>">Terminer la mise à jour</a></div>
			<?php
		} else {
			$form = new Form('update', null, null, 'admin');
			$form->addField(new Hidden('checkUpdates', true, 'admin', $disabledForm));
			$form->addField(new Button('checkUpdatesButton', 'fetch', 'Vérifier les mises à jour', 'admin', null, $disabledForm));
			if (!$disabledForm){
				if ($timestamp > 0) {
					if (time() - $timestamp > 7776000) {
						// 3 mois
						$color = 'danger';
					} elseif (time() - $timestamp > 2592000) {
						//1 mois
						$color = 'warning';
					} else {
						$color = 'default';
					}
					?><p>Dernière vérification le <strong class="text-<?php echo $color; ?>"><?php echo Sanitize::date($timestamp, 'dateTime') ?></strong> <?php Help::icon('clock-o', 'info', 'il y a ' . Sanitize::timeDuration(time() - $timestamp)); ?></p><?php
				} else {
					?><p class="text-danger">Pas de vérifications précédentes.</p><?php
				}
			}
			$form->display();
		}
		return true;
	}
} 