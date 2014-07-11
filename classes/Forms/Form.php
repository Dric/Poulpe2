<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 14/04/14
 * Time: 08:57
 */

namespace Forms;
use Components\Help;
use Users\ACL;

/**
 * Gestion des formulaires
 * Class Form
 *
 * @package Forms
 */
class Form {

	/**
	 * Nom du formulaire (repris dans l'id de l'élément <form>
	 * @var string
	 */
	protected $name = '';

	/**
	 * Action du formulaire (propriété action de l'élément <form>
	 * @var string
	 */
	protected $action = '';

	protected $component = array(
		'component' => null,
	  'id'        => 0
	);

	protected $class = '';

	protected $method = 'post';

	/**
	 * Tableau des champs du formulaire
	 * @var array
	 */
	protected $fields = array();

	protected $parameters = array();


	/**
	 * Types de champs
	 * @var array
	 */
	static protected $fieldTypes = array('fields', 'hidden', 'buttons');

	/**
	 * Construction du formulaire
	 *
	 * @param string $name Nom du formulaire
	 * @param string $action Action du formulaire (url de redirection, si nécessaire - il vaut mieux privilégier un champ masqué ou une valeur de bouton d'envoi s'il n'y a pas de redirection à effectuer)
	 * @param array  $fields Champs du formulaire, répartis dans 3 index du tableau : 'field', 'hidden' ou 'button'
	 * @param string $component Composant ('module', 'admin', 'profil', etc.)
	 * @param int    $componentId Id du composant
	 * @param string $method Méthode (post ou get)
	 * @param string $class
	 * @param array  $parameters Paramètres optionnels à passer dans l'url, sous forme de tableau associatif
	 */
	public function __construct($name, $action = null, $fields = array(), $component = null, $componentId = null, $method = 'post', $class = null, $parameters = null){
		$this->name = $name;
		if (!empty($action)) $this->action = $action;
		foreach (self::$fieldTypes as $type){
			$this->fields[$type] = array();
		}
		if (!empty($fields)) $this->fields = $fields;
		if (!empty($component)) $this->component['component'] = $component;
		if (!empty($componentId)) $this->component['id'] = $componentId;
		$this->method = $method;
		if (!empty($class)) $this->class = $class;
		if (!empty($parameters)) $this->parameters = $parameters;
	}

	/**
	 * Ajoute un champ au formulaire
	 *
	 * @param Field $field
	 */
	public function addField(Field $field){
		switch ($field->getType()){
			case 'hidden': $fieldType = 'hidden'; break;
			case 'button':
			case 'linkButton':
				$fieldType = 'buttons';
				break;
			default: $fieldType = 'fields'; break;
		}
		$this->fields[$fieldType][] = $field;
	}

	/**
	 * Affiche un formulaire
	 */
	public function display(){
		global $cUser;
		?>
		<form id="form_<?php echo $this->name?>" class="<?php if (!empty($this->class)) echo $this->class; ?>" method="<?php echo $this->method; ?>" role="form" action="<?php if (!empty($this->action)) echo $this->action; ?>" enctype="multipart/form-data">
			<?php
			// On affiche d'abord les champs
			/**
			 * @var Field $field
			 */
			$userValue = (isset($this->fields['userSettings']) and $this->fields['userSettings']) ? true : false;
			foreach ($this->fields['fields'] as $field){
				if (($userValue and $field->getCategory() == 'user') or !$userValue){
					//if ($field->getName() != 'allowUsersSettings') {
					if (!empty($this->component['component'])){
						// On vérifie les ACL
						$canFunction = 'can'.ucfirst($field->getACLLevel());
						$hasACL = ACL::$canFunction($this->component['component'], $this->component['id'], $cUser->getId());
					}else{
						$hasACL = true;
					}
					$field->display($hasACL, $userValue);
					//}
				}
			}
			// On affiche ensuite les champs masqués
			if (isset($this->fields['hidden'])){
				foreach ($this->fields['hidden'] as $field){
					$field->display(true, $userValue);
				}
			}

			// Et enfin les boutons
			foreach ($this->fields['buttons'] as $field){
				if (!empty($this->component['component'])){
					// On vérifie les ACL
					$canFunction = 'can'.ucfirst($field->getACLLevel());
					$hasACL = ACL::$canFunction($this->component['component'], $this->component['id'], $cUser->getId());
				}else{
					$hasACL = true;
				}
				$field->display($hasACL, $userValue);
			}

			foreach ($this->parameters as $parameter => $value){
				?>
				<input type="hidden" name="<?php echo $parameter; ?>" value="<?php echo $value; ?>">
				<?php
			}
			?>
		</form>
		<?php
	}

	/**
	 * @return array
	 */
	static public function getFieldTypes(){
		return self::$fieldTypes;
	}
} 