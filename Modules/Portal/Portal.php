<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 01/07/14
 * Time: 10:12
 */

namespace Modules\Portal;


use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Forms\Field;
use Forms\Form;
use Users\ACL;

class Portal extends Module {
	protected $name = 'Portail';
	protected $title = 'Petit Portail Informatique';
	protected $allowUsersSettings = true;
	protected $groups = array();
	public static $badgesTypes = array(
		'danger'  => 'Rouge',
	  'info'    => 'Bleu',
	  'warning' => 'Jaune',
	  'success' => 'Vert'
	);

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
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		/**
		 * @see Db\Db->createTable pour plus de détails sur la création d'une table.
		 */
		$this->dbTables['widgets_groups'] = array(
			'name'        => 'widgets_groups',
			'desc'        => 'Groupes de widgets',
			'fields'      => array(
				'id'    => array(
					'type'          => 'int',
					'length'        => 6,
					'null'          => false,
					'show'          => false
				),
				'name' => array(
					'comment' => 'Nom du groupe',
					'type'    => 'string',
					'length'  => 100,
					'null'    => false
				),
				'enabled'  => array(
					'comment' => 'Etat (activé/désactivé)',
					'type'    => 'bool',
					'default' => 1,
					'null'    => false
				),
				'desc'  => array(
					'comment' => 'Description courte',
					'type'    => 'string',
					'length'  => 265,
					'null'    => true
				),
				'badgeLabel'  => array(
					'comment' => 'Titre du badge optionnel',
					'type'    => 'string',
					'length'  => 50,
					'null'    => true
				),
				'badgeType'  => array(
					'comment' => 'Type du badge optionnel (danger, info, success, warning)',
					'type'    => 'string',
					'length'  => 7,
					'null'    => true
				),
				'shared'  => array(
					'comment' => 'groupe public',
					'type'    => 'bool',
					'default' => 1,
					'null'    => false
				),
				'author'  => array(
					'comment' => 'ID de l\'auteur du groupe',
					'type'    => 'smallint',
					'length'  => 6,
					'null'    => false,
					'show'    => false
				)
			),
			'primaryKey'  => 'id',
			'indexKey'    => array('author', 'shared', 'enabled'),
			'onDuplicateKeyUpdate' => array('name', 'enabled', 'desc', 'badgeLabel', 'badgeType', 'shared'),
			'foreignKey'  => array(
				'field'         => 'author',
				'foreignTable'  => 'users',
				'foreignField'  => 'id',
				'onDelete'      => 'NO ACTION',
				'onUpdate'      => 'CASCADE'
			)
		);
		$this->dbTables['widgets'] = array(
			'name'        => 'widgets',
			'desc'        => 'Tuiles',
			'fields'      => array(
				'id'    => array(
					'show'          => false,
					'type'          => 'int',
					'length'        => 11,
					'null'          => false,
					'autoIncrement' => true,
				),
				'name' => array(
					'comment' => 'Nom de la tuile',
					'type'    => 'string',
					'length'  => 100,
					'null'    => false
				),
				'enabled'  => array(
					'comment' => 'Etat (activé/désactivé)',
					'type'    => 'bool',
					'default' => 1,
					'null'    => false
				),
				'desc'  => array(
					'comment' => 'Description courte',
					'type'    => 'string',
					'length'  => 265,
					'null'    => true
				),
				'link'  => array(
					'comment' => 'Lien de la tuile',
					'type'    => 'string',
					'length'  => 150,
					'null'    => false
				),
				'badgeLabel'  => array(
					'comment' => 'Titre du badge optionnel',
					'type'    => 'string',
					'length'  => 50,
					'null'    => true
				),
				'badgeType'  => array(
					'comment' => 'Type du badge optionnel (danger, info, success, warning)',
					'type'    => 'string',
					'length'  => 7,
					'null'    => true
				),
				'shared'  => array(
					'comment' => 'Tuile publique',
					'type'    => 'bool',
					'default' => 1,
					'null'    => false
				),
				'author'  => array(
					'comment' => 'ID de l\'auteur de la tuile',
					'type'    => 'smallint',
					'length'  => 6,
					'null'    => false
				),
				'group'   => array(
					'comment' => 'ID du groupe de widgets',
					'type'    => 'int',
					'length'  => 6,
					'null'    => true
				)
			),
			'primaryKey'  => 'id',
			'indexKey'    => array('author', 'shared', 'enabled', 'group'),
			'onDuplicateKeyUpdate' => array('name', 'enabled', 'desc', 'link', 'badgeLabel', 'badgeType', 'shared'),
			'foreignKey'  => array(
				array(
					'field'         => 'author',
					'foreignTable'  => 'users',
					'foreignField'  => 'id',
					'onDelete'      => 'NO ACTION',
					'onUpdate'      => 'CASCADE'
				),
				array(
					'field'         => 'group',
					'foreignTable'  => 'widgets_groups',
					'foreignField'  => 'id',
					'onDelete'      => 'CASCADE',
					'onUpdate'      => 'CASCADE'
				)
			)
		);
		$this->dbTables['users_widgets'] = array(
			'name'        => 'users_widgets',
			'desc'        => 'Widgets des utilisateurs',
			'fields'      => array(
				'widget'    => array(
					'type'          => 'int',
					'length'        => 11,
					'null'          => false
				),
				'user'  => array(
					'comment' => 'ID de l\'utilisateur',
					'type'    => 'smallint',
					'length'  => 6,
					'null'    => false
				),
				'enabled'  => array(
					'comment' => 'Etat (activé/désactivé)',
					'type'    => 'bool',
					'default' => 1,
					'null'    => false
				),
				'group'   => array(
					'comment' => 'ID du groupe de widgets',
					'type'    => 'int',
					'length'  => 6,
					'null'    => true
				)
			),
			'indexKey'    => array('widget', 'user', 'enabled', 'group'),
			'uniqueMultiKey'  => array('widget', 'user'),
			'onDuplicateKeyUpdate' => array('enabled', 'group'),
			'foreignKey'  => array(
				array(
					'field'         => 'user',
					'foreignTable'  => 'users',
					'foreignField'  => 'id',
					'onDelete'      => 'NO ACTION',
					'onUpdate'      => 'CASCADE'
				),
				array(
					'field'         => 'widget',
					'foreignTable'  => 'widgets',
					'foreignField'  => 'id',
					'onDelete'      => 'CASCADE',
					'onUpdate'      => 'CASCADE'
				),
				array(
					'field'         => 'group',
					'foreignTable'  => 'widgets_groups',
					'foreignField'  => 'id',
					'onDelete'      => 'CASCADE',
					'onUpdate'      => 'CASCADE'
				)
			)
		);
		$this->dbTables['users_widgets_groups'] = array(
			'name'        => 'users_widgets_groups',
			'desc'        => 'Groupes de Widgets des utilisateurs',
			'fields'      => array(
				'group'   => array(
					'comment' => 'ID du groupe de widgets',
					'type'    => 'int',
					'length'  => 6,
					'null'    => true
				),
				'user'  => array(
					'comment' => 'ID de l\'utilisateur',
					'type'    => 'smallint',
					'length'  => 6,
					'null'    => false
				),
				'enabled'  => array(
					'comment' => 'Etat (activé/désactivé)',
					'type'    => 'bool',
					'default' => 1,
					'null'    => false
				)
			),
			'indexKey'    => array('user', 'enabled', 'group'),
			'uniqueMultiKey'  => array('group', 'user'),
			'onDuplicateKeyUpdate' => array('enabled'),
			'foreignKey'  => array(
				array(
					'field'         => 'user',
					'foreignTable'  => 'users',
					'foreignField'  => 'id',
					'onDelete'      => 'NO ACTION',
					'onUpdate'      => 'CASCADE'
				),
				array(
					'field'         => 'group',
					'foreignTable'  => 'widgets_groups',
					'foreignField'  => 'id',
					'onDelete'      => 'CASCADE',
					'onUpdate'      => 'CASCADE'
				)
			)
		);
		$data = array(
		  'choices'   => array(
			  'auto'    => 'Automatique',
			  'bubbles' => 'Bulles',
		    'list'    => 'Liste'
		  )
		);
		$switchArray = array(
			'switch'        => true,
			'size'          => 'small',
			'labelPosition' => 'right'
		);
		$this->settings['grid']  = new Field('grid', 'select', 'user', 'auto', 'Affichage des liens', null, $data, null, null, null, true);
		$this->settings['sharedByDefault'] = new Field('sharedByDefault', 'bool', 'user', true, 'Rendre les liens publiques par défaut', null, $switchArray);
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
				<div class="row">
					<div class="col-md-6">
						<?php $this->editWidgetForm(); ?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	protected function getGroups(){
		if (empty($this->groups)){
			global $db;
			$dbGroups = $db->get('widgets_groups');
			foreach ($dbGroups as $dbGroup){
				$this->groups[$dbGroup->name] = new Group($dbGroup->name, $dbGroup->id, $dbGroup->shared, $dbGroup->enabled, $dbGroup->desc, $dbGroup->id, $dbGroup->badgeLabel, $dbGroup->badgeType);
			}
		}
		return $this->groups;
	}
	/**
	 * Affiche le formulaire d'édition ou ajout de nouveau widget
	 * @param Widget $widget Objet Widget à éditer
	 */
	protected function editWidgetForm(Widget $widget = null){
		global $cUser;
		if (!empty($widget) and $widget->getAuthor() != $cUser->getId() and !ACL::canAdmin('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de modifier ce lien !');
			$widget = null;
		}
		$title = (!empty($widget)) ? $widget->getName() : '';
		$desc = (!empty($widget)) ? $widget->getDesc() : '';
		$link = (!empty($widget)) ? $widget->getLink() : 'http://';
		$badgeLabel = (!empty($widget)) ? $widget->getBadgeLabel() : '';
		$badgeType = (!empty($widget)) ? $widget->getBadgeType() : '';
		$shared = (!empty($widget)) ? $widget->getShared() : $this->settings['sharedByDefault']->getValue();
		$enabled = (!empty($widget)) ? $widget->getEnabled() : true;
		$groupId = (!empty($widget)) ? $widget->getGroup() : null;
		$action = (!empty($widget)) ? '#widget_'.$widget->getId() : '';
		$form = new Form('addWidget', $action, null, 'module', $this->id);
		$form->addField(new Field('title', 'string', 'global', $title, 'Titre', 'Titre du lien', null, null, null, null, false, null, 'modify'));
		$form->addField(new Field('desc', 'string', 'global', $desc, 'Description courte', 'Je suis un lien fantastique', null, null, null, null, false, null, 'modify'));
		$form->addField(new Field('link', 'string', 'global', $link, 'Lien', 'http://lien', null, null, null, null, false, null, 'modify'));
		$groupsChoices = array();
		foreach ($this->groups as $groupName => $group){
			$groupsChoices[$groupName] = $group->getId();
		}
		$groupsData = array(
			'addEmpty'  => true,
			'choices'   => $groupsChoices
		);
		$form->addField(new Field('group', 'select', 'global', $groupId, 'Catégorie', null, $groupsData, null, null, null, false, null, 'modify'));
		$switchArray = array(
			'switch'  => true,
			'labelPosition' => 'right'
		);
		$form->addField(new Field('badgeLabel', 'string', 'global', $badgeLabel, 'Badge (optionnel)', null, null, null, null, null, false, null, 'modify'));
		$data = array(
			'addEmpty'  => true,
			'choices'   => self::$badgesTypes
		);
		$form->addField(new Field('badgeType', 'select', 'global', $badgeType, 'Type de badge (optionnel)', null, $data, null, null, null, false, null, 'modify'));
		$form->addField(new Field('enabled', 'bool', 'global', $enabled, 'Lien visible', null, $switchArray, 'Le lien sera visible sur le portail', null, null, false, null, 'modify'));
		$form->addField(new Field('shared', 'bool', 'global', $shared, 'Lien partagé', null, $switchArray, 'Si actif, votre lien sera visible par tout le monde', null, null, false, null, 'modify'));
		if (!empty($widget)){
			$form->addField(new Field('id', 'hidden', 'global', $widget->getId()));
		}
		$label = (!empty($widget)) ? 'Modifier' : 'Ajouter';
		$form->addField(new Field('action', 'button', 'global', 'saveWidget', $label, null, null, null, null, null, false, null, 'modify', 'btn-primary'));
		$form->display();
	}
} 