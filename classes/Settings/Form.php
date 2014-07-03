<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 14/04/14
 * Time: 08:57
 */

namespace Settings;
use Components\Help;
use Users\ACL;

/**
 * Gestion des formulaires
 * Class Form
 *
 * @package Settings
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
					$this->displayField($field, $userValue, $hasACL);
					//}
				}
			}
			// On affiche ensuite les champs masqués
			if (isset($this->fields['hidden'])){
				foreach ($this->fields['hidden'] as $field){
					$this->displayField($field, $userValue);
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
				$this->displayField($field, $userValue, $hasACL);
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
	 * Affiche un champ de formulaire
	 *
	 * @param Field $field
	 * @param bool  $userValue Récupérer les valeurs utilisateurs au lieu des valeurs globales
	 * @param bool  $enabled Champ modifiable (suivant les ACL, on désactive le champ si l'utilisateur n'a pas les autorisations requises)
	 */
	protected function displayField(Field $field, $userValue = false, $enabled = true){
		$data = $field->getData();
		$value = ($userValue and !is_null($field->getUserValue())) ? $field->getUserValue() : $field->getValue(false);
		//var_dump($field);
		switch ($field->getType()){
			case 'dbTable':
				global $db;
				$dbData = $db->get($value);
				?>
				<h3><?php echo $field->getData()['desc']; ?>  <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></h3>
				<table class="table table-responsive<?php echo ' '.$field->getClass(); ?>">
					<thead>
						<tr class="tr_dbTable_header">
							<?php
							foreach ($field->getData()['fields'] as $subFieldArgs){
								if (!isset($subFieldArgs['show']) or $subFieldArgs['show']){
									?><th><?php echo (isset($subFieldArgs['label']) ? $subFieldArgs['label'] : $subFieldArgs['comment']); ?></th><?php
								}
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						if (!empty($dbData)){
							$i = 99999;
							foreach ($dbData as $dbItem){
								$id = (isset($dbItem->id)) ? $dbItem->id : $i;
								$i++;
								?><tr id="tr_dbTable_<?php echo $field->getName(); ?>_<?php echo $id; ?>" class="tr_dbTable"><?php
								foreach ($field->getData()['fields'] as $subField => $subFieldArgs){
									if (!isset($subFieldArgs['show']) or $subFieldArgs['show']){
										?><td><input type="text" class="form-control" name="dbTable_<?php echo $field->getName(); ?>_<?php echo $subFieldArgs['type']; ?>_<?php echo $subField; ?>_<?php echo $id; ?>" value="<?php echo $dbItem->$subField; ?>"></td><?php
									}
								}
								?></tr><?php
							}
						}
						?>
						<tr id="<?php echo $field->getName(); ?>_new_tr">
							<?php
							foreach ($field->getData()['fields'] as $subField => $subFieldArgs){
								if (!isset($subFieldArgs['show']) or $subFieldArgs['show']){
									?><td><input type="text" class="form-control" name="dbTable_<?php echo $field->getName(); ?>_<?php echo $subFieldArgs['type']; ?>_<?php echo $subField; ?>_new"></td><?php
								}
							}
							?>
						</tr>
					</tbody>
				</table>
				<noscript><span class="help-block">Pour supprimer une ligne, il vous suffit d'effacer toutes les valeurs contenues dans celle-ci.</span></noscript>
				<?php
				break;
			case 'string':
			case 'int':
			case 'float':
			case 'date':
			case 'list':
				?>
				<div class="form-group">
					<label for="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>"><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label>
					<input type="text" class="form-control<?php echo ' '.$field->getClass(); ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" <?php if ($field->getPlaceholder() != '') echo 'placeholder="'.$field->getPlaceholder().'"'; ?> value="<?php echo $value; ?>" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?> <?php if (isset($data['autocomplete']) and !$data['autocomplete']) echo 'autocomplete="off"' ;?>>
				</div>
				<?php
				break;
			case 'array':
				$arraySerialize = (isset($field->getData()['serialize'])) ? $field->getData()['serialize'] : false;
				?>
				<div class="form-group">
					<label for="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>"><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label>
					<textarea class="form-control<?php echo ' '.$field->getClass(); ?>" rows="<?php echo count($value); ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" <?php if ($field->getPlaceholder() != '') echo 'placeholder="'.$field->getPlaceholder().'"'; ?> <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>><?php echo implode(PHP_EOL, $value); ?></textarea>
					<input type="hidden" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_serialize" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_serialize" value="<?php echo $arraySerialize ?>">
					<p class="help-block">Utilisation : Un item par ligne</p>
				</div>
				<?php
				break;
			case 'file':
				?>
				<div class="form-group">
					<label for="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>"><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label>
					<input type="file" class="form-control<?php echo ' '.$field->getClass(); ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" <?php if ($field->getPlaceholder() != '') echo 'placeholder="'.$field->getPlaceholder().'"'; ?> value="<?php echo $value; ?>" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
				</div>
				<?php
				break;
			case 'hidden':
				?>
				<input type="hidden" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" value="<?php echo $value; ?>" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
				<?php
				break;
			case 'bool':
			case 'boolean':
				// Gestion des Switchs
				$dataAttr = '';
				if (isset($field->getData()['switch']) and $field->getData()['switch']){
					if (isset($data['onText']))   $dataAttr .= ' data-on-text="'.$data['onText'].'"';
					if (isset($data['offText']))  $dataAttr .= ' data-off-text="'.$data['offText'].'"';
					if (isset($data['onColor']) and   in_array($data['onColor'],  array('primary', 'info', 'success', 'warning', 'danger', 'default'))) $dataAttr .= ' data-on-color="'.$data['onColor'].'"';
					if (isset($data['offColor']) and  in_array($data['offColor'], array('primary', 'info', 'success', 'warning', 'danger', 'default'))) $dataAttr .= ' data-off-color="'.$data['offColor'].'"';
					if (isset($data['size']) and in_array($data['size'], array('mini', 'small', 'normal', 'large'))) $dataAttr .= ' data-size="'.$data['size'].'"';
				}
				if (isset($data['labelPosition']) and $data['labelPosition'] == 'left'){
					?>
					<div class="form-group">
						<label for="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>">
							<?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?>
						</label>
						<input type="checkbox" class="form-control <?php if ($field->getData()['switch']) echo 'checkboxSwitch'; ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_checkbox" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_checkbox" value="1" <?php if ($value === true or $value == 'true') echo 'checked'; ?> <?php if (!empty($dataAttr)) echo $dataAttr; ?> <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
						<input type="hidden" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_hidden" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_hidden" value="0" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
					</div>
				<?php
				}else{
					?>
					<div class="checkbox">
						<label>
							<input type="checkbox" class="<?php if (isset($field->getData()['switch']) and $field->getData()['switch']) echo 'checkboxSwitch'; ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_checkbox" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_checkbox" value="1" <?php if ($value === true or $value == 'true') echo 'checked'; ?> <?php if (!empty($dataAttr)) echo $dataAttr; ?> <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
							<input type="hidden" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_hidden" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>_hidden" value="0" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
							<?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?>
						</label>
					</div>
					<?php
				}
				break;
			case 'checkboxList':
				$i = 0;
				$defaultChecked = (isset($field->getData()['defaultChecked'])) ? $field->getData()['defaultChecked'] : null;
				?><label><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label><?php
				foreach ($field->getData()['choices'] as $choice => $label){
					$i++;
					?>
					<div class="checkbox<?php echo ' '.$field->getClass(); ?>" id="checkboxList_<?php echo $choice; ?>">
						<label>
							<input id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName().'_'.$i; ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>[]" type="checkbox" value="<?php echo $choice; ?>" <?php if (is_array($value) and in_array($choice, $value) or ($defaultChecked === $value or $defaultChecked == 'all' and empty($value))) echo 'checked'; ?> <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
							<?php echo $label; ?>
						</label>
					</div>
				<?php
				}
				break;
			case 'radio':
				$i = 0;
				?><label><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label><?php
				foreach ($field->getData()['choices'] as $choice => $label){
					$i++;
					?>
					<div class="radio">
					  <label>
					    <input id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName().'_'.$i; ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" type="radio" value="<?php echo $choice; ?>" <?php if ($value == $choice) echo 'checked'; ?> <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
							<?php echo $label; ?>
					  </label>
					</div>
					<?php
				}
				break;
			case 'text':
				?>
				<div class="form-group">
					<label for="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>"><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label>
					<textarea class="form-control<?php echo ' '.$field->getClass(); ?>" rows="<?php echo (isset($data['rows'])) ? $data['rows'] : '5'; ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" <?php if ($field->getPlaceholder() != '') echo 'placeholder="'.$field->getPlaceholder().'"'; ?> <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>><?php echo $value; ?></textarea>
				</div>
				<?php
				break;
			case 'button':
				?>
				<button type="submit" class="btn btn-default<?php echo ' '.$field->getClass(); ?>" id="<?php echo $field->getName(); ?>" name="<?php echo $field->getName(); ?>" value="<?php echo $value; ?>" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>><?php echo $field->getLabel(); ?></button>
				<?php
				break;
			case 'linkButton':
				?>
				<a class="btn btn-default<?php echo ' '.$field->getClass(); ?>" id="<?php echo $field->getName(); ?>"  <?php if($field->getHelp() != '') echo 'title="'.$field->getHelp().'"'; ?> name="<?php echo $field->getName(); ?>" href="<?php echo $value; ?>" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>><?php echo $field->getLabel(); ?></a>
				<?php
				break;
			case 'select':
				?>
				<div class="form-group">
					<label for="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>"><?php echo $field->getLabel(); ?> <?php if($field->getHelp() != '') Help::iconHelp($field->getHelp()); ?></label>
					<select class="form-control<?php echo ' '.$field->getClass(); ?>" id="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" name="field_<?php echo $field->getType(); ?>_<?php echo $field->getName(); ?>" <?php if ($field->getDisabled() or !$enabled) echo 'disabled'; ?>>
						<?php
						if (isset($data['addEmpty']) and $data['addEmpty']){
							?><option></option><?php
						}
						foreach ($data['choices'] as $choice => $label){
							?><option value="<?php echo $choice; ?>" <?php if ($value == $choice) echo 'selected'; ?>><?php echo $label; ?></option><?php
						}
						?>
					</select>
				</div>
				<?php
		}
	}

	/**
	 * @return array
	 */
	static public function getFieldTypes(){
		return self::$fieldTypes;
	}
} 