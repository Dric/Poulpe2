<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 03/06/14
 * Time: 15:28
 */

namespace Modules\CitrixDesktops;


use Components\Item;
use Front;
use Modules\Module;
use Modules\ModulesManagement;

class CitrixDesktops extends Module {
	protected $name = 'Bureaux Citrix';
	protected $title = 'Permet d\'ouvrir un bureau Citrix avec d\'autres identifiants que le PNAgent';

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('CitrixDesktops', 'Bureaux Citrix', MODULE_URL.end($module), 'Permet d\'ouvrir un bureau Citrix avec d\'autres identifiants que le PNAgent', null, null));
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
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Bureaux Citrix <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					<?php echo $this->title; ?>.
				</p>
				<p>
					Vous pouvez avoir besoin d'ouvrir un bureau citrix sous un autre identifiant que le vôtre, sans pour autant fermer votre propre session.<br />
					Les Bureaux Citrix sont des fichiers ica qui fonctionnent un peu comme l'ancien client ICA (le Program Neighborhood), et qui permettent d'ouvrir une session avec d'autres identifiants que celui du PNAgent.
				</p>
				<p>
					En cliquant sur le lien, vous allez lancer automatiquement le bureau citrix demandé. Vous n'aurez plus qu'à saisir les identifiants désirés.
				</p>
				<h3>Liste des bureaux Citrix</h3>
				<ul>
					<li><a href="<?php echo str_replace(getcwd().'/', '', dirname(__FILE__)); ?>/bureaux/Bureau.ica" title="Bureau de base" class="tooltip-right" target="_blank">Bureau</a></li>
					<li><a href="<?php echo str_replace(getcwd().'/', '', dirname(__FILE__)); ?>/bureaux/BureauTest.ica" title="Bureau de Test" class="tooltip-right" target="_blank">Bureau Test</a></li>
					<li><a href="<?php echo str_replace(getcwd().'/', '', dirname(__FILE__)); ?>/bureaux/BureauXen.ica" title="Bureau Xen. Utilisé pour se connecter sur un serveur précis. Le serveur de connexion est à modifier dans la console Citrix." class="tooltip-right" target="_blank">Bureau Xen</a></li>
				</ul>
				<p>
					Si vous êtes sous Firefox, vous pouvez ensuite fermer l'onglet ou la fenêtre qui vient de s'ouvrir, elle n'est plus d'aucune utilité.<br />
					Si vous êtes sous Google Chrome, il faudra faire "Ouvrir" en bas à gauche pour lancer le fichier ICA.
				</p>
			</div>
		</div>
	<?php
	}
} 