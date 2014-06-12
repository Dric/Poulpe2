<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 03/06/14
 * Time: 15:57
 */

namespace Modules\PostIt;


use Components\Avatar;
use Components\Item;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Settings\Field;
use Settings\Form;
use Settings\PostedData;
use Users\ACL;
use Users\User;

class PostIt extends Module{
	protected $name = 'Post-It';
	protected $title = 'Permet de noter des petites choses, des astuces, des bouts de code, etc.';

	protected $authorAvatars = array();
	protected $nbPosts = 0;
	protected $page = 1;
	protected $allowUsersSettings = true;

	public function __construct(){
		parent::__construct();
		Front::setCssHeader('<link href="js/pagedown-bootstrap/css/jquery.pagedown-bootstrap.css" rel="stylesheet">');
		Front::setCssHeader('<link href="js/highlight/styles/default.css" rel="stylesheet">');
		Front::setJsFooter('<script src="js/pagedown-bootstrap/js/jquery.pagedown-bootstrap.combined.min.js"></script>');
		Front::setJsFooter('<script src="js/highlight/highlight.pack.js"></script>');
		Front::setJsFooter('<script src="Modules/PostIt/PostIt.js"></script>');

	}

	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('PostIt', 'Post-It', MODULE_URL.end(explode('\\', get_class())), 'Permet de noter des petites choses, des astuces, des bouts de code, etc.', null, null));
	}

	/**
	 * Installe le module en bdd, avec ses paramètres
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
		/**
		 * @see Db\Db->createTable pour plus de détails sur la création d'une table.
		 */
		$this->dbTables['module_postit'] = array(
			'name'        => 'module_postit',
			'desc'        => 'Posts',
			'fields'      => array(
				'id'    => array(
					'show'          => false,
					'type'          => 'int',
					'length'        => 11,
					'null'          => false,
					'autoIncrement' => true,
				),
				'author' => array(
					'comment' => 'ID de l\'auteur du post',
					'type'    => 'smallint',
					'length'  => 6,
					'null'    => false
				),
				'content'  => array(
					'comment' => 'Texte du post-it',
					'type'    => 'text',
					'length'  => 0,
					'null'    => false
				),
				'shared'  => array(
					'comment' => 'Post-it public ou privé',
					'type'    => 'bool',
					'length'  => 1,
					'default' => 0,
					'null'    => false
				),
				'created'  => array(
					'comment' => 'Timestamp de création',
					'type'    => 'int',
					'length'  => 11,
					'null'    => false
				),
				'modified'  => array(
					'comment' => 'Timestamp de dernière modification',
					'type'    => 'int',
					'length'  => 11,
					'null'    => false
				)
			),
			'primaryKey'  => 'id',
			'indexKey'    => array('author', 'shared'),
			'onDuplicateKeyUpdate' => array('content', 'modified'),
		  'foreignKey'  => array(
				'field'         => 'author',
		    'foreignTable'  => 'users',
		    'foreignField'  => 'id',
		    'onDelete'      => 'NO ACTION',
		    'onUpdate'      => 'CASCADE'
		  )
		);
		$switchArray = array(
			'switch'        => true,
		  'size'          => 'small',
		  'labelPosition' => 'right'
		);
		$this->settings['sharedByDefault'] = new Field('sharedByDefault', 'bool', 'user', true, 'Rendre les post-it publiques par défaut', null, $switchArray);
		$this->settings['alwaysShowAddPost'] = new Field('alwaysShowAddPost', 'bool', 'user', false, 'Afficher l\'ajout de post-it en permanence <noscript><span class="text-danger">(actif seulement quand Javascript est activé)</span></noscript>', null, $switchArray);
		$choices = array();
		$tab = explode(', ', PER_PAGE_VALUES);
		$choices['choices'] = array_combine($tab, $tab);
		$this->settings['postsPerPage'] = new Field('postsPerPage', 'select', 'user', 10, 'Nombre de post-it par page', null, $choices);
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		global $db, $cUser;
		// Nombre de post-it accessibles par l'utilisateur en bdd
		$this->nbPosts = $db->query('SELECT COUNT(id) as nbPosts FROM `module_postit` WHERE `author` = '.$cUser->getId().' OR `shared` = 1', 'val');
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>Post-It <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<?php
				// Traitement des envois de formulaires
				$req = PostedData::get();
				$search = null;
				$filters = array();
				// Formulaire de recherche
				if (isset($_GET['action']) and $_GET['action'] == 'searchPost'){
					$search = htmlspecialchars($_GET['field_string_search']);
					if (!empty($search)) $filters['search'] = $search;
					// Afin de conserver la requête de recherche sur les éventuelles autres pages, on ajoute les critères à l'url du module
					$this->url .= '&field_string_search='.$search.'&action=searchPost';
				}
				// Traitement de la page actuelle
				if (isset($_REQUEST['itemsPage'])){
					$filters['page'] = (int)$_REQUEST['itemsPage'];
					$this->page = $filters['page'];
				}
				?>
				<div class="row">
					<div class="col-lg-6 col-md-6">
						<p>
							<?php echo $this->title; ?>.
						</p>
					</div>
					<div class="col-lg-6 col-md-6">
						<?php $this->searchForm($search); ?>
					</div>
				</div>
				<?php if (ACL::canModify('module', $this->id)){ ?>
				<h3>Ajouter un Post-it <a class="btn btn-default btn-xs" href="#" id="toggleEditForm"><?php echo ($this->settings['alwaysShowAddPost']->getValue() and !(isset($req['action']) and $req['action'] == 'editPost')) ? 'Masquer' : 'Afficher'; ?> l'ajout de Post-it</a></h3>
				<div id="editForm" <?php if (!$this->settings['alwaysShowAddPost']->getValue() and !(isset($req['action']) and $req['action'] == 'editPost')) echo 'style="display:none;"'; ?>>
					<div class="row">
						<div class="col-md-8">
							<?php
							$post = null;
							// Formulaire d'édition et suppression
							if (isset($req['action']) and in_array($req['action'], array('editPost', 'delPost')) and isset($req['id'])){
								// récupération du post-it à traiter
								$ret = $db->get('module_postit', null, array('id'=>$req['id']));
								if (empty($ret)){
									new Alert('error', 'Ce post-it n\'existe pas !');
								}else{
									if ($req['action'] == 'editPost'){
										$post = new Post($ret[0]);
									}elseif($req['action'] == 'delPost'){
										$this->deletePost(new Post($ret[0]));
									}
								}
							}
							// Affichage du formulaire d'ajout/édition
							$this->editPostForm($post);
							?>
							<br>
						</div>
						<div class="col-md-4">
							Règles d'utilisation :
							<div class="help-block">
								<ul>
									<li>Vous pouvez utiliser la syntaxe <a href="http://michelf.ca/projets/php-markdown/syntaxe/" title="Mettez en forme votre texte rapidement">Markdown</a> étendue</li>
									<li>Le HTML est autorisé, mais privilégiez plutôt Markdown</li>
									<li>La recherche se faisant sur le texte, n'hésitez pas à être exhaustif</li>
									<li>Respectez la langue française, elle vous respectera en retour</li>
									<li>Evitez de noter ici des mots de passe, utilisez plutôt KeyPass</li>
									<li><?php echo \Get::stupidRule(); ?></li>
									<li>Les adresses Internet seront automatiquement converties en lien hypertexte</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<?php }else{ ?>
				<br>
				<div class="alert alert-info">Vous pouvez seulement visualiser les Post-it.</div>
				<?php } ?>
				<?php $this->displayPosts($filters); ?>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Affiche le formulaire de recherche
	 *
	 * @param string $search Requête de recherche
	 */
	protected function searchForm($search = null){
		$form = new Form('search', $this->url, null, null, null, 'get', 'form-inline', array('module' => 'PostIt'));
		$form->addField(new Field('search', 'string', 'global', $search, '', 'Chercher'));
		$form->addField(new Field('action', 'button', 'global', 'searchPost', 'Rechercher', null, null, null, null, null, false, null, 'modify', 'btn-primary btn-sm'));
		$form->display();
	}

	/**
	 * Ajoute ou modifie un post-it
	 *
	 * @param Post $post Objet post-it (facultatif)
	 */
	protected function editPostForm(Post $post = null){
		global $cUser;
		if (!empty($post) and $post->getAuthor() != $cUser->getId() and !ACL::canAdmin('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de modifier ce post-it !');
			$post = null;
		}
		$content = (!empty($post)) ? $post->getContent(true) : '';
		$shared = (!empty($post)) ? $post->getShared() : $this->settings['sharedByDefault']->getValue();

		$action = (!empty($post)) ? '#post_'.$post->getId() : '#postsEnd';
		$form = new Form('addPost', $action, null, 'module', $this->id);
		$form->addField(new Field('content', 'text', 'global', $content, 'Post-It', 'Merci de veiller à ce que votre prose soit correctement orthographiée !', null, null, null, null, false, null, 'modify'));
		$switchArray = array(
			'switch'  => true,
			'labelPosition' => 'left'
		);
		$form->addField(new Field('shared', 'bool', 'global', $shared, 'Post-It partagé', null, $switchArray, 'Si actif, votre post-it sera visible par tout le monde', null, null, false, null, 'modify'));
		if (!empty($post)){
			$form->addField(new Field('id', 'hidden', 'global', $post->getId()));
			$form->addField(new Field('page', 'hidden', 'global', $this->page));
		}else{
			$form->addField(new Field('page', 'hidden', 'global', ceil($this->nbPosts / $this->settings['postsPerPage']->getValue())));
		}
		$label = (!empty($post)) ? 'Modifier' : 'Ajouter';
		$form->addField(new Field('action', 'button', 'global', 'savePost', $label, null, null, null, null, null, false, null, 'modify', 'btn-primary'));
		$form->display();
	}

	/**
	 * Gère l'affichage des post-it
	 */
	protected function displayPosts($filters = array()){
		global $db, $cUser;
		$title = 'Liste des Post-it';
		$postsPerPage = $this->settings['postsPerPage']->getValue();
		// Page courante
		$this->page = (isset($filters['page'])) ? $filters['page'] : 1;
		// Nombre de post-it accessibles par l'utilisateur en bdd
		$this->nbPosts = $db->query('SELECT COUNT(id) as nbPosts FROM `module_postit` WHERE `author` = '.$cUser->getId().' OR `shared` = 1', 'val');
		// requête SQL permettant de retourner la liste des post-it à afficher
		$sql = 'SELECT *';
		if (!empty($filters)){
			// Filtre de recherche
			if (isset($filters['search'])){
				$searchWords = explode(' ', $filters['search']);
				if (!empty($searchWords)){
					$sql .= ', (';
					foreach ($searchWords as $word){
						/**On ne traite pas les mots de moins de 3 caractères, sauf s'ils sont en majuscules (AD, OU, etc.)	*/
						if (ctype_upper($word) OR strlen($word) > 2){
							$sql .= '(CASE WHEN content LIKE "%'.strtolower($word).'%" THEN 1 ELSE 0 END) + ';
						}
					}
					$sql = trim($sql, ' + ');
					$sql .= ') AS relevance FROM module_postit WHERE ';
					foreach ($searchWords as $word){
						$sql .= 'content LIKE "%'.strtolower($word).'%" OR ';
					}
					$sql = trim($sql, ' OR ');
					$sql .= ' ORDER BY relevance DESC';
				}
				$title = 'Résultats pour la recherche sur <code>'.$filters['search'].'</code>';
				$nbPostsFiltered = count($db->query($sql));
			}else{
				$sql .= ' FROM `module_postit` WHERE `author` = '.$cUser->getId().' OR `shared` = 1';
			}
		}
		if ($sql == 'SELECT *'){
			$sql = 'SELECT * FROM `module_postit` WHERE `author` = '.$cUser->getId().' OR `shared` = 1';
		}
		// On ne demande que les post-it de la page en cours (LIMIT position, nombre de post-it à retourner)
		$sql .= ' LIMIT '.(($postsPerPage*$this->page)-$postsPerPage).', '.$postsPerPage;
		// Requête vers la bdd
		$postsDb = $db->query($sql);
		// Nombre de post-it à paginer : nombre de post-it retournés de la recherche ou nombre de post-it accessibles par l'utilisateur
		$nbPaginate = (isset($nbPostsFiltered)) ? $nbPostsFiltered : $this->nbPosts;
		// Numéro du premier post-it affiché
		$min = $postsPerPage * ($this->page - 1) + 1;
		// Numéro du dernier post-it affiché
		$max = $postsPerPage * ($this->page - 1) + count($postsDb);
		/**
		 * Affichage du titre
		 *
		 * - Affichage de la page en cours si plusieurs pages sont possibles
		 * - Affichage d'un badge présentant les numéros des post-it affichés, s'ils sont filtrés par une recherche ainsi que leur nombre total.
		 */
		if (!empty($postsDb)){
			// Pagination en haut de la liste
			?><div class="pull-right clearfix"><?php Front::paginate($this->page, $postsPerPage, $nbPaginate, $this->url); ?></div><?php
		}
		?><h3><?php echo $title.((ceil($nbPaginate / $postsPerPage) > 1) ? ' (page '.$this->page.')' : ''); ?> <span class="badge alert-info"><?php echo ($min < $max) ? $min.'-'.$max : $max; ?><?php if (isset($filters['search'])) echo ' sur '.$nbPaginate.' filtré'.((count($postsDb) != 1) ? 's':''); ?> sur <?php echo $this->nbPosts; ?></span><?php if (isset($filters['search'])) echo ' - <span class="small"><a href="'.MODULE_URL.'PostIt">Voir tous les Post-It</a></small>'; ?></h3><?php
		if (!empty($postsDb)){
			// Affichage des post-it
			foreach ($postsDb as $post){
				?><br><?php
				$this->displaySinglePost(new Post($post));
			}
			// Pagination en bas de la liste
			?><div id="postsEnd" class="pull-right"><?php Front::paginate($this->page, $postsPerPage, $nbPaginate, $this->url); ?></div><?php
		}else{
			?><div class="alert alert-info">Il n'y a pas de post-it à afficher !</div><?php
		}
	}

	/**
	 * Affiche un post-it
	 *
	 * @param Post $post Post-it à afficher
	 */
	protected function displaySinglePost(Post $post){
		global $cUser;
		// Les avatars sont mis en mémoire afin de ne pas refaire une requête à chaque fois
		if (!isset($this->authorAvatars[$post->getAuthor(true)])){
			$author = new User($post->getAuthor(true));
			$this->authorAvatars[$post->getAuthor(true)] = $author->getAvatar();
		}
		?>
		<div id="post_<?php echo $post->getId(); ?>" class="row">
			<div class="col-lg-1 col-md-2 col-sm-2 hidden-xs hidden-phone">
				<?php echo $this->authorAvatars[$post->getAuthor(true)]; ?>
			</div>
			<div class="col-lg-11 col-md-10 col-sm-10 col-xs-12">
				<div class="well well-large">
					<?php echo $post->getContent(); ?>
				</div>
				<div class="pull-right small">
					<i>
						Ajouté le <?php echo $post->getCreated(); ?>
						<?php if ($post->getModified(true) > 0) { ?>
						- Modifié le <?php echo $post->getModified(); ?>
						<?php } ?>
						&nbsp;<span class="visible-xs visible-phone">par <?php echo $post->getAuthor(); ?></span>
					</i>
					<?php if ($post->getShared()) { ?><span class="glyphicon glyphicon-bullhorn tooltip-bottom" title="Post-It public"></span>&nbsp;<?php } ?>
					<?php
					if (ACL::canModify('module', $this->id) and (ACL::canAdmin('module', $this->id) or $cUser->getId() == $post->getAuthor(true))){
						$form = new Form('editPost_'.$post->getId(), $this->url.'&itemsPage='.$this->page, null, 'module', $this->id, 'post', 'form-inline inline-block');
						$form->addField(new Field('id', 'hidden', 'global', $post->getId()));
						$form->addField(new Field('page', 'hidden', 'global', $this->page));
						$form->addField(new Field('action', 'button', 'global', 'editPost', 'Modifier', null, null, null, null, null, false, null, 'modify', 'btn-xs'));
						$form->addField(new Field('action', 'button', 'global', 'delPost', 'Supprimer', null, null, null, null, null, false, null, 'modify', 'btn-xs'));
						$form->display();
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Sauvegarde un post-it en bdd
	 * @return bool
	 */
	protected function savePost(){
		global $cUser, $db;
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$req = PostedData::get();
		if (!isset($req['content']) or empty($req['content'])){
			new Alert('error', 'Le post-it est vide !');
			return false;
		}

		$fields['content'] = \Sanitize::SanitizeForDb($req['content'], false);
		$fields['shared'] = \Sanitize::SanitizeForDb($req['shared']);
		if (isset($req['id'])){
			$fields['modified'] = time();
			$where['id'] = $req['id'];
			$ret = $db->update('module_postit', $fields, $where);
		}else{
			$fields['author'] = $cUser->getId();
			$fields['created'] = time();
			$ret = $db->insert('module_postit', $fields);
		}
		if (!$ret){
			new Alert('error', 'Impossible de sauvegarder le post-it !');
			return false;
		}else{
			new Alert('success', 'Le post-it a été sauvegardé !');
			$_REQUEST['itemsPage'] = $req['page'];
			PostedData::reset();
			return true;
		}
	}

	/**
	 * Supprime un post-it
	 *
	 * @param Post $post Post-it à supprimer
	 *
	 * @return bool
	 */
	protected function deletePost(Post $post){
		global $cUser, $db;
		if (!ACL::canModify('module', $this->id) or (!ACL::canAdmin('module', $this->id) and $post->getAuthor() != $cUser->getId())){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$ret = $db->delete('module_postit', array('id'=>$post->getId()));
		if (!$ret){
			new Alert('error', 'Impossible de supprimer le post-it !');
			return false;
		}else{
			new Alert('success', 'Le post-it a été supprimé !');
			return true;
		}
	}

} 