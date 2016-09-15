<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 15/04/14
 * Time: 11:26
 */

namespace Components;
use Check;
use Front;

/**
 * Class de gestion de l'avatar
 *
 * @package Components
 */
class Avatar {

	/**
	 * retourne un avatar mis en forme
	 *
	 * @param string $avatar Avatar à afficher (dans le cas d'un gravatar, $avatar doit être une adresse email)
	 * @param string $avatarTitle Titre à mettre dans la propriété 'alt' de l'image
	 *
	 * @return string
	 */
	public static function display($avatar = null,  $avatarTitle = 'Avatar') {
		$pathInfo = pathinfo($avatar);
		if (empty($avatar) or $avatar == 'default'){
			$src = Front::getBaseUrl() . '/'. AVATAR_PATH . AVATAR_DEFAULT;
		}elseif (isset($pathInfo['extension']) and in_array($pathInfo['extension'], array('jpg', 'jpeg', 'gif', 'png', 'bmp'))){
			$src = Front::getBaseUrl() . '/'. AVATAR_PATH . $avatar;
		}elseif(Check::isEmail($avatar)){
			$src = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $avatar ) ) ) . '?s=80';
		}else{
			// avatar en format binaire (venant de LDAP)
			$src = 'data:image/jpg;base64,' . base64_encode($avatar);
		}
		return '<img class="img-circle avatar tooltip-bottom" alt="' . $avatarTitle . '" src="' . $src . '">';
	}

	/**
	 * Contrôle si l'utilisateur a un gravatar ou non.
	 *
	 * @param string $email Adresse email de l'utilisateur, afin de récupérer son gravatar
	 * @return bool True si l'utilisateur a un gravatar, false sinon.'
	 */
	public static function hasGravatar($email) {
		// Craft a potential url and test its headers
		$hash = md5($email);
		$uri = 'https://www.gravatar.com/avatar/' . $hash . '?d=404';
		$headers = @get_headers($uri);
		if (!preg_match("|200|", $headers[0])) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
}