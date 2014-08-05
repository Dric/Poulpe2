<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 01/08/14
 * Time: 09:26
 */

namespace Modules\CHGS;


use Components\Help;
use Components\Item;
use Front;
use Modules\Module;
use Modules\ModulesManagement;

class CHGS extends Module{
	protected $name = 'Petits Outils Informatique';
	protected $title = 'Des petits outils pour faciliter la vie des informaticiens';



	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('portail', 'Portail', 'http://glpi', 'Retour au Petit Portail Informatique'), 97);
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
	 * Affichage principal
	 */
	public function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1><?php echo $this->name; ?>  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p><?php echo $this->title; ?></p>
				<h2>Notice d'utilisation</h2>
				<ul>
					<li>
						Si vous avez un doute quand à ce qu'il convient de faire, n'hésitez pas à passer la souris sur les petits symboles d'information :  <?php Help::iconHelp('Et si vraiment vous êtes paumé(e), demandez à Cédric'); ?><br />
					</li>
					<li>Certains modules peuvent être paramétrés pour vos besoins. Il vous suffit pour cela de cliquer sur le bouton <a href="#" class="btn btn-default btn-xs" title="Inutile de cliquer sur ce bouton, il ne vous emmènera nulle part..."><span class="fa fa-cog"></span> Paramètres</a> qui apparaît à côté du titre du module.</li>
					<li>Vous pouvez modifier certaines informations de votre <a href=".?module=profil">profil</a> en cliquant sur votre avatar en haut du menu.</li>
					<li>Ce produit ne convient pas aux fosses septiques.</li>
					<li>Vous pouvez retourner sur le <a href="http://glpi" title="L'Intranet est une légende urbaine de la fin du XXè siècle. Aucune donnée fiable ne permet aujourd'hui d'affirmer qu'une telle chose existe.">Petit Portail Informatique</a> en cliquant sur <code>Portail</code> dans le menu à gauche, et vous pouvez vous déconnecter en cliquant sur <code class="tooltip-bottom" title="Captain Obvious à la rescousse !">Déconnexion</code>.</li>
					<li>Visuel non contractuel.</li>
				</ul>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/
} 