<?php
/**
 * Classe des items de menus
 *
 * User: cedric.gallard
 * Date: 17/03/14
 * Time: 14:05
 *
 * @package Components
 */

namespace Components;
use Sanitize;

/**
 * Class Item
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

	protected $desc = '';

	/**
	 * Icône associée à l'item de menu
	 * @var string
	 */
	protected $icon = '';

	protected $class = '';

	public function __construct($name, $title, $link, $desc = '', $icon = '', $class = ''){
		$this->name = Sanitize::sanitizeFilename($name);
		$this->title = $title;
		$this->link = htmlspecialchars($link);
		if (!empty($acl))  $this->acl = (array)$acl;
		if (!empty($desc)) $this->desc = $desc;
		if (!empty($icon)) $this->icon = htmlspecialchars($icon);
		if (!empty($class)) $this->class = htmlspecialchars($class);
	}

	public function build($itemClass = ''){
		if (!empty($this->class)) $itemClass = $this->class.' '.$itemClass;
		?>
		<li class="menuItem<?php echo (!empty($itemClass)) ? ' '.$itemClass : ''; ?>" id="item-<?php echo $this->name; ?>">
			<a href="<?php echo $this->link; ?>" title="<?php echo $this->desc; ?>">
				<?php if (!empty($this->icon)) { ?><span class="glyphicon glyphicon-<?php echo $this->icon; ?>"></span>&nbsp;<?php } ?>
				<?php echo $this->title; ?>
			</a>
		</li>
		<?php
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
}

?>