<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 20/05/14
 * Time: 09:02
 */

namespace Modules\UserInfo;


use API\APIManagement;
use Components\Help;
use Components\Item;
use DateInterval;
use Forms\Fields\Button;
use Forms\Fields\String;
use Front;
use Ldap\Ldap;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Sanitize;
use Forms\Field;
use Forms\Form;
use Forms\PostedData;

class UserInfo extends Module{
	protected $name = 'Informations utilisateur';
	protected $title = 'Affiche les informations d\'un utilisateur Active Directory';

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('userInfo', 'Infos utilisateur', Front::getModuleUrl().end($module), 'Affiche les informations d\'un utilisateur Active Directory', null, null));
	}

	/**
	 * Installe le module
	 */
	public function install(){
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
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/js/bootstrap3-typeahead.min.js"></script>');
		Front::setJsFooter('<script src="'.Front::getBaseUrl().'/Modules/UserInfo/UserInfo.js"></script>');
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
				$req = $this->postedData;
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
		$form->addField(new String('user', $userSearched, 'Nom de l\'utilisateur', 'prenom.nom', 'La recherche peut se faire sur un login complet (prenom.nom) ou sur une partie de celui-ci', null, true, 'access', null, false, false));
		$form->addField(new Button('action', 'getUserInfo', 'Rechercher', 'access', 'btn-primary btn-sm'));
		$form->display();
	}

	/**
	 * Affiche un tableau JSON des utilisateurs correspondant à la recherche.
	 *
	 * Utilisable uniquement dans le cas d'une requête asynchrone (AJAX)
	 */
	protected function returnUsers(){
		$ldap = new Ldap();
		$req = $this->postedData;
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
			<li>Matricule : <?php echo (isset($user['employeeid'][0])) ? '<strong>'.$user['employeeid'][0].'</strong>' : '<span class="text-danger">manquant</span>'; ?></li>
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
			<?php
			$hasXenAppLogs = false;
			if (ModulesManagement::isActiveModule('Modules\UsersTraces2\UsersTraces2')){
				// On envoie la requête pour récupérer la date de dernière connexion à Citrix à l'API
				$curlResult = APIManagement::sendAPIRequest('api/getLogData/user/'.$userSearched.'/lastCitrixLogin');
				if ($curlResult['result'] == 'success' and isset($curlResult['data'])) {
					$lastCitrixLogin = key($curlResult['data']);
					?>
					<li>Dernière ouverture de session sous Citrix XenApp 7 : <strong><?php echo Sanitize::date($lastCitrixLogin, 'dateAtTime'); ?></strong></li>
					<?php
					$hasXenAppLogs = true;
					// On envoie la requête pour récupérer les derniers postes utilisés à l'API
					$limit = new \DateTime();
					$limit->setTimestamp($lastCitrixLogin);
					$limit->sub(DateInterval::createFromDateString("1 month"));
					$curlResult = APIManagement::sendAPIRequest('api/getLogData/user/'.$userSearched.'/lastClients/'.$limit->getTimestamp());
					if ($curlResult['result'] == 'success' and isset($curlResult['data'])) {
						?><li>Liste des postes utilisés le mois précédent la dernière connexion : <ul><?php
						// On inverse clés et valeurs
						$clients = array_flip($curlResult['data']);
						// On trie par ordre décroissant pour afficher les postes les plus utilisés d'abord
						krsort($clients);
						foreach ($clients as $count => $client){
							if (!empty($client)){
								?><li><strong><?php echo $client; ?></strong> (<?php echo $count;?> connexions)</li><?php
							}
						}
						?></ul></li><?php
					}
				}
			}
			?>
			<li>Dernière connexion à Active Directory <?php Help::iconHelp('L\'heure de connexion à l\'AD peut être postérieure à celle de l\'ouverture réelle de session, des vérifications au sein de l\'AD étant régulièrement faites par le système.'); ?> le <strong><?php echo Sanitize::date($ldap->lastlogon($userSearched), 'dateTime'); ?></strong></li>
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