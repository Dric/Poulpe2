<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 22/05/14
 * Time: 09:00
 */

namespace Modules\BandesTina;

use Components\Item;
use FileSystem\Fs;
use Forms\Fields\Password;
use Forms\Fields\String;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Forms\Field;
use Users\ACL;

/**
 * Class BandesTina
 *
 * * Gestion de l'externalisation des bandes du robot de sauvegarde.
 * -----------------------------
 *
 * Ce module va lire le contenu de deux fichiers texte (to_import.txt et to_export.txt) sur le serveur tina.
 * Ces deux fichiers sont créés par un script qui lance une ligne de commande tina pour récupérer les code-barres des bandes à enlever et à remettre dans le robot de sauvegarde.
 *
 * @see <http://srv-intratest/git/gestion_bandes_tina.git/>
 *
 * @package Modules\BandesTina
 */
class BandesTina extends Module{
	protected $name = 'Bandes Tina';
	protected $title = 'Gestion de l\'externalisation des bandes du robot de sauvegarde';

	/**
	 * Expression régulière permettant de trouver les code-barres des cartouches.
	 *
	 * Comme cette variable est utilisée également par la classe Cart, on la passe en public static
	 *
	 * On cherche 3 valeurs :
	 *  - Le code-barre complet de la bande
	 *  - L'identifiant sur 2 chiffres de la bande
	 *  - Le type de sauvegarde inscrit sur la bande (vvb, tib, tmb, etc.)
	 *
	 * Une expression régulière est encadrée par des caractères (ici '/'), le petit 'i' spécifiant une recherche insensible à la casse et le '^' qu'on commence la recherche en début de ligne.
	 * Chaque expression entre parenthèse retourne une valeur si elle se vérifie.
	 * Schéma : (code-barre complet : PE + 2 chiffres + (identifiant en 2 chiffres) + L4)   (type de sauvegarde en 3 lettres) + _ + 7 chiffres
	 *
	 * @var string
	 */
	public static $regex = "/^(PE\d{2}(\d{2})L4)   ([a-z]{3})_\d{7}/i";

	/**
	 * Tableau des cartouches à enlever du robot
	 * @var array
	 */
	protected $cartsOut = array();

	/**
	 * Tableau des cartouches à remettre dans le robot
	 * @var array
	 */
	protected $cartsIn = array();

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('bandesTina', 'Bandes de sauvegarde', MODULE_URL.end($module), 'Gestion de l\'externalisation des bandes du robot de sauvegarde', null, null));
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
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		$this->settings['scriptsPath']  = new String('scriptsPath', '\\\\srv-tina\c$\scripts\gestion_cartouches', 'Chemin des scripts', '\\\\srv-tina\c$\scripts\gestion_cartouches', null, new Pattern('string', true), true);
		$this->settings['scriptName']   = new String('scriptName', 'liste_cartouches.bat', 'Nom du script qui récupère la liste des bandes', 'liste_cartouches.bat', null, new Pattern('string', true), true);
		$this->settings['tinaLibrary']  = new String('tinaLibrary', 'NEO200S', 'Nom du robot', 'NEO200S', null, new Pattern('string', true), true);
		$this->settings['tinaServerAdminLogin'] = new String('tinaServerAdminLogin', 'administrateur', 'Nom d\'administrateur du serveur', null, 'Saisissez un nom d\'administrateur pouvant se connecter sur le serveur et sur Tina', new Pattern('string', true), true);
		$this->settings['tinaServerAdminPwd'] = new Password('tinaServerAdminPwd', null, 'Mot de passe du compte administrateur', null, null, new Pattern('password', true), true);
	}

	/**
	 * Affichage principal
	 */
	public function mainDisplay(){
		$this->refreshCartsList();
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p><?php echo $this->title; ?>.</p>
				<div class="alert alert-info">Note : vous pouvez passer par l'interface de Tina pour plus de visibilité sur cette opération.</div>
				<p>On le sait, Tina est loin d'être une merveille d'ergonomie. Pour savoir quelles bandes enlever et remettre dans le robot, cette petite interface va vous simplifier la vie.</p>
				<h2>Etape 1 : Sortir et remettre les bandes dans le robot</h2>
				<ul>
					<li>Le nombre de cartouches enlevées doit être identique au nombre de cartouches remises.</li>
					<li>Ce qui veut dire qu'il vous faudra mettre des bandes vierges dans le robot si le nombre de cartouches enlevées est supérieur au nombre de cartouches remises.</li>
					<li>Il se peut aussi que vous en ayez plus à remettre qu'à enlever. Dans ce cas, repérez les bandes en <i>spare</i> ou qui ne sont pas affectées pour les enlever. il vous faudra malheureusement passer par Tina pour ce faire.</li>
					<li>Il doit rester au moins deux emplacements vides dans le robot.</li>
				</ul>
				<!-- Liste des cartouches à enlever et remettre dans le robot-->
				<div class="row">
					<div class="col-md-10 col-md-offset-1">
						<div class="row" id="carts-list">
							<?php
							$this->displayCarts('Out');
							$this->displayCarts('In');
							?>
						</div>
					</div>
				</div>
				<h2>Etape 2 : réinitialiser les code-barres</h2>
				<p>Pour que Tina prenne en compte les bandes que vous avez enlevées et remises, il faut refaire une lecture des code-barres de toutes les bandes.<br />La logique voudrait que Tina et le robot communiquent sur le sujet, puisque le robot fait justement cette vérification quand on manipule les cartouches, mais la logique en informatique étant un concept assez flou, cette info n'est pas remontée jusqu'à Tina.</p>
				<p>
					Si vous avez déplacé des bandes dans le lecteur, il vous faudra lancer deux fois la réinitialisation des bandes pour que tout soit pris en compte.<br>
					La cartouche de nettoyage ne doit pas être déplacée, car Tina n'ira la chercher que dans le <code>slot 0</code>.
				</p>
				<div class="text-center">
					<form method="post">
						<button id="reinit-barcodes" class="btn btn-lg btn-warning" type="submit" name="action" value="reinitBarcodes" <?php if (!ACL::canModify('module', $this->id)) echo 'disabled'; ?>>Réinitialiser les code-barres</button>
						<?php if (!ACL::canModify('module', $this->id)) { ?>
						<span class="help-block">Vous n'avez pas les droits pour lancer cette commande.</span>
						<?php } ?>
					</form>
				</div>
				<br /><br />
				<!-- Affichage du message de résultat de la réinitialisation des code-barres -->
				<div id="reinit-result" class="text-center"></div>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Exécute le script qui envoie la liste des bandes à sortir/remettre dans les fichiers adéquats
	 *
	 * Le script ne sera exécuté que si les fichiers ont été mis à jour il y a plus de 24h ou que le paramètre $force est à true.
	 * @param bool $force Forcer le rafraîchissement des fichiers
	 *
	 * @return bool
	 */
	protected function refreshCartsList($force = false){
		if (!$force){
			//On monte le partage réseau
			$module = explode('\\', get_class());
			if (!$share = new Fs($this->settings['scriptsPath']->getValue(), null, end($module))){
				new Alert('error', 'Impossible d\'accéder aux fichiers du serveur !');
				return false;
			}
			$meta['In'] 	= $share->getFileMeta('to_import.txt', 'dateModified');
			$meta['Out'] 	= $share->getFileMeta('to_export.txt', 'dateModified');
			if ($meta['In'] == false or $meta['Out'] == false){
				new Alert('error', 'Les listes de bandes ne sont pas accessibles !');
				return false;
			}
			if (($meta['In']->dateModified + 24*3600) < time() or ($meta['Out']->dateModified + 24*3600) < time()){
				// Si les fichiers ont été modifiés il y a plus de 24h, on les recrée.
				$force = true;
			}
		}
		if ($force and ACL::canModify('module', $this->id)){
			// Possibilité d'optimisation : Il y a probablement plus efficace pour récupérer le nom du serveur et le chemin du partage...
			$path = mb_substr($this->settings['scriptsPath']->getValue(), 2);
			$tab = explode('\\', $path);
			$server = $tab[0];
			unset($tab[0]);
			$filePath = str_replace('$', ':', implode('\\', $tab));

			// On recrée les fichiers
			passthru('cat </dev/null | winexe --interactive=0 -U '.$this->settings['tinaServerAdminLogin']->getValue().'%'.$this->settings['tinaServerAdminPwd']->getValue().' //'.$server.' "cmd /C '.$filePath.'/'.$this->settings['scriptName']->getValue().'"', $retCode);
			if ($retCode != 0){
				new Alert('error', 'Impossible d\'exécuter la commande de rafraîchissement des listes de bandes. Le code d\'erreur retourné est <code>'.$retCode.'</code>');
				return false;
			}
		}
		if (empty($this->cartsIn) or $force){
			//On met à jour la liste des bandes
			$this->populateCarts('In');
			$this->populateCarts('Out');
		}
		return true;
	}

	/**
	 * Récupération des bandes à enlever ou à sortir
	 * @param string $IO 'In' ou 'Out' pour respectivement traiter les cartouches à remettre ou à enlever
	 *
	 * @return bool
	 */
	protected function populateCarts($IO){
		// On vérifie que $IO est bien correct à ce qu'on attend.
		if (!in_array($IO, array('In', 'Out'))){
			new Alert('debug', '<code>BandesTina->populateCarts()</code> : <code>'.$IO.'</code> est incorrect');
			return false;
		}
		$files = array(
			'In'	=> 'to_import.txt',
			'Out'	=> 'to_export.txt'
		);
		//On monte le partage réseau
		$module = explode('\\', get_class());
		if (!$share = new Fs($this->settings['scriptsPath']->getValue(), null, end($module))){
			return false;
		}
		$file = $share->readFile($files[$IO]);
		if ($file === false) {
			return false;
		}
		foreach ($file as $line){
			preg_match(self::$regex, $line, $barcode);
			if (isset($barcode[0])){
				$this->{'carts'.$IO}[] = new Cart($barcode[0]);
			}
		}
		//On trie par id de cartouches
		usort($this->{'carts'.$IO}, function($a, $b){
			return strcmp($a->GetId(), $b->GetId());
		});
		return true;
	}

	/**
	 * Affiche les bandes à remettre ou à enlever
	 * @param string $IO ('In' pour les bandes à remettre, 'Out' pour celles à enlever)
	 *
	 * @return bool
	 */
	protected function displayCarts($IO){
		// On vérifie que $IO est bien correct à ce qu'on attend.
		if (!in_array($IO, array('In', 'Out'))){
			new Alert('debug', '<code>BandesTina->populateCarts()</code> : <code>'.$IO.'</code> est incorrect');
			return false;
		}
		$count = count($this->{'carts'.$IO});
		?>
		<div class="col-md-6">
			<h3><?php echo $count; ?> Cartouche<?php if ($count != 1) echo 's'; ?> <?php echo ($IO == 'In') ? 'à remettre' : 'à enlever'; ?></h3>
			<ul>
				<?php
				/**
				 * @var Cart $cart
				 */
				foreach ($this->{'carts'.$IO} as $cart){
					?>
					<li><strong><?php echo $cart->getId(); ?></strong> (<?php echo $cart->getType().' '.$cart->getBarcode(); ?>)</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
		return true;
	}

	/**
	 * Réinitialisation des code-barres
	 * @return bool
	 */
	protected function reinitBarcodes(){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		// Possibilité d'optimisation : Il y a probablement plus efficace pour récupérer le nom du serveur et le chemin du partage...
		$path = mb_substr($this->settings['scriptsPath']->getValue(), 2);
		$tab = explode('\\', $path);
		$server = $tab[0];
		unset($tab[0]);
		$cmd = 'cat </dev/null | winexe --interactive=0 -U '.$this->settings['tinaServerAdminLogin']->getValue().'%'.$this->settings['tinaServerAdminPwd']->getValue().' //'.$server.' "cmd /C C:\Progra~1\Atempo\tina\Bin\tina_library_control -library '.$this->settings['tinaLibrary']->getValue().' -identity '.$this->settings['tinaServerAdminLogin']->getValue().':'.$this->settings['tinaServerAdminPwd']->getValue().' -reinit_barcode"';
		$ret = shell_exec($cmd);

		$module = explode('\\', get_class());
		$share = new Fs('\\\\'.$server.'\c$\program files\atempo\tina\adm', null, end($module));

		// On récupère les 5 dernières lignes du fichier d'événements Tina
		$file = $share->tailFile('event.txt', 5);
		if ($file !== false){
			$isDone = $isLaunched = false;
			foreach ($file as $line){
				/*
				 * Exemple de ligne retournée :
				 * 8|29|cfg_end_log_command|10|1|3|1401434943|1401434943|28136|tina_library_control|SRV-TINA|~|SRV-TINA\\Administrateur|~|srvtina_tina|SRV-TINA|End of "C:\\Progra\~1\\Atempo\\tina\\Bin\\tina_library_control" avec les paramÃ¨tres "-library NEO200S -identity *** -reinit_barcode"|0|~|~|~|~|~|~|
				 */
				$tab = explode('|', $line);
				if ($tab[1] == '29' and $tab[2] == 'cfg_log_start_log_command' and $tab[9] == 'tina_library_control' and strpos($tab[16],'Starting') !== false){
					// La commande s'est lancée
					$isLaunched = true;
				}elseif ($tab[1] == '29' and $tab[2] == 'cfg_end_log_command' and $tab[9] == 'tina_library_control' and strpos($tab[16],'End of') !== false){
					// La commande s'est terminée !
					$isDone = true;
				}
			}
			if ($isDone){
				new Alert('success', 'Les code-barres ont été réinitialisés !');
			}elseif($isLaunched){
				new Alert('warning', 'La réinitialisation des code-barres a débuté mais elle n\'est pas encore terminée. Veuillez vérifier sur le gestionnaire d\'événements Tina les événements <code>15940 tina_library_control</code>');
			}else{
				new Alert('error', 'La réinitialisation des code-barres ne s\'est pas lancée. Veuillez passer par Tina pour la lancer !');
				return false;
			}
			$this->refreshCartsList(true);
			return true;
		}else{
			new Alert('warning', 'Impossible de vérifier que la commande de réinitialisation s\'est bien déroulée !');
			$this->refreshCartsList(true);
			return true;
		}
	}

}