<?php
/**
 * Classe de gestion des menus
 *
 * User: cedric.gallard
 * Date: 17/03/14
 * Time: 14:03
 *
 */

namespace Components;
use Logs\Alert;
use Sanitize;

/**
 * Classe de gestion des menus
 *
 * Un menu est composé d'items de menus
 * Les instanciations de menus sont stockées dans la variable {self::$menus}
 *
 *
 * @use Item
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
	 * Lien du menu
	 * @var string
	 */
	protected $link = '';

	/**
	 * Titre affiché du menu
	 * @var string
	 */
	protected $title = '';

	/**
	 * Description du menu
	 * @var string
	 */
	protected $desc = '';

	/**
	 * Icône associée au menu
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Tableau global des menus instanciés
	 * @var Menu[]
	 */
	protected static $menus = array();

	/**
	 * Liste d'objets d'items de menu
	 * @var Item[]
	 */
	protected $items = array();

	/**
	 * Construction du menu
	 *
	 * @param string $name  Nom du menu
	 * @param string $title Titre affiché du menu
	 * @param string $link  Lien du menu (facultatif)
	 * @param string $desc  Description du menu (facultatif)
	 * @param string $icon  Icône associée au menu (facultatif)
	 */
	public function __construct($name, $title, $link = '', $desc = '', $icon = ''){
		$this->name = Sanitize::sanitizeFilename($name);
		$this->title = htmlspecialchars($title);
		if (!empty($link)) $this->link = htmlspecialchars($link);
		if (!empty($desc)) $this->desc = htmlspecialchars($desc);
		if (!empty($icon)) $this->icon = htmlspecialchars($icon);
		self::$menus[$this->name] = $this;
	}

	/**
	 * Destruction du menu
	 */
	public function __destruct(){
		unset(self::$menus[$this->name]);
	}

	/**
	 * Ajoute un item dans le menu
	 *
	 * @param Item $item Item ou sous-menu de menu
	 * @param int  $priority Priorité de l'item de menu (entre 0 et 100) (facultatif)
	 *
	 * @return bool
	 */
	public function add(Item $item, $priority = 50){
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

		$itemKey = ($priority * 100) + count($this->items) + 1;
		$this->items[$itemKey] = $item;
		self::$menus[$this->name]->items = $this->items;
		return true;
	}

	protected function sortMenus(){
		/*
		Plusieurs items peuvent avoir la même priorité de base.
		Pour pouvoir les traiter, on multiplie la priorité par 100,
		ce qui permet potentiellement à 100 items d'avoir la même priorité
		*/

		$toOrder = array();
		// On met tous les Items qui ont la même priorité que celui ajouté dans un autre tableau, et on les enlève de $this->items
		foreach ($this->items as $itemPriority => $itemObj){
			$toOrder[(int)($itemPriority / 100)][] = $itemObj;
		}
		// On réinitialise les items de menu
		$this->items = array();
		foreach ($toOrder as $priority => $items){
			// On trie le tableau par titre
			$toOrder[$priority] = Sanitize::sortObjectList($items, array('title'));
		}
		// On (ré)insère les items de $toOrder dans le tableau global des items
		foreach ($toOrder as $priority => $itemObjs){
			foreach ($itemObjs as $key => $item) {
				$itemKey               = ($priority * 100) + $key;
				$this->items[$itemKey] = $item;
			}
		}
		self::$menus[$this->name]->items = $this->items;
	}

	/**
	 * Crée et affiche le menu
	 *
	 * @param string $menuClass Classe CSS optionnelle du menu (facultatif)
	 * @param string $itemClass Classe CSS optionnelle des items du menu (facultatif)
	 * @param bool   $displayTitle Afficher ou non le titre du menu (pas d'affichage par défaut) (facultatif)
	 */
	public function build($menuClass = '', $itemClass = '', $displayTitle = false){
		$this->sortMenus();
		ksort($this->items);
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