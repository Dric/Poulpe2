<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 09/07/14
 * Time: 08:58
 */

namespace Forms\Fields;


use Components\Help;
use Forms\Field;

/**
 * Champ de sélection d'une valeur dans une liste
 *
 * @package Forms\Fields
 */
class Select extends Field{

	/** @var string Type de champ (pour sauvegarde) */
	protected $type = 'select';
	/**
	 * Liste de choix dans un tableau associatif de la forme `Valeur => libellé`
	 * @var string[]
	 */
	protected $choices = array();
	/**
	 * Ajouter une valeur vide au début des choix
	 * @var bool
	 */
	protected $addEmpty = false;

	/**
	 * Déclaration d'une liste à sélection unique
	 *
	 * @param string    $name           Nom du champ
	 * @param string    $value          Valeur du champ
	 * @param string    $label          Intitulé du champ (facultatif)
	 * @param string    $help           Message d'aide affiché en infobulle (facultatif)
	 * @param bool      $important      Le champ est marqué comme étant important (facultatif)
	 * @param string    $ACLLevel       Niveau de sécurité requis pour modifier le champ (facultatif)
	 * @param string    $class          Classe CSS à ajouter au champ (facultatif)
	 * @param bool      $disabled       Champ désactivé (facultatif)
	 * @param array     $choices        Choix possibles dans la liste sous forme de tableau associatif 'valeur' => 'libellé'
	 * @param bool      $addEmpty       Valeur cochée par défaut dans la liste $choices ('all' pour cocher toutes les valeurs)
	 */
	public function __construct($name, $value = null, $label = null, $help = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false, $choices = null, $addEmpty = false){
		$this->choices = (array)$choices;
		$this->addEmpty = (bool)$addEmpty;
		parent::__construct($name, $this->type, $value, $label, null, $help, null, $important, $ACLLevel, $class, $disabled);
	}

	/**
	 * Affichage du champ
	 *
	 * Les entrées sont lues à partir d'un tableau de type `$choix => $libellé`
	 * Si $libellé est lui-même un tableau, on crée une balise `<optgroup>` dans laquelle on met le contenu de $libellé
	 *
	 * @param bool $enabled Champ modifiable
	 * @param bool $userValue Affichage de la valeur utilisateur au lieu de la valeur globale
	 * @param string $attrs Attributs html à ajouter au champ de saisie
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		?>
		<div class="form-group <?php if ($this->important) echo 'has-warning'; ?>">
			<label for="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>"><?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help != '') Help::iconHelp($this->help); ?></label>
			<select class="form-control<?php echo ' '.$this->class; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?>>
				<?php
				if ($this->addEmpty){
				?><option></option><?php
				}
				foreach ($this->choices as $choice => $label){
					if (is_array($label)){
						?><optgroup label="<?php echo $choice; ?>"><?php
						foreach ($label as $subChoice => $subLabel){
							?><option value="<?php echo $subChoice; ?>" <?php if ($value == $subChoice) echo 'selected'; ?>><?php echo $subLabel; ?></option><?php
						}
						?></optgroup><?php
					}else{
						?><option value="<?php echo $choice; ?>" <?php if ($value == $choice) echo 'selected'; ?>><?php echo $label; ?></option><?php
					}
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 * Affichage du champ dans une table de bdd
	 *
	 * @param string      $tableName  Nom de la table
	 * @param int|string  $rowId      ID de la ligne
	 * @param mixed       $value      Valeur du champ
	 */
	public function tableItemDisplay($tableName, $rowId, $value = null){
		?>
		<select class="form-control<?php echo ' '.$this->class; ?>" id="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" <?php if ($this->disabled) echo 'disabled'; ?>>
			<?php
			if ($this->addEmpty){
			?><option></option><?php
			}
			foreach ($this->choices as $choice => $label){
				if (is_array($label)){
					?><optgroup label="<?php echo $choice; ?>"><?php
					foreach ($label as $subChoice => $subLabel){
						?><option value="<?php echo $subChoice; ?>" <?php if ($value == $subChoice) echo 'selected'; ?>><?php echo $subLabel; ?></option><?php
					}
					?></optgroup><?php
				}else{
					?><option value="<?php echo $choice; ?>" <?php if ($value == $choice) echo 'selected'; ?>><?php echo $label; ?></option><?php
				}
			}
			?>
		</select>
	<?php
	}

	/**
	 * Retourne la liste des choix
	 *
	 * @return \string[]
	 */
	public function getChoices() {
		return $this->choices;
	}

	/**
	 *
	 * Ajoute un choix dans la liste des choix
	 *
	 * @param string $label Libellé du choix
	 * @param string $value Valeur du choix
	 *
	 * @internal param \string[] $choices
	 */
	public function addChoice($label, $value) {
		$this->choices[$value] = $label;
	}

	/**
	 * Ajoute plusieurs choix dans la liste des choix
	 *
	 * @param \string[string] $choicesToAdd Choix à ajouter, sous la forme d'un tableau associatif 'valeur' => 'libellé'
	 */
	public function addChoices(array $choicesToAdd){
		array_merge($this->choices, $choicesToAdd);
	}

	/**
	 * Définit la liste des choix
	 *
	 * Si des choix existent déjà, ils seront écrasés.
	 *
	 * @param \string[] $choices
	 *
	 */
	public function setChoices($choices) {
		$this->choices = $choices;
	}

} 