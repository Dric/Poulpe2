<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 17/04/14
 * Time: 11:25
 */

namespace FileSystem;


use Logs\Alert;
use Components;
use Exception;
use Get;
use Sanitize;

class Upload {

	/**
	 * Traite un upload de fichier et retourne son nom
	 *
	 * @param array  $file tableau $_FILE[fichier]
	 * @param string $moveTo Répertoire de destination
	 * @param int    $maxSize Taille maximum du fichier, en ko.
	 * @param array  $allowedExtensions Tableau des extensions autorisées de la forme array('jpg', 'png', ...) - accepte toutes les extensions si vide
	 * @param array  $args Tableau de paramètres - accepte les clés suivantes :
	 * - 'resize' array   Tableau de redimensionnement pour les images - les valeurs sont en pixels - accepte les clés suivantes :
	 *    - 'width'     int Largeur désirée en pixels (facultatif)
	 *    - 'height'    int Hauteur désirée en pixels (facultatif)
	 *    - 'toSquare'  bool   Si true, l'image sera redimensionnée/tronquée pour former un carré (largeur et hauteur identiques) suivant la largeur ou la hauteur définie.
	 * - 'name'   string  Nom à donner au fichier (est formaté comme il faut par la fonction) sans son extension (celle-ci est automatiquement rajoutée).
	 *
	 * @return bool|string false si erreur, nom du fichier chargé si OK
	 */
	public static function File($file, $moveTo, $maxSize, $allowedExtensions = array(), $args = array()) {
		// Commençons par voir si php a remonté une erreur
		if ($file['error'] > 0) {
			$message = 'Une erreur est survenue au cours du transfert : ';
			switch ($file['error']) {
				case UPLOAD_ERR_NO_FILE :
					$message .= 'le fichier est manquant !';
					break;
				case UPLOAD_ERR_INI_SIZE :
					$message .= 'le fichier dépasse la taille maximale autorisée par PHP.';
					break;
				case UPLOAD_ERR_FORM_SIZE :
					$message .= 'le fichier dépasse la taille maximale autorisée par le formulaire.';
					break;
				case UPLOAD_ERR_PARTIAL :
					$message .= 'le fichier a été transféré partiellement.';
					break;
				default:
					$message .= 'erreur inconnue';
			}
			new Alert('error', $message);
			return false;
		}
		// Assurons-nous que le chemin de destination est correct
		$moveTo = rtrim($moveTo, '/') . '/';
		if (!file_exists($moveTo)) {
			new Alert('debug', '<code>Upload::File()</code> : Le répertoire de destination <code>' . $moveTo . '</code> n\'existe pas !');
			return false;
		}
		// La taille du fichier est-elle dans les clous ?
		if ($file['size'] > ($maxSize * 1024)) {
			new Alert('error', 'Le fichier fait ' . Sanitize::readableFileSize($file['size']) . ' alors que la taille maximum autorisée est de ' . $maxSize . 'ko !');
			return false;
		}
		// L'extension du fichier est-elle autorisée ?
		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		if (!empty($allowedExtensions)) {
			if (!is_array($allowedExtensions)) {
				new Alert('debug', '<code>Upload::File()</code> : <code>$allowedExtensions</code> n\'est pas un tableau ! ' . Get::varDump($allowedExtensions));
				return false;
			}
			if (!in_array($extension, $allowedExtensions)) {
				new Alert('error', 'Les fichiers <code>.' . $extension . '</code> ne sont pas autorisés !');
				return false;
			}
		}
		// On nettoie le nom du fichier
		$name = Sanitize::sanitizeFilename($file['tmp_name']) . '.' . $extension;
		// On traite les arguments
		if (!empty($args)) {
			// Remplacement du nom du fichier
			if (isset($args['name'])) {
				if (empty($args['name'])) {
					new Alert('debug', '<code>Upload::File()</code> : <code>$args[\'name\']</code> est vide ! ');
					return false;
				}
				// On nettoie le nom du fichier demandé
				$name = Sanitize::sanitizeFilename($args['name']);
				$nameExt = pathinfo($name, PATHINFO_EXTENSION);
				if (empty($nameExt) or strlen($nameExt) > 4) $name .= '.' . $extension;
			}
			// Redimensionnement d'image : largeur et/ou hauteur fixe
			if (isset($args['resize']) and in_array(pathinfo($file['name'])['extension'], unserialize(ALLOWED_IMAGES_EXT))) {
				$resizeArgs = $args['resize'];
				// C'est une image !
				$img = new Components\SimpleImage($file['tmp_name']);
				if (isset ($resizeArgs['width']) or isset($resizeArgs['height'])) {
					// On  affecte une valeur 0 à 'width' ou 'height' s'ils ne sont pas remplis
					$width = (isset($resizeArgs['width']) and !empty($resizeArgs['width'])) ? (int)$resizeArgs['width'] : 0;
					$height = (isset($resizeArgs['height']) and !empty($resizeArgs['height'])) ? (int)$resizeArgs['height'] : 0;
					try {
						if ($width == 0) {
							if ($args['toSquare']) {
								$img->best_fit($height, $height);
							} else {
								$img->fit_to_height($height);
							}
						} elseif ($height == 0) {
							if ($args['toSquare']) {
								$img->best_fit($width, $width);
							} else {
								$img->fit_to_width($width);
							}
						} else {
							$img->best_fit($width, $height);
						}
					} catch(Exception $e) {
						new Alert('error', 'Erreur de redimensionnement : ' . $e->getMessage());
						return false;
					}
				} else {
					new Alert('debug', '<code>Upload::File()</code> : Il n\'y a ni largeur ni hauteur définie pour le redimensionnement !');
				}
			}
		}
		// Sauvegarde du fichier
		if (isset ($img)) {
			try {
				$img->save($moveTo . $name);
			} catch(Exception $e) {
				new Alert('error', 'impossible de sauvegarder l\'image : ' . $e->getMessage());
				return false;
			}
		} else {
			if (!move_uploaded_file($file['tmp_name'], $moveTo . $name)) {
				new Alert('error', 'impossible de sauvegarder le fichier !');
				return false;
			}
		}
		return $name;
	}
}