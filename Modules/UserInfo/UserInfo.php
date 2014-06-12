<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 20/05/14
 * Time: 09:02
 */

namespace Modules\UserInfo;


use Components\Help;
use Components\Item;
use Front;
use Ldap\Ldap;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Sanitize;
use Settings\Field;
use Settings\Form;
use Settings\PostedData;

class UserInfo extends Module{
	protected $name = 'Informations utilisateur';
	protected $title = 'Affiche les informations d\'un utilisateur Active Directory';

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('userInfo', 'Infos utilisateur', MODULE_URL.end(explode('\\', get_class())), 'Affiche les informations d\'un utilisateur Active Directory', null, null));
	}

	/**
	 * Installe le module
	 */
	public function install(){
		// On renseigne le chemin du module
		$this->path = basename(__DIR__).DIRECTORY_SEPARATOR.basename(__FILE__);
		// Définition des ACL par défaut pour ce module
		$defaultACL = array(
			'type'  => 'modify',
			'value' => true
		);
		return ModulesManagement::installModule($this, $defaultACL);
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		Front::setJsFooter('<script src="js/bootstrap3-typeahead.min.js"></script>');
		Front::setJsFooter('<script src="Modules/UserInfo/UserInfo.js"></script>');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Informations utilisateur AD  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					Cette page vous permet d'afficher plein d'informations sur un utilisateur du domaine.
				</p>
				<?php
				$userSearched = null;
				$req = PostedData::get();
				if (isset($req['user'])){
					$userSearched = $req['user'];
				}elseif(isset($_REQUEST['user'])){
					$userSearched = $_REQUEST['user'];
				}
				$this->searchForm($userSearched);
				if (!empty($userSearched)) $this->userInfo($userSearched);
				?>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Affiche le champ de recherche utilisateurs
	 * @param string $userSearched Utilisateur recherché (juste pour affichage)
	 */
	protected function searchForm($userSearched = null){
		$form = new Form('userSearch', null, null, 'module', $this->id, 'post', 'form-inline');
		$form->addField(new Field('user', 'string', 'global', $userSearched, 'Nom de l\'utilisateur', 'prenom.nom', array('autocomplete' => false), 'La recherche peut se faire sur un login complet (prenom.nom) ou sur une partie de celui-ci', null, null, false, null, 'access'));
		$form->addField(new Field('action', 'button', 'global', 'getUserInfo', 'Rechercher', null, null, null, null, null, false, null, 'access', 'btn-primary btn-sm'));
		$form->display();
	}

	/**
	 * Affiche un tableau JSON des utilisateurs correspondant à la recherche.
	 *
	 * Utilisable uniquement dans le cas d'une requête asynchrone (AJAX)
	 */
	protected function returnUsers(){
		$ldap = new Ldap();
		$req = PostedData::get();
		header('Content-type: application/json');
		echo $ldap->users('json', $req['query']);
		exit();
	}

	/**
	 * Affiche les infos d'un utilisateur AD
	 * @param string $userSearched Utilisateur
	 *
	 * @return bool
	 */
	public function userInfo($userSearched){
		$ldap = new Ldap();
		$user = $ldap->search('user', $userSearched, null, array(), true);
		if ($user['count'] == 0){
			new Alert('error', 'L\'utilisateur <code>'.$userSearched.'</code> n\'existe pas !');
			return false;
		}
		$user = $user[0];
		//var_dump($user);
		?>
		<h2><?php echo $user['cn'][0]; ?></h2>
		<ul>
			<li>Compte <span <?php echo ($user["useraccountcontrol"][0] == "514" or $user["useraccountcontrol"][0] == "66050") ? 'class="text-error">désactivé' : 'class="text-success">activé' ; ?></span></li>
			<li>Nom affiché : <strong><?php echo $user['displayname'][0]; ?></strong></li>
			<li>Description : <?php echo (isset($user['description'][0])) ? '<strong>'.$user['description'][0].'</strong>' : '<span class="text-danger">manquante</span>'; ?></li>
			<?php if (isset($user['proxyaddresses'])) {
				$email = 'manquante';
				foreach ($user['proxyaddresses'] as $emailAdr){
					if (substr($emailAdr, 0, 4) == 'SMTP'){
						$email = substr($emailAdr, 5);
					}
				}
				$mdbTab = explode(',', $user['homemdb'][0]);
				$mdb = ltrim($mdbTab[0], 'CN=');
			?>
			<li>Adresse email : <strong><?php echo $email; ?></strong></li>
			<li>Alias de boîte Exchange : <strong><?php echo $user['mailnickname'][0]; ?></strong></li>
			<li>Base de données exchange : <strong><?php echo $mdb; ?></strong></li>
			<?php }else{ ?>
			<li>Adresse email : <span class="text-error">Non</span></li>
			<?php } ?>

			<li>Créé le <strong><?php echo Sanitize::date(Sanitize::ADToUnixTimestamp($user['whencreated'][0]), 'dateTime'); ?></strong></li>
			<li>Dernière connexion le <strong><?php echo Sanitize::date($ldap->lastlogon($userSearched), 'dateTime'); ?></strong></li>
			<li>Membre des groupes <?php Help::iconHelp('L\'appartenance aux groupes est recherchée de façon récursive : si un groupe de l\'utilisateur est membre d\'un autre groupe, ce dernier apparaîtra aussi dans la liste.'); ?> :
				<ol>
					<?php
					$userGroups = $ldap->userMembership($userSearched);
					if (!empty($userGroups)){
						foreach($userGroups as $group){
							?><li><?php echo $group; ?></li><?php
						}
					}else{
						?><li>Aucun groupe</li><?php
					}
					?>
				</ol>
			</li>
		</ul>
		<?php
		return true;
	}
} 