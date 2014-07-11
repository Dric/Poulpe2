<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/04/14
 * Time: 12:18
 */

namespace Profiles;
use Forms\Fields\Button;
use Forms\Fields\File;
use Forms\Fields\RadioList;
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

	public function __construct(){
		$this->moduleMenu();
		$this->breadCrumb = array(
			'title' => $this->name,
			'link'  => MODULE_URL.$this->name
		);
	}

	protected function saveUserProfile(){
		global $cUser;
		$req = PostedData::get();
		$avatar = $req['avatar'];
		// Si un fichier est chargé, alors l'utilisateur veut certainement celui-ci comme avatar...
		if (isset($_FILES['field_file_avatarFile']) and !empty($_FILES['field_file_avatarFile']['name'])) $avatar = 'user';

		switch ($avatar){
			case 'default':   $cUser->setAvatar('default'); break;
			case 'gravatar':  $cUser->setAvatar('gravatar'); break;
			case 'user':
				$userAvatarFile = Sanitize::sanitizeFilename($cUser->getName()).'.png';
				if ((!isset($_FILES['field_avatarFile']) or empty($_FILES['field_avatarFile']['name'])) and !file_exists(AVATAR_PATH.$userAvatarFile)){
					// Si l'utilisateur n'a pas d'image déjà chargée et qu'il n'en a pas indiqué dans le champ adéquat, on retourne false
					new Alert('error', 'Impossible de sauvegarder l\'avatar, aucune image n\'a été chargée !');
					return false;
				}elseif(isset($_FILES['field_avatarFile']) and !empty($_FILES['field_avatarFile']['name'])){
					// Chargement de l'image
					$args = array();
					$args['resize'] = array('width' => 80, 'height' => 80);
					// Les avatars auront le nom des utilisateurs, et seront automatiquement transformés en .png par SimpleImages
					$args['name'] = $cUser->getName().'.png';
					$avatarFile = Upload::File($_FILES['field_avatarFile'], AVATAR_PATH, AVATAR_MAX_SIZE, unserialize(ALLOWED_IMAGES_EXT), $args);
					if (!$avatar) {
						new Alert('error', 'L\'avatar n\'a pas été pris en compte !');
						return false;
					}
					$cUser->setAvatar($avatarFile);
				}else{
					// Si l'utilisateur a déjà une image chargée et qu'il n'en a pas indiqué de nouvelle, on lui remet celle qu'il a déjà.
					$cUser->setAvatar($userAvatarFile);
				}
				break;
			case 'ldap': $cUser->setAvatar('ldap');
		}
		if (UsersManagement::updateDBUser($cUser)){
		  new Alert('success', 'Les paramètres du profil ont été correctement sauvegardés !');
			return true;
		}else{
			new Alert('error', 'Echec de la sauvegarde des paramètres !');
			return false;
		}
	}


	public function mainDisplay(){
		global $cUser;

		switch ($cUser->getAvatar(true)){
			case null:
			case 'default':   $valueAvatar = 'default'; break;
			case 'ldap':      $valueAvatar = 'ldap'; break;
			case 'gravatar':  $valueAvatar = 'gravatar'; break;
			default:          $valueAvatar = 'user';
		}

		$dataAvatar = array();
		$dataAvatar['default'] = Avatar::display(null, 'Avatar par défaut');
		// On propose l'avatar LDAP si celui-ci est disponible.
		if (!empty($cUser->getLDAPProps()->avatar)) $dataAvatar['ldap'] = Avatar::display($cUser->getLDAPProps()->avatar, 'Avatar LDAP');
		$dataAvatar['gravatar'] = Avatar::display($cUser->getEmail(), 'Gravatar');
		// On teste l'existence d'un avatar chargé par l'utilisateur
		$userAvatarFile = Sanitize::sanitizeFilename($cUser->getName()).'.png';
		if (file_exists(AVATAR_PATH.$userAvatarFile)){
			$dataAvatar['user'] = Avatar::display($userAvatarFile, 'Votre avatar');
		}else{
			$dataAvatar['user'] = 'Choisir un fichier';
		}


		$form = new Form('userProfile', null, null);
		$form->addField(new RadioList('avatar', 'global', $valueAvatar, null, 'Avatar', 'Choisissez une image pour vous représenter (avatar)', false, null, null, null, false, $dataAvatar, $cUser->getAvatar(true)));
		$form->addField(new File('avatarFile', 'user', null, null, 'Fichier de l\'avatar'));
		$form->addField(new Button('action', 'global', 'saveUserProfile', 'Sauvegarder'));

		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Profil de <?php echo $cUser->getName(); ?></h1>
				</div>
				<?php $form->display(); ?>
				<?php //echo \Get::varDump($cUser->getLDAPProps()) ?>
			</div>
		</div>
		<?php
	}
}
