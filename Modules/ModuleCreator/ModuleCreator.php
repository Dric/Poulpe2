<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 04/08/14
 * Time: 14:25
 */

namespace Modules\ModuleCreator;


use Components\Item;
use Components\Menu;
use Front;
use Modules\Module;
use Modules\ModulesManagement;

class ModuleCreator extends Module{
	protected $name = 'Aide à la création de modules';
	protected $title = 'Pour faciliter le développement de modules';



	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('ModuleCreator', 'Aide à la création de modules', MODULE_URL.end(explode('\\', get_class())), 'Pour faciliter le développement de modules'));
	}

	/**
	 * Installe le module
	 */
	public function install(){
		// Définition des ACL par défaut pour ce module
		$defaultACL = array(
			'type'  => 'access',
			'value' => true
		);
		return ModulesManagement::installModule($this, $defaultACL);
	}

	/**
	 * Gère le menu du module
	 */
	protected function moduleMenu(){
		$menu = new Menu($this->name, 'Aide à la création de modules', '', '', '');
		$menu->add(new Item('moduleCreator', 'Aide', $this->url, '', 'book'), 2);
		$menu->add(new Item('formCreator', 'Création de formulaires', $this->url.'&page=formCreator', 'Création de formulaire', 'edit'));
		$menu->add(new Item('phpInfo', 'PHPInfo du serveur', $this->url.'&page=phpInfo', 'PHPInfo du serveur', 'plug'));
		Front::setSecondaryMenus($menu);
	}

	/**
	 * Affichage principal
	 */
	public function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p><?php echo $this->title; ?>.</p>
				<img class="pull-right" alt="MonsieurPoulpe !" src="./img/poulpe2-logo-145x200.png">
				<p>Ce module vous donnera accès aux ressources disponibles pour vous aider dans la création de modules pour Poulpe2.</p>
				<h3>Documentations</h3>
				<ul>
					<li>
						<a href="./Docs/Poulpe2" target="_blank">Documentation du framework</a>
					</li>
					<li><a href="./Docs/Code" target="_blank">Documentation du code de Poulpe2</a></li>
					<li>
						<a href="./Docs/Bootstrap" target="_blank">Documentation de Bootstrap</a>
						<ul>
							<li><a href="./Docs/Bootstrap/css.html#forms" target="_blank">Formulaires Bootstrap</a></li>
							<li><a href="http://fortawesome.github.io/Font-Awesome/icons/" target="_blank">Icônes de Font Awesome</a></li>
							<li><a href="./Docs/Bootstrap/css.html#helper-classes" target="_blank">Helpers Bootstrap</a></li>
						</ul>
					</li>
				</ul>
				<h3>Ressources</h3>
				<ul>
					<li><a href="http://github.com/Dric/Poulpe2" target="_blank">Dépôt GitHub de poulpe2</a></li>
					<li><a href="http://realfavicongenerator.net/" target="_blank">Génération de favicons avancées</a></li>
					<li><a href="http://regex101.com/" target="_blank">Aide pour les expressions régulières</a></li>
				</ul>
				<h3>Commandes git utiles</h3>
				Ces commandes s'emploient dans le répertoire racine de Poulpe2.
				<ul>
					<li>Mettre à jour Poulpe2 : <code>git pull</code></li>
					<li>Enlever les modifications locales sur les fichiers avant de mettre à jour : <code>git stash</code></li>
				</ul>
				<h3>Mode DEBUG</h3>
				<p>Pour faciliter le debug des modules, vous pouvez passer la constante <code>DEBUG</code> à <code>true</code> pour activer globalement le mode DEBUG, ou bien ponctuellement avec une variable nommé <code>debug</code> en <code>POST</code>, <code>GET</code> ou via un cookie.</p>
				<p>Ex : <code><?php echo $this->url; ?>&debug</code></p>
				<?php
				//var_dump(crack_check('poney'));
				?>
			</div>
		</div>
		<?php
	}

	protected function modulePhpInfo(){
		Front::setJsFooter('<script src="Modules/ModuleCreator/ModuleCreator.js"></script>');
		ob_start();
		phpinfo();
		$pinfo = ob_get_contents();
		ob_end_clean();

		$pinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);
		?>
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="page-header">
					<h1>PHPInfo du serveur  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<small><?php echo $pinfo; ?></small>
			</div>
		</div>
		<?php
	}
}