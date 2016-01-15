<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 14/04/14
 * Time: 09:12
 */

namespace Forms;
use Components\Help;
use Db\DbFieldSettings;
use Sanitize;
use Settings\Setting;

/**
 * Champ de formulaire (extension de Setting)
 *
 * @warning Cette classe ne doit pas être utilisée directement, utilisez plutôt ses sous-classes
 *
 * @todo Réfléchir à une manière de virer la catégorie ainsi que les arguments peu utilisés
 * @package Forms
 */
class Field extends Setting{

	/**
	 * Libellé affiché dans une balise `<label>`
	 * @var string
	 */
	protected $label = '';

	/**
	 * Libellé affiché dans la propriété placeholder du champ
	 * @var string
	 */
	protected $placeholder = '';

	/**
	 * Objet de validation du champ ou paramètres du champ dans une base de données
	 * @var DbFieldSettings|Pattern
	 */
	protected $pattern = null;

	/**
	 * Type de champ (typage php pour la sauvegarde)
	 * @var string
	 */
	protected $type = '';

	/**
	 * Aide présentée à côté du libellé du champ dans une infobulle
	 * @var string
	 */
	protected $help = '';

	/**
	 * Classe CSS à appliquer au champ
	 * @var string
	 */
	protected $class = '';

	/**
	 * Paramètre important
	 *
	 * Les paramètres importants ne doivent pas être changés à la légère.
	 * @var bool
	 */
	protected $important = false;

	/**
	 * Types possibles pour un champ - élargi par rapport aux types de Setting
	 * @var array
	 */
	static protected $types = array();

	/**
	 * Niveau d'autorisation requis pour utiliser ce champ
	 * @see \Users\ACL
	 *
	 * @var string
	 */
	protected $ACLLevel = null;

	/**
	 * Champ désactivé
	 * @var bool
	 */
	protected $disabled = false;

	/**
	 * Type html du champ
	 * @var string
	 */
	protected $htmlType = 'text';

	/**
	 * Icône associée au champ si présente
	 *
	 * Ces icônes sont celles de Bootstrap, affichées via des classes CSS `glyphicon glyphicon-<icon>`
	 * @var string
	 */
	protected $associatedIcon = null;

	/**
	 * Construction du champ
	 *
	 * @param string                  $name        Nom du champ - repris dans la propriété name et id
	 * @param string                  $type        Type du champ - type élargi par rapport au type de Setting
	 * @param mixed                   $value       Valeur à mettre dans la propriété value
	 * @param string                  $label       Libellé du champ - repris dans l'élément `<label>`
	 * @param string                  $placeholder Placeholder - repris dans la propriété du même nom
	 * @param string                  $help        Message d'aide du champ - facultatif
	 * @param DbFieldSettings|Pattern $pattern     Validation de saisie
	 * @param bool                    $important   Paramètre à signaler comme étant important
	 * @param string                  $ACLLevel    Niveau minimum d'autorisation (`access`, `modify` ou `admin`)
	 * @param string                  $class       Classe optionnelle à ajouter au champ
	 * @param bool                    $disabled    Champ désactivé si à true
	 */
	public function __construct($name, $type, $value, $label = null, $placeholder = null, $help = null, Pattern $pattern = null, $important = false, $ACLLevel = 'admin', $class = '', $disabled = false){
		self::$types = Setting::$types;
		self::$types += array(
			'email'   => 'string',
		  'radio'   => 'string',
		  'file'    => 'string',
		  'linkButton'  => 'string',
		  'select'  => 'string',
		  'checkboxList'  => 'array'
		);
		switch ($type){
			case 'email':
			case 'radio':
			case 'select':
				$typeSetting = 'string';
				break;
			default:
				$typeSetting = $type;
		}
		parent::__construct($name, $typeSetting, $value);

		$this->type = $type;
		$this->important = (bool)$important;
		if (!empty($label)) $this->label = $label;
		if (!empty($placeholder)) $this->placeholder = $placeholder;
		if (!empty($help)) $this->help = $help;
		if (!empty($pattern)) $this->pattern = $pattern;
		$this->ACLLevel = (!empty($ACLLevel)) ? $ACLLevel : 'admin';
		if (!empty($class)) $this->class = $class;
		$this->disabled = $disabled;
	}

	/**
	 * Affichage du champ
	 *
	 * @param bool   $enabled Champ modifiable
	 * @param bool   $userValue Afficher les valeurs utilisateurs au lieu des valeurs globales
	 * @param string $attrs Attributs optionnels à ajouter au champ
	 */
	public function display($enabled = true, $userValue = false, $attrs = null){
		/**
		 * @var Pattern $pattern
		 */
		$pattern = $this->pattern;
		$displayPattern = null;
		if (!empty($pattern)){
			if ($pattern->getRequired())        $displayPattern .= 'required ';
			if ($pattern->getMinLength() > 0)   $displayPattern .= 'data-minlength="'.$pattern->getMinLength().'" ';
			if ($pattern->getMaxLength() > 0)   $displayPattern .= 'data-maxlength="'.$pattern->getMaxLength().'" ';
			if ($pattern->getMinValue() > 0)    $displayPattern .= 'min="'.$pattern->getMinValue().'" ';
			if ($pattern->getMaxValue() > 0)    $displayPattern .= 'max="'.$pattern->getMaxValue().'" ';
			if ($pattern->getRegExp() !== null) $displayPattern .= 'pattern="'.$pattern->getRegExp().'" ';
		}
		$value = ($userValue and !empty($this->userValue)) ? $this->userValue : $this->value;
		?>
		<div class="form-group <?php if ($this->important) echo 'has-warning'; ?>">
			<label for="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>"><?php echo $this->label; ?> <?php if (!empty($pattern) and $pattern->getRequired()) $this->displayRequired(); ?> <?php if($this->help != '') Help::iconHelp($this->help); ?></label>
			<?php if (!empty($this->associatedIcon)) { ?>
			<div class="input-group input-<?php echo $this->type; ?>">
			<?php } ?>
			<input type="<?php echo $this->htmlType;?>" class="form-control<?php echo ' '.$this->class; ?>" id="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" name="field_<?php echo $this->type; ?>_<?php echo $this->name; ?>" <?php if ($this->placeholder != '') echo 'placeholder="'.$this->placeholder.'"'; ?> value="<?php echo $value; ?>" <?php if ($this->disabled or !$enabled) echo 'disabled'; ?> <?php echo $attrs; ?> <?php echo $displayPattern; ?>>
			<?php if (!empty($this->associatedIcon)) { ?>
			<span class="input-group-addon"><span class="fa fa-<?php echo $this->associatedIcon; ?>"></span></span>
			</div>
			<?php } ?>
			<?php if (!empty($pattern)) { ?>
			<div class="help-block with-errors"></div>
			<?php } ?>
		</div>
	<?php
	}

	/**
	 * Affichage du champ dans un table de bdd
	 *
	 * @param string      $tableName  Nom de la table
	 * @param int|string  $rowId      ID de la ligne
	 * @param mixed       $value      Valeur du champ
	 */
	public function tableItemDisplay($tableName, $rowId, $value = null){
		?><input type="<?php echo $this->htmlType; ?>" class="form-control" name="dbTable_<?php echo $tableName; ?>_<?php echo $this->type; ?>_<?php echo $this->name; ?>_<?php echo $rowId; ?>" value="<?php echo $value; ?>"><?php
	}

	/**
	 * Affiche un astérisque rouge à côté du label pour indiquer une saisie obligatoire
	 */
	protected function displayRequired(){
		?><small><span class="fa fa-asterisk text-danger tooltip-bottom" title="Obligatoire"></span></small><?php
	}

	/**
	 * Affiche un panneau warning jaune à côté du label
	 *
	 * @param string $msg Message à afficher en infobulle
	 */
	protected function displayImportant($msg = null){
		$msg = (!empty($msg)) ? $msg : 'Attention : une modification sur ce champ peut entraîner des dysfonctionnements !';
		?><small><span class="fa fa-exclamation-triangle text-warning tooltip-bottom" title="<?php echo $msg; ?>"></span></small><?php
	}

	/**
	 * Retourne le libellé du champ (balise `<label`)
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Retourne l'objet de validation de saisie du champ
	 * @return Pattern|DbFieldSettings
	 */
	public function getPattern() {
		return $this->pattern;
	}

	/**
	 * Retourne l'objet de validation de saisie du champ
	 *
	 * Alias de {@link getPattern()}
	 *
	 * @return DbFieldSettings
	 */
	public function getSettings() {
		return $this->pattern;
	}

	/**
	 * Retourne l'aide à la saisie du champ (attribut html `placeholder`)
	 * @return string
	 */
	public function getPlaceholder() {
		return $this->placeholder;
	}

	/**
	 * retourne l'aide affichée en infobulle du champ
	 * @return string
	 */
	public function getHelp() {
		return $this->help;
	}

	/**
	 * Retourne le niveau d'autorisation requis pour modifier le champ
	 * @return null|string
	 */
	public function getACLLevel() {
		return $this->ACLLevel;
	}

	/**
	 * Retourne la ou les classes CSS appliquée(s) au champ
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * Retourne l'importance du paramètre
	 * @return boolean
	 */
	public function getImportant() {
		return $this->important;
	}

	/**
	 * Retourne le statut de désactivation du champ
	 * @return boolean
	 */
	public function getDisabled() {
		return $this->disabled;
	}

	/**
	 * Définit l'aide du champ
	 * @param string $help
	 */
	public function setHelp($help) {
		$this->help = $help;
	}

	/**
	 * Valeur du paramètre définie par l'utilisateur
	 * @param mixed $userValue
	 */
	public function setUserValue($userValue) {

		if ($this->type == 'date') $userValue = Sanitize::date($userValue, 'timestamp');
		if ($this->type != 'select') settype($userValue, self::$types[$this->type]);
		$this->userValue = $userValue;
	}

	/**
	 * Valeur du paramètre définie par l'administrateur
	 * @param mixed $value
	 */
	public function setValue($value) {
		if ($this->type == 'date') $value = Sanitize::date($value, 'timestamp');
		if ($this->type != 'select') settype($value, self::$types[$this->type]);
		$this->value = $value;
	}

	/**
	 * Définit le statut d'activation du champ (saisie impossible si `true`)
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled = true) {
		$this->disabled = $disabled;
	}

	/**
	 * Ajoute une classe CSS au champ
	 * @param string $class
	 */
	public function addClass($class) {
		$this->class .= $class.' ';
	}

	/**
	 * Définit le statut d'importance du champ
	 * @param boolean $important
	 */
	public function setImportant($important = true) {
		$this->important = $important;
	}

	/**
	 * Définit un objet de validation/paramétrage pour le champ
	 *
	 * @param \Db\DbFieldSettings|\Forms\Pattern $pattern
	 */
	public function setPattern($pattern) {
		$this->pattern = $pattern;
	}



}