<?php
/**
 * Classe des items de menus
 *
 * User: cedric.gallard
 * Date: 17/03/14
 * Time: 14:05
 *
 */

namespace Components;
use Sanitize;

/**
 * Classe des items de menus
 *
 * @package Components
 */
class Item {

	/**
	 * Nom de l'item de menu
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

	/**
	 * Description de l'item
	 * @var string
	 */
	protected $desc = '';

	/**
	 * Icône associée à l'item de menu
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Classe CSS appliquée à l'item
	 * @var string
	 */
	protected $class = '';

	/**
	 * Construction de l'item de menu
	 *
	 * @param string $name  Nom de l'item de menu
	 * @param string $title Titre affiché de l'item de menu
	 * @param string $link  Lien de l'item de menu
	 * @param string $desc  Description de l'item (facultatif)
	 * @param string $icon  Icône associée à l'item de menu (facultatif)
	 * @param string $class Classe CSS appliquée à l'item (facultatif)
	 */
	public function __construct($name, $title, $link, $desc = '', $icon = '', $class = ''){
		$this->name = Sanitize::sanitizeFilename($name);
		$this->title = $title;
		$this->link = htmlspecialchars($link);
		if (!empty($acl))  $this->acl = (array)$acl;
		if (!empty($desc)) $this->desc = $desc;
		if (!empty($icon)) $this->icon = htmlspecialchars($icon);
		if (!empty($class)) $this->class = htmlspecialchars($class);
	}

	/**
	 * Affichage de l'item de menu
	 *
	 * @param string $itemClass Classe à appliquer à l'item (facultatif)
	 */
	public function build($itemClass = ''){
		if (!empty($this->class)) $itemClass = $this->class.' '.$itemClass;
		?>
		<li class="menuItem<?php echo (!empty($itemClass)) ? ' '.$itemClass : ''; ?>" id="item-<?php echo $this->name; ?>">
			<a href="<?php echo $this->link; ?>" title="<?php echo $this->desc; ?>">
				<?php if (!empty($this->icon)) { ?><span class="fa fa-<?php echo $this->icon; ?> fa-fw"></span>&nbsp;<?php } ?>
				<?php echo $this->title; ?>
			</a>
		</li>
		<?php
	}

	/**
	 * Retourne le titre de l'item de menu
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
}

?>