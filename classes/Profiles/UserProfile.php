<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/04/14
 * Time: 12:18
 */

namespace Profiles;
use Forms\Fields\Button;
use Forms\Fields\Email;
use Forms\Fields\File;
use Forms\Fields\Hidden;
use Forms\Fields\LinkButton;
use Forms\Fields\Password;
use Forms\Fields\RadioList;
use Forms\Fields\String;
use Forms\Pattern;
use Logs\Alert;
use Components\Avatar;
use FileSystem\Upload;
use Front;
use Modules\Module;
use Sanitize;
use Forms\Field;
use Forms\Form;
use Forms\PostedData;
use Settings\Setting;
use Users\ACL;
use Users\Login;
use Users\User;
use Users\UsersManagement;

/**
	 * Profil de l'utilisateur
	 *
	 * Cette classe doit avoir des fonctions publiques similaires à celles des modules, car elle est appelée de la même façon que ces derniers.
	 *
	 * @package Profiles
	 */
class UserProfile extends Module {

	protected $name = 'profil';
	protected $title = 'Profil';
	/**
	 * Objet utilisateur dont on veut modifier le profil
	 * @var User
	 */
	protected $user = null;
	/**
	 * @var Form
	 */
	protected $form = null;

	public function __construct(){
		$this->moduleMenu();
		$this->breadCrumb = array(
			'title' => $this->name,
			'link'  => MODULE_URL.$this->name
		);
		if (isset($_REQUEST['user'])){
			if ($_REQUEST['user'] != $GLOBALS['cUser']->getId() and !ACL::canAdmin('admin', 0)){
				new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
				$this->user = $GLOBALS['cUser'];
			}else{
				$users = UsersManagement::getDBUsers();
				$found = \Get::getObjectsInList($users, 'id', $_REQUEST['user']);
				if (empty($found)){
					new Alert('error', 'Cet utilisateur n\'existe pas !');
					$this->user = $GLOBALS['cUser'];
				}else{
					$this->user = new User($_REQUEST['user']);
					$this->title = 'Profil de '.$this->user->getName();
				}
			}
		}else{
			$this->user = $GLOBALS['cUser'];
		}
		$this->form = new Form('userProfile');
	}

	/**
	 * Sauvegarde les informations du profil
	 *
	 * @return bool
	 */
	protected function saveUserProfile(){
		$req = $this->postedData;
		$avatar = $req['avatar'];
		// Si un fichier est chargé, alors l'utilisateur veut certainement celui-ci comme avatar...
		if (isset($_FILES['field_string_avatarFile']) and !empty($_FILES['field_string_avatarFile']['name'])) $avatar = 'user';

		switch ($avatar){
			case 'default':   $this->user->setAvatar('default'); break;
			case 'gravatar':  $this->user->setAvatar('gravatar'); break;
			case 'user':
				$userAvatarFile = Sanitize::sanitizeFilename($this->user->getName()).'.png';
				if ((!isset($_FILES['field_string_avatarFile']) or empty($_FILES['field_string_avatarFile']['name'])) and !file_exists(AVATAR_PATH.$userAvatarFile)){
					// Si l'utilisateur n'a pas d'image déjà chargée et qu'il n'en a pas indiqué dans le champ adéquat, on retourne false
					new Alert('error', 'Impossible de sauvegarder l\'avatar, aucune image n\'a été chargée !');
					return false;
				}elseif(isset($_FILES['field_string_avatarFile']) and !empty($_FILES['field_string_avatarFile']['name'])){
					// Chargement de l'image
					$args = array();
					$args['resize'] = array('width' => 80, 'height' => 80);
					// Les avatars auront le nom des utilisateurs, et seront automatiquement transformés en .png par SimpleImages
					$args['name'] = $this->user->getName().'.png';
					$avatarFile = Upload::File($_FILES['field_string_avatarFile'], AVATAR_PATH, AVATAR_MAX_SIZE, unserialize(ALLOWED_IMAGES_EXT), $args);
					if (!$avatar) {
						new Alert('error', 'L\'avatar n\'a pas été pris en compte !');
						return false;
					}
					$this->user->setAvatar($avatarFile);
				}else{
					// Si l'utilisateur a déjà une image chargée et qu'il n'en a pas indiqué de nouvelle, on lui remet celle qu'il a déjà.
					$this->user->setAvatar($userAvatarFile);
				}
				break;
			case 'ldap': $this->user->setAvatar('ldap');
		}
		// L'authentification via LDAP ramène déjà le nom l'adresse email et la gestion du mot de passe
		if (AUTH_MODE == 'sql'){
			if (!isset($req['name'])){
				new Alert('error', 'Vous n\'avez pas indiqué le nom d\'utilisateur !');
				return false;
			}
			if (!isset($req['email'])){
				new Alert('error', 'Vous n\'avez pas indiqué l\'adresse email !');
				return false;
			}
			$name = htmlspecialchars($req['name']);
			if (UsersManagement::getDBUsers($name) != null and $name != $this->user->getName()){
				new Alert('error', 'Ce nom d\'utilisateur est déjà pris !');
				return false;
			}
			$email = $req['email'];
			if (!\Check::isEmail($email)){
				new Alert('error', 'Le format de l\'adresse email que vous avez saisi est incorrect !');
				return false;
			}
			$currentPwd = (isset($req['currentPwd'])) ? $req['currentPwd'] : null;
			$newPwd = (isset($req['newPwd'])) ? $req['newPwd'] : null;
			if (!empty($newPwd)){
				// On vérifie que le mot de passe actuel a bien été saisi
				if (!ACL::canAdmin('admin', 0) and empty($currentPwd)){
					new Alert('error', 'Vous avez saisi un nouveau mot de passe sans saisir le mot de passe actuel !');
					return false;
				}
				// On vérifie que le mot de passe actuel est correct
				if (!ACL::canAdmin('admin', 0) and Login::saltPwd($currentPwd) != $this->user->getPwd()){
					new Alert('error', 'Le mot de passe actuel que vous avez saisi est incorrect !');
					return false;
				}
				// On vérifie que le nouveau mot de passe comporte bien le nombre minimum de caractères requis
				if (strlen($newPwd) < PWD_MIN_SIZE){
					new Alert('error', 'Le mot de passe doit comporter au moins '.PWD_MIN_SIZE.' caractères !');
					return false;
				}
				$this->user->setPwd($newPwd);
			}
		}

		if (UsersManagement::updateDBUser($this->user)){
			$msg = ($this->user->getId() == $GLOBALS['cUser']->getId()) ? '' : 'de '.$this->user->getName().' ';
		  new Alert('success', 'Les paramètres du profil '.$msg.'ont été correctement sauvegardés !');
			return true;
		}else{
			new Alert('error', 'Echec de la sauvegarde des paramètres !');
			return false;
		}
	}

	/**
	 * Supprime un compte utilisateur
	 * @return bool
	 */
	protected function confirmDeleteUser(){
		if (UsersManagement::deleteUser($this->user)){
			new Alert('success', (($this->user->getId() == $GLOBALS['cUser']->getId()) ? 'Votre compte' : 'Le compte de '.$this->user->getName()).' a été supprimé !');
			return true;
		}else{
			new Alert('error', 'Impossible de supprimer le compte utilisateur !');
			return false;
		}
	}


	public function mainDisplay(){
		if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'deleteUser'){
			?>
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="row">
						<div class="col-md-8 col-md-offset-2 text-center">
							<div class="alert alert-danger alert-block">
								<h3>Attention !! Vous allez supprimer <?php echo ($this->user->getId() == $GLOBALS['cUser']->getId()) ? 'votre compte' : 'le compte '.$this->user->getName(); ?> !!</h3>
							</div>
							<p>Confirmez-vous cet acte irrécupérable ?</p>
							<div class="">
								<?php
								$form = new Form('deleteConfirmation');
								$form->addField(new Hidden('user', 'global', $this->user->getId()));
								$form->addField(new Button('action', 'global', 'confirmDeleteUser', 'Je confirme la suppression', null, 'btn-danger'));
								$form->addField(new LinkButton('cancel', 'global', $this->url, 'Annuler'));
								$form->display();
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}else{
			?>
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="page-header">
						<h1>Profil de <?php echo $this->user->getName(); ?></h1>
					</div>
					<?php
					if (AUTH_MODE == 'sql'){
						$this->accountFormItems();
						$this->passwordFormItems();
					}
					?>
					<?php $this->avatarFormItems(); ?>
					<?php $this->profileFormButtons(); ?>
					<?php $this->form->display(); ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Prépare les champs de choix d'avatar
	 */
	protected function avatarFormItems(){

		switch ($this->user->getAvatar(true)){
			case 'default':
			case 'ldap':
			case 'gravatar':
				$valueAvatar = $this->user->getAvatar(true);
				break;
			case null:
				$valueAvatar = 'default';
				break;
			default:
				$valueAvatar = 'user';
		}
		$dataAvatar = array();
		$dataAvatar['default'] = Avatar::display(null, 'Avatar par défaut');
		// On propose l'avatar LDAP si celui-ci est disponible.
		if (!empty($this->user->getLDAPProps()->avatar)) $dataAvatar['ldap'] = Avatar::display($this->user->getLDAPProps()->avatar, 'Avatar LDAP');
		if ($this->user->getEmail() != '') $dataAvatar['gravatar'] = Avatar::display($this->user->getEmail(), 'Gravatar');
		// On teste l'existence d'un avatar chargé par l'utilisateur
		$userAvatarFile = Sanitize::sanitizeFilename($this->user->getName()).'.png';
		if (file_exists(AVATAR_PATH.$userAvatarFile)){
			$dataAvatar['user'] = Avatar::display($userAvatarFile, 'Avatar personnalisé');
		}else{
			$dataAvatar['user'] = 'Choisir un fichier';
		}
		$this->form->addField(new RadioList('avatar', 'global', $valueAvatar, null, 'Avatar', 'Choisissez une image pour vous représenter (avatar)', false, null, null, false, $dataAvatar, $this->user->getAvatar(true)));
		$this->form->addField(new File('avatarFile', 'user', null, null, 'Fichier de l\'avatar'));

	}

	/**
	 * Prépare les champs de changement de nom et d'adresse email
	 */
	protected function accountFormItems(){
		$this->form->addField(new String('name', 'global', $this->user->getName(), null, 'Nom/Pseudo', 'Veuillez saisir un nom ou un pseudonyme', null, new Pattern('text', true, 4, 150), true));
		$this->form->addField(new Email('email', 'global', $this->user->getEmail(), null, 'Adresse email', 'adresse@domaine.extension', null, new Pattern('email', true, 0, 250), true));
	}

	/**
	 * Prépare les champs de changement de mot de passe
	 */
	protected function passwordFormItems(){
		$this->form->addField(new Password('currentPwd', 'global', null, null, 'Mot de passe actuel', 'Laissez ce champ vide si vous ne souhaitez pas changer de mot de passe', 'Ne saisissez votre mot de passe actuel que si vous souhaitez en changer', new Pattern('password', false, PWD_MIN_SIZE, 100), true));
		$this->form->addField(new Password('newPwd', 'global', null, null, 'Nouveau mot de passe', 'Mot de passe de '.PWD_MIN_SIZE.' caractères minimum', 'Ne saisissez un mot de passe ici que si vous souhaitez en changer', new Pattern('password', false, PWD_MIN_SIZE, 100), true));
	}

	protected function profileFormButtons(){
		$this->form->addField(new Hidden('user', 'global', $this->user->getId()));
		$this->form->addField(new Button('action', 'global', 'saveUserProfile', 'Sauvegarder'));
		$this->form->addField(new Button('action', 'global', $this->url.'deleteUser', 'Supprimer '.(($this->user->getId() == $GLOBALS['cUser']->getId()) ? 'mon compte' : 'le compte '.$this->user->getName()), null, 'btn-danger'));
	}
}
