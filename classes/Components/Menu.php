<?php
/**
 * Classe de gestion des menus
 *
 * User: cedric.gallard
 * Date: 17/03/14
 * Time: 14:03
 *
 * @package Components
 */

namespace Components;
use Logs\Alert;
use Sanitize;

/**
 * Class Menu
 *
 * @package Components
 */
class Menu {

	/**
	 * Nom du menu
	 * @var string
	 */
	protected $name = '';

	/**
	 * Lien de l'item de menu
	 * @var string
	 */
	protected $link = '';

	/**
	 * Titre affiché de l'item de menu
	 * @var string
	 */
	protected $title = '';

	protected $desc = '';

	/**
	 * Icône associée à l'item de menu
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Tableau global de toutes les alertes instanciées
	 * @var array
	 */
	protected static $menus = array();

	/**
	 * Liste d'objets d'items de menu
	 * @var array
	 */
	protected $items = array();

	public function __construct($name, $title, $link = '', $desc = '', $icon = ''){
		$this->name = Sanitize::sanitizeFilename($name);
		$this->title = htmlspecialchars($title);
		if (!empty($link)) $this->link = htmlspecialchars($link);
		if (!empty($desc)) $this->desc = htmlspecialchars($desc);
		if (!empty($icon)) $this->icon = htmlspecialchars($icon);
		self::$menus[$this->name] = $this;
	}

	public function __destruct(){
		unset(self::$menus[$this->name]);
	}

	/**
	 * Ajoute un item dans le menu
	 *
	 * @param object $item Item ou sous-menu de menu
	 * @param int  $priority Priorité de l'item de menu (entre 0 et 100)
	 *
	 * @return bool
	 */
	public function add($item, $priority = 50){
		// Si $item n'est ni un Item, ni un Menu, on ne l'ajoute pas.
		if (!($item instanceof Item) and !($item instanceof Menu)){
			new Alert('debug', '<code>Menu->add()</code> : $item n\'est ni un menu, ni un item !');
			return false;
		}
		/*
		Plusieurs items peuvent avoir la même priorité de base.
		Pour pouvoir les traiter, on multiplie la priorité par 100,
		ce qui permet potentiellement à 100 items d'avoir la même priorité
		*/

		$toOrder = array();
		// On met tous les Items qui ont la même priorité que celui ajouté dans un autre tableau, et on les enlève de $this->items
		foreach ($this->items as $itemPriority => $itemObj){
			if ((int)($itemPriority / 100) == $priority){
				$toOrder[] = $itemObj;
				unset($this->items[$itemPriority]);
			}
		}
		// N'oublions pas l'item qu'on veut ajouter au menu...
		$toOrder[] = $item;
		// On trie le tableau par titre
		$toOrder = Sanitize::sortObjectList($toOrder, array('title'));
		// On (ré)insère les items de $toOrder dans le tableau global des items
		foreach ($toOrder as $key => $itemObj){
			$itemKey = ($priority * 100) + $key;
			$this->items[$itemKey] = $itemObj;
		}

		self::$menus[$this->name]->items = $this->items;
		return true;
	}

	/**
	 * Crée et affiche le menu
	 * @param string $menuClass Classe CSS optionnelle du menu
	 * @param string $itemClass Classe CSS optionnelle des items du menu
	 * @param bool   $displayTitle Afficher ou non le titre du menu (pas d'affichage par défaut)
	 */
	public function build($menuClass = '', $itemClass = '', $displayTitle = false){
		ksort ($this->items);
		?>
		<div class="secondary-menu">
			<?php if (!empty($this->title) and $displayTitle) {?><h4 class="secondary-menu-title"><?php echo $this->title; ?></h4><?php } ?>
			<ul class="menu<?php echo (!empty($menuClass)) ? ' '.$menuClass : ''; ?>" id="menu-<?php echo $this->name; ?>">
				<?php
				foreach ($this->items as $item){
					/** @var Item $item */
					$item->build($itemClass);
				}
				?>
			</ul>
		</div>
		<?php
	}
}

?>