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
use Forms\Fields\Button;
use Forms\Fields\CheckboxList;
use Forms\Fields\RadioList;
use Forms\Fields\Select;
use Forms\Fields\String;
use Forms\Form;
use Forms\Pattern;
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
		$module = explode('\\', get_class());
		Front::$mainMenu->add(new Item('ModuleCreator', 'Aide à la création de modules', Front::getModuleUrl().end($module), 'Pour faciliter le développement de modules'));
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
				<img class="pull-right" alt="MonsieurPoulpe !" src="<?php echo Front::getBaseUrl(); ?>/img/poulpe2-logo-145x200.png">
				<p>Ce module vous donnera accès aux ressources disponibles pour vous aider dans la création de modules pour Poulpe2.</p>
				<h3>Documentations</h3>
				<ul>
					<li>
						<a href="<?php echo Front::getBaseUrl(); ?>/Docs/Poulpe2" target="_blank">Documentation du framework</a>
					</li>
					<li><a href="<?php echo Front::getBaseUrl(); ?>/Docs/Code" target="_blank">Documentation du code de Poulpe2</a></li>
					<li>
						<a href="<?php echo Front::getBaseUrl(); ?>/Docs/Bootstrap" target="_blank">Documentation de Bootstrap</a>
						<ul>
							<li><a href="<?php echo Front::getBaseUrl(); ?>/Docs/Bootstrap/css.html#forms" target="_blank">Formulaires Bootstrap</a></li>
							<li><a href="http://fortawesome.github.io/Font-Awesome/icons/" target="_blank">Icônes de Font Awesome</a></li>
							<li><a href="<?php echo Front::getBaseUrl(); ?>/Docs/Bootstrap/css.html#helper-classes" target="_blank">Helpers Bootstrap</a></li>
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
				<p>Ex : <code><?php echo $this->buildArgsURL(array('debug' => true)); ?></code></p>
				<?php
				//var_dump(crack_check('poney'));
				?>
			</div>
		</div>
		<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Générateur de code pour créer des formulaires
	 */
	protected function moduleFormCreator(){
		$form = new Form('formSettings');
		$form->addField(new String('formName', null, 'Nom du formulaire', null, 'Ce nom doit respecter au maximum la nomenclature de Perl : en minuscules, avec une majuscule pour séparer les mots mais pas au début, pas de séparateur, pas de caractères spéciaux ou accents. ex : formName, nomDeFormulaire', new Pattern('text', true, 1, 0, null, null, '^\w*$'), true));
		$form->addField(new RadioList('formMethod', 'post', 'Méthode d\'envoi du formulaire', 'Envoi en \'post\' ou en \'get\'', false, null, null, false, array('post' => 'POST', 'get' => 'GET')));
		$choices = array(
			'form-inline' => 'Formulaire sur une ligne'
		);
		$form->addField(new CheckboxList('formOptions', null, 'Options du formulaire', 'Options applicables au formulaire', false, null, null, false, $choices));
		$form->addField(new Button('action', 'showCode', 'Afficher le code'));
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Générateur de code pour créer des formulaires  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<?php $form->display(); ?>
			</div>
		</div>
		<?php
	}

	protected function showCode(){

	}

	/**
	 * Affiche le PHPInfo du serveur
	 */
	protected function modulePhpInfo(){
		// PHPInfo possède son propre style CSS, si on veut l'intégrer il faut capturer ce qu'il produit pour le réinjecter proprement
		ob_start();
		phpinfo();
		$phpInfo = ob_get_contents();
		ob_end_clean();
		$phpInfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$phpInfo);
		$phpInfo = preg_replace('/<table/i', '<table class="table table-bordered table-striped"', $phpInfo);
		preg_match_all('/<a name="(.*)".*>(.*)<\/a>/i', $phpInfo, $matches);
		?>
		<style>
			td.e{font-weight:bold}
			td.v{word-break:break-all}
		</style>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>PHPInfo du serveur  <?php $this->manageModuleButtons(); ?></h1>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-8 col-md-offset-1">
				<small><?php echo $phpInfo; ?></small>
			</div>
			<div class="col-md-2">
				<h2>Sommaire</h2>
				<ul>
					<?php
					foreach ($matches[1] as $key => $anchor){
						?><li><a href="#<?php echo $anchor; ?>"><?php echo $matches[2][$key]; ?></a></li><?php
					}
					?>
				</ul>
			</div>
		</div>
		<?php
	}
}