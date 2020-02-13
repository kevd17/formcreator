<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @copyright Copyright © 2011 - 2019 Teclib'
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

abstract class PluginFormcreatorTargetBase extends CommonDBChild implements
PluginFormcreatorExportableInterface,
PluginFormcreatorTargetInterface
{
   static public $itemtype = PluginFormcreatorForm::class;
   static public $items_id = 'plugin_formcreator_forms_id';

   /** @var array $requesters requester actors of the target */
   protected $requesters;

   /** @var array $observers watcher actors of the target */
   protected $observers;

   /** @var array $assigned assigned actors of the target */
   protected $assigned;

   /** @var array $assignedSuppliers assigned suppliers actors of the target */
   protected $assignedSuppliers;

   /** @var array $requesterGroups requester groups of the target */
   protected $requesterGroups;

   /** @var array $observerGroups watcher groups of the target */
   protected $observerGroups;

   /** @var array $assignedGroups assigned groups of the target */
   protected $assignedGroups;

   protected $attachedDocuments = [];

   protected $form = null;

   /** @var boolean $skipCreateActors Flag to disable creation of actors after creation of the item */
   protected $skipCreateActors = false;

   abstract public function export($remove_uuid = false);

   abstract public function save(PluginFormcreatorFormAnswer $formanswer);

   /**
    * Gets an instance object for the relation between the target itemtype
    * and an user
    *
    * @return CommonDBTM
    */
   abstract protected function getItem_User();

   /**
    * Gets an instance object for the relation between the target itemtype
    * and a group
    *
    * @return CommonDBTM
    */
   abstract protected function getItem_Group();

   /**
    * Gets an instance object for the relation between the target itemtype
    * and supplier
    *
    * @return CommonDBTM
    */
   abstract protected function getItem_Supplier();

   /**
    * Gets an instance object for the relation between the target itemtype
    * and an object of any itemtype
    *
    * @return CommonDBTM
    */
   abstract protected function getItem_Item();

   /**
    * Gets the class name of the target itemtype
    *
    * @return string
    */
   abstract protected function getTargetItemtypeName();

   /**
    * get an instance of the itemtype storing the actors if the target
    *
    * @return PluginFormcreatorTarget_Actor
    */
   abstract public function getItem_Actor();

   /**
    * Get the query criterias to query the ITIL categories
    * for the target
    *
    * @return array
    */
   abstract protected function getCategoryFilter();

   /**
    * get fields containing tags for target generation
    * the tags are replaced when target is  generated
    * with label of questions and answers to questions
    *
    * @return array field names used as templates
    */
   abstract protected function getTaggableFields();

   const DUE_DATE_RULE_NONE = 1;
   const DUE_DATE_RULE_ANSWER = 2;
   const DUE_DATE_RULE_TICKET = 3;
   const DUE_DATE_RULE_CALC = 4;

   const DUE_DATE_PERIOD_MINUTE = 1;
   const DUE_DATE_PERIOD_HOUR = 2;
   const DUE_DATE_PERIOD_DAY = 3;
   const DUE_DATE_PERIOD_MONTH = 4;

   const URGENCY_RULE_NONE = 1;
   const URGENCY_RULE_SPECIFIC = 2;
   const URGENCY_RULE_ANSWER = 3;

   const DESTINATION_ENTITY_CURRENT = 1;
   const DESTINATION_ENTITY_REQUESTER = 2;
   const DESTINATION_ENTITY_REQUESTER_DYN_FIRST = 3;
   const DESTINATION_ENTITY_REQUESTER_DYN_LAST = 4;
   const DESTINATION_ENTITY_FORM = 5;
   const DESTINATION_ENTITY_VALIDATOR = 6;
   const DESTINATION_ENTITY_SPECIFIC = 7;
   const DESTINATION_ENTITY_USER = 8;
   const DESTINATION_ENTITY_ENTITY = 9;

   const TAG_TYPE_NONE = 1;
   const TAG_TYPE_QUESTIONS = 2;
   const TAG_TYPE_SPECIFICS = 3;
   const TAG_TYPE_QUESTIONS_AND_SPECIFIC = 4;
   const TAG_TYPE_QUESTIONS_OR_SPECIFIC = 5;

   const CATEGORY_RULE_NONE = 1;
   const CATEGORY_RULE_SPECIFIC = 2;
   const CATEGORY_RULE_ANSWER = 3;

   const LOCATION_RULE_NONE = 1;
   const LOCATION_RULE_SPECIFIC = 2;
   const LOCATION_RULE_ANSWER = 3;

   public static function getEnumDestinationEntity() {
      return [
         self::DESTINATION_ENTITY_CURRENT   => __('Current active entity', 'formcreator'),
         self::DESTINATION_ENTITY_REQUESTER => __("Default requester user's entity", 'formcreator'),
         self::DESTINATION_ENTITY_REQUESTER_DYN_FIRST => __("First dynamic requester user's entity (alphabetical)", 'formcreator'),
         self::DESTINATION_ENTITY_REQUESTER_DYN_LAST => __("Last dynamic requester user's entity (alphabetical)", 'formcreator'),
         self::DESTINATION_ENTITY_FORM      => __('The form entity', 'formcreator'),
         self::DESTINATION_ENTITY_VALIDATOR => __('Default entity of the validator', 'formcreator'),
         self::DESTINATION_ENTITY_SPECIFIC  => __('Specific entity', 'formcreator'),
         self::DESTINATION_ENTITY_USER      => __('Default entity of a user type question answer', 'formcreator'),
         self::DESTINATION_ENTITY_ENTITY    => __('From a GLPI object > Entity type question answer', 'formcreator'),
      ];
   }

   public static function getEnumTagType() {
      return [
         self::TAG_TYPE_NONE                   => __('None'),
         self::TAG_TYPE_QUESTIONS              => __('Tags from questions', 'formcreator'),
         self::TAG_TYPE_SPECIFICS              => __('Specific tags', 'formcreator'),
         self::TAG_TYPE_QUESTIONS_AND_SPECIFIC => __('Tags from questions and specific tags', 'formcreator'),
         self::TAG_TYPE_QUESTIONS_OR_SPECIFIC  => __('Tags from questions or specific tags', 'formcreator')
      ];
   }

   public static function getEnumDueDateRule() {
      return [
         self::DUE_DATE_RULE_ANSWER => __('equals to the answer to the question', 'formcreator'),
         self::DUE_DATE_RULE_TICKET => __('calculated from the ticket creation date', 'formcreator'),
         self::DUE_DATE_RULE_CALC => __('calculated from the answer to the question', 'formcreator'),
      ];
   }

   public static function getEnumUrgencyRule() {
      return [
         self::URGENCY_RULE_NONE      => __('Urgency from template or Medium', 'formcreator'),
         self::URGENCY_RULE_SPECIFIC  => __('Specific urgency', 'formcreator'),
         self::URGENCY_RULE_ANSWER    => __('Equals to the answer to the question', 'formcreator'),
      ];
   }

   public static function getEnumCategoryRule() {
      return [
         self::CATEGORY_RULE_NONE      => __('Category from template or none', 'formcreator'),
         self::CATEGORY_RULE_SPECIFIC  => __('Specific category', 'formcreator'),
         self::CATEGORY_RULE_ANSWER    => __('Equals to the answer to the question', 'formcreator'),
      ];
   }

   public static function getEnumLocationRule() {
      return [
         self::LOCATION_RULE_NONE      => __('Location from template or none', 'formcreator'),
         self::LOCATION_RULE_SPECIFIC  => __('Specific location', 'formcreator'),
         self::LOCATION_RULE_ANSWER    => __('Equals to the answer to the question', 'formcreator'),
      ];
   }

   /**
    * get the associated form
    */
   public function getForm() {
      if ($this->form === null) {
         $form = new PluginFormcreatorForm();
         if (!$form->getFromDB($this->fields[PluginFormcreatorForm::getForeignKeyField()])) {
            return null;
         }
         $this->form = $form;
      }

      return $this->form;
   }

   /**
    * Set the entity of the target
    *
    * @param array $data input data of the target
    * @param PluginFormcreatorFormAnswer $formanswer
    * @param integer $requesters_id ID of the requester of the answers
    * @return integer ID of the entity where the target must be generated
    */
   protected function setTargetEntity($data, PluginFormcreatorFormAnswer $formanswer, $requesters_id) {
      global $DB;

      $entityId = 0;
      $entityFk = Entity::getForeignKeyField();
      switch ($this->fields['destination_entity']) {
         // Requester's entity
         case self::DESTINATION_ENTITY_CURRENT :
            $entityId = $formanswer->fields[$entityFk];
            break;

         case self::DESTINATION_ENTITY_REQUESTER :
            $userObj = new User();
            $userObj->getFromDB($requesters_id);
            $entityId = $userObj->fields[$entityFk];
            break;

         // Requester's first dynamic entity
         case self::DESTINATION_ENTITY_REQUESTER_DYN_FIRST :
            $order_entities = "glpi_profiles.name ASC";
         case self::DESTINATION_ENTITY_REQUESTER_DYN_LAST :
            if (!isset($order_entities)) {
               $order_entities = "glpi_profiles.name DESC";
            }
            $profileUserTable = Profile_User::getTable();
            $profileTable = Profile::getTable();
            $profileFk  = Profile::getForeignKeyField();
            $res_entities = $DB->request([
               'SELECT' => [
                  $profileUserTable => [Entity::getForeignKeyField()]
               ],
               'FROM' => $profileUserTable,
               'LEFT JOIN' => [
                  $profileTable => [
                     'FKEY' => [
                        $profileTable => 'id',
                        $profileUserTable => $profileFk
                     ]
                  ]
               ],
               'WHERE' => [
                  "$profileUserTable.users_id" => $requesters_id
               ],
               'ORDER' => [
                  "$profileUserTable.is_dynamic DSC",
                  $order_entities
               ]
            ]);

            $data_entities = [];
            foreach ($res_entities as $entity) {
               $data_entities[] = $entity;
            }
            $first_entity = array_shift($data_entities);
            $entityId = $first_entity[$entityFk];
            break;

         // Specific entity
         case self::DESTINATION_ENTITY_SPECIFIC :
            $entityId = $this->fields['destination_entity_value'];
            break;

         // The form entity
         case self::DESTINATION_ENTITY_FORM :
            $entityId = $formanswer->getForm()->fields[$entityFk];
            break;

         // The validator entity
         case self::DESTINATION_ENTITY_VALIDATOR :
            $userObj = new User();
            $userObj->getFromDB($formanswer->fields['users_id_validator']);
            $entityId = $userObj->fields[$entityFk];
            break;

         // Default entity of a user from the answer of a user's type question
         case self::DESTINATION_ENTITY_USER :
            $user = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->fields['id'],
                  'plugin_formcreator_questions_id'   => $this->fields['destination_entity_value'],
               ]
            ])->next();
            $user_id = $user['answer'];

            if ($user_id > 0) {
               $userObj = new User();
               $userObj->getFromDB($user_id);
               $entityId = $userObj->fields[$entityFk];
            }
            break;

         // Entity from the answer of an entity's type question
         case self::DESTINATION_ENTITY_ENTITY :
            $entity = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->fields['id'],
                  'plugin_formcreator_questions_id'   => $this->fields['destination_entity_value'],
               ]
            ])->next();
            $entityId = $entity['answer'];
            break;
      }

      $data[$entityFk] = $entityId;
      return $data;
   }

   protected function setTargetCategory($data, $formanswer) {
      global $DB;

      switch ($this->fields['category_rule']) {
         case self::CATEGORY_RULE_ANSWER:
            $category = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->fields['id'],
                  'plugin_formcreator_questions_id'   => $this->fields['category_question']
               ]
            ])->next();
            $category = $category['answer'];
            break;
         case self::CATEGORY_RULE_SPECIFIC:
            $category = $this->fields['category_question'];
            break;
         default:
            $category = null;
      }
      if ($category !== null) {
         $data['itilcategories_id'] = $category;
      }

      return $data;
   }

   protected function setTargetUrgency($data, $formanswer) {
      global $DB;

      $urgency = null;
      switch ($this->fields['urgency_rule']) {
         case PluginFormcreatorTargetBase::URGENCY_RULE_ANSWER:
            $urgency = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->getID(),
                  'plugin_formcreator_questions_id'   => $this->fields['urgency_question']
               ]
            ])->next();
            $urgency = $urgency['answer'];
            break;
         case PluginFormcreatorTargetBase::URGENCY_RULE_SPECIFIC:
            $urgency = $this->fields['urgency_question'];
            break;
      }
      if (!is_null($urgency)) {
         $data['urgency'] = $urgency;
      }

      return $data;
   }

   /**
    * find all actors and prepare data for the ticket being created
    */
   protected function prepareActors(PluginFormcreatorForm $form, PluginFormcreatorFormAnswer $formanswer) {
      global $DB;

      $target_actor = $this->getItem_Actor();
      $foreignKey   = $this->getForeignKeyField();

      $rows = $DB->request([
         'FROM'   => $target_actor::getTable(),
         'WHERE'  => [
            $foreignKey => $this->getID(),
         ]
      ]);
      foreach ($rows as $actor) {
         // If actor type is validator and if the form doesn't have a validator, continue to other actors
         if ($actor['actor_type'] == PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR && !$form->fields['validation_required']) {
            continue;
         }

         switch ($actor['actor_type']) {
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR :
               $userIds = [$formanswer->fields['requester_id']];
               $notify  = $actor['use_notification'];
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR :
               $userIds = [$_SESSION['glpiID']];
               $notify  = $actor['use_notification'];
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPPLIER :
               $userIds = [$actor['actor_value']];
               $notify  = $actor['use_notification'];
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_SUPPLIER :
               $answer  = new PluginFormcreatorAnswer();
               $actorValue = $actor['actor_value'];
               $formanswerId = $formanswer->getID();
               $answer->getFromDBByCrit([
                  'AND' => [
                     'plugin_formcreator_questions_id'     => $actorValue,
                     'plugin_formcreator_formanswers_id' => $formanswerId
                  ]
               ]);

               if ($answer->isNewItem()) {
                  continue 2;
               } else {
                  $userIds = [$answer->fields['answer']];
               }
               $notify  = $actor['use_notification'];
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS:
               $answer  = new PluginFormcreatorAnswer();
               $actorValue = $actor['actor_value'];
               $formanswerId = $formanswer->getID();
               $answer->getFromDBByCrit([
                  'AND' => [
                     'plugin_formcreator_questions_id'     => $actorValue,
                     'plugin_formcreator_formanswers_id' => $formanswerId
                  ]
               ]);

               if ($answer->isNewItem()) {
                  continue 2;
               } else {
                  $userIds = json_decode($answer->fields['answer'], JSON_OBJECT_AS_ARRAY);
               }
               $notify = $actor['use_notification'];
               break;
         }

         switch ($actor['actor_type']) {
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS:
               foreach ($userIds as $userIdOrEmail) {
                  $this->addActor($actor['actor_role'], $userIdOrEmail, $notify);
               }
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP :
               foreach ($userIds as $groupId) {
                  $this->addGroupActor($actor['actor_role'], $groupId);
               }
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPPLIER :
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_SUPPLIER :
               foreach ($userIds as $userId) {
                  $this->addActor(PluginFormcreatorTarget_Actor::ACTOR_ROLE_SUPPLIER, $userId, $notify);
               }
               break;
         }
      }
   }

   /**
    * Adds an user to the given actor role (requester, observer assigned or supplier)
    *
    * @param string $role role of the user
    * @param string $user user ID or email address for anonymous users
    * @param boolean $notify true to enable notification for the actor
    * @return boolean true on success, false on error
    */
   protected function addActor($role, $user, $notify) {
      if (filter_var($user, FILTER_VALIDATE_EMAIL) !== false) {
         $userId = 0;
         $alternativeEmail = $user;
      } else {
         $userId = (int) $user;
         $alternativeEmail = '';
         if ($userId == '0') {
            // there is no actor
            return false;
         }
      }

      $actorType = null;
      $actorTypeNotif = null;
      switch ($role) {
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_REQUESTER:
            $actorType = &$this->requesters['_users_id_requester'];
            $actorTypeNotif = &$this->requesters['_users_id_requester_notif'];
            break;
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_OBSERVER:
            $actorType = &$this->observers['_users_id_observer'];
            $actorTypeNotif = &$this->observers['_users_id_observer_notif'];
            break;
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_ASSIGNED :
            $actorType = &$this->assigned['_users_id_assign'];
            $actorTypeNotif = &$this->assigned['_users_id_assign_notif'];
            break;
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_SUPPLIER :
            $actorType = &$this->assignedSuppliers['_suppliers_id_assign'];
            $actorTypeNotif = &$this->assignedSuppliers['_suppliers_id_assign_notif'];
            break;
         default:
            return false;
      }

      $actorKey = array_search($userId, $actorType);
      if ($actorKey === false) {
         // Add the actor
         $actorType[]                      = $userId;
         $actorTypeNotif['use_notification'][]  = ($notify == true);
         $actorTypeNotif['alternative_email'][] = $alternativeEmail;
      } else {
         // New actor settings takes precedence
         $actorType[$actorKey] = $userId;
         $actorTypeNotif['use_notification'][$actorKey]  = ($notify == true);
         $actorTypeNotif['alternative_email'][$actorKey] = $alternativeEmail;
      }

      return true;
   }

   /**
    * Adds a group to the given actor role
    *
    * @param string $role Role of the group
    * @param string $group Group ID
    * @return boolean true on sucess, false on error
    */
   protected function addGroupActor($role, $group) {
      $actorType = null;
      switch ($role) {
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_REQUESTER:
            $actorType = &$this->requesterGroups['_groups_id_requester'];
            break;
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_OBSERVER :
            $actorType = &$this->observerGroups['_groups_id_observer'];
            break;
         case PluginFormcreatorTarget_Actor::ACTOR_ROLE_ASSIGNED :
            $actorType = &$this->assignedGroups['_groups_id_assign'];
            break;
         default:
            return false;
      }

      $actorKey = array_search($group, $actorType);
      if ($actorKey !== false) {
         return false;
      }

      // Add the group actor
      $actorType[] = $group;

      return true;
   }

   /**
    * Attach documents of the answer to the target
    */
   protected function attachDocument($formAnswerId, $itemtype, $targetID) {
      global $CFG_GLPI;

      $docItem = new Document_Item();
      if (count($this->attachedDocuments) <= 0) {
         return;
      }

      foreach ($this->attachedDocuments as $documentID => $dummy) {
         $docItem->add([
            'documents_id' => $documentID,
            'itemtype'     => $itemtype,
            'items_id'     => $targetID,
         ]);
         if ($itemtype === Ticket::class) {
            $document = new Document();
            $documentCategoryFk = DocumentCategory::getForeignKeyField();
            $document->update([
               'id' => $documentID,
               $documentCategoryFk => $CFG_GLPI["documentcategories_id_forticket"],
            ]);
         }
      }
   }

   public function addAttachedDocument($documentId) {
      $this->attachedDocuments[$documentId] = true;
   }

   protected function showDestinationEntitySetings($rand) {
      echo '<tr class="line1">';
      echo '<td width="15%">' . __('Destination entity') . '</td>';
      echo '<td width="25%">';
      Dropdown::showFromArray(
         'destination_entity',
         self::getEnumDestinationEntity(),
         [
            'value'     => $this->fields['destination_entity'],
            'on_change' => "plugin_formcreator_change_entity($rand)",
            'rand'      => $rand,
         ]
      );

      echo Html::scriptBlock("plugin_formcreator_change_entity($rand)");
      echo '</td>';
      echo '<td width="15%">';
      echo '<span id="entity_specific_title" style="display: none">' . _n('Entity', 'Entities', 1) . '</span>';
      echo '<span id="entity_user_title" style="display: none">' . __('User type question', 'formcreator') . '</span>';
      echo '<span id="entity_entity_title" style="display: none">' . __('Entity type question', 'formcreator') . '</span>';
      echo '</td>';
      echo '<td width="25%">';

      echo '<div id="entity_specific_value" style="display: none">';
      Entity::dropdown([
         'name' => '_destination_entity_value_specific',
         'value' => $this->fields['destination_entity_value'],
      ]);
      echo '</div>';

      echo '<div id="entity_user_value" style="display: none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['glpiselect'],
            'values' => User::class,
         ],
         '_destination_entity_value_user',
         [
            'value' => $this->fields['destination_entity_value']
         ]
      );
      echo '</div>';

      echo '<div id="entity_entity_value" style="display: none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['glpiselect'],
            'values' => Entity::class,
         ],
         '_destination_entity_value_entity',
         [
            'value' => $this->fields['destination_entity_value']
         ]
      );
      echo '</div>';
      echo '</td>';
      echo '</tr>';
   }

   protected function showTemplateSettings($rand) {
      echo '<td width="15%">' . _n('Ticket template', 'Ticket templates', 1) . '</td>';
      echo '<td width="25%">';
      Dropdown::show('TicketTemplate', [
         'name'  => 'tickettemplates_id',
         'value' => $this->fields['tickettemplates_id']
      ]);
      echo '</td>';
   }

   protected  function showDueDateSettings(PluginFormcreatorForm $form, $rand) {
      echo '<td width="15%">' . __('Time to resolve') . '</td>';
      echo '<td width="45%">';

      // Due date type selection
      Dropdown::showFromArray('due_date_rule', self::getEnumDueDateRule(),
         [
            'value'     => $this->fields['due_date_rule'],
            'on_change' => 'plugin_formcreator_formcreatorChangeDueDate(this.value)',
            'display_emptychoice' => true
         ]
      );

      $questionTable = PluginFormcreatorQuestion::getTable();
      $questions = (new PluginFormcreatorQuestion)->getQuestionsFromForm(
         $this->getForm()->getID(),
         [
            "$questionTable.fieldtype" => ['date', 'datetime'],
         ]
      );
      $questions_list = [];
      foreach ($questions as $question) {
         $questions_list[$question->getID()] = $question->fields['name'];
      }
      // List questions
      if ($this->fields['due_date_rule'] != PluginFormcreatorTargetBase::DUE_DATE_RULE_ANSWER
            && $this->fields['due_date_rule'] != 'calcul') {
         echo '<div id="due_date_questions" style="display:none">';
      } else {
         echo '<div id="due_date_questions">';
      }
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['date', 'datetime'],
         ],
         'due_date_question',
         [
            'value' => $this->fields['due_date_question']
         ]
      );
      echo '</div>';

      if ($this->fields['due_date_rule'] != '2'
            && $this->fields['due_date_rule'] != '3') {
         echo '<div id="due_date_time" style="display:none">';
      } else {
         echo '<div id="due_date_time">';
      }
      Dropdown::showNumber("due_date_value", [
         'value' => $this->fields['due_date_value'],
         'min'   => -30,
         'max'   => 30
      ]);
      Dropdown::showFromArray('due_date_period', [
         PluginFormcreatorTargetBase::DUE_DATE_PERIOD_MINUTE => _n('Minute', 'Minutes', 2),
         PluginFormcreatorTargetBase::DUE_DATE_PERIOD_HOUR   => _n('Hour', 'Hours', 2),
         PluginFormcreatorTargetBase::DUE_DATE_PERIOD_DAY    => _n('Day', 'Days', 2),
         PluginFormcreatorTargetBase::DUE_DATE_PERIOD_MONTH  => _n('Month', 'Months', 2),
      ], [
         'value' => $this->fields['due_date_period']
      ]);
      echo '</div>';
      echo '</td>';
   }

   protected function showCategorySettings(PluginFormcreatorForm $form, $rand) {
      echo '<tr class="line0">';
      echo '<td width="15%">' . __('Ticket category', 'formcreator') . '</td>';
      echo '<td width="25%">';
      Dropdown::showFromArray(
         'category_rule',
         static::getEnumCategoryRule(),
         [
            'value'     => $this->fields['category_rule'],
            'on_change' => "plugin_formcreator_changeCategory($rand)",
            'rand'      => $rand,
         ]
      );
      echo Html::scriptBlock("plugin_formcreator_changeCategory($rand);");
      echo '</td>';
      echo '<td width="15%">';
      echo '<span id="category_specific_title" style="display: none">' . __('Category', 'formcreator') . '</span>';
      echo '<span id="category_question_title" style="display: none">' . __('Question', 'formcreator') . '</span>';
      echo '</td>';
      echo '<td width="25%">';
      echo '<div id="category_question_value" style="display: none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['dropdown'],
         ],
         '_category_question',
         [
            $this->fields['category_question']
         ]
      );
      echo '</div>';
      echo '<div id="category_specific_value" style="display: none">';
      ITILCategory::dropdown([
         'name'      => '_category_specific',
         'value'     => $this->fields["category_question"],
         'condition' => $this->getCategoryFilter(),
      ]);
      echo '</div>';
      echo '</td>';
      echo '</tr>';
   }

   protected function showUrgencySettings(PluginFormcreatorForm $form, $rand) {
      echo '<tr class="line0">';
      echo '<td width="15%">' . __('Urgency') . '</td>';
      echo '<td width="45%">';
      Dropdown::showFromArray('urgency_rule', static::getEnumUrgencyRule(), [
         'value'                 => $this->fields['urgency_rule'],
         'on_change'             => "plugin_formcreator_changeUrgency($rand)",
         'rand'                  => $rand
      ]);
      echo Html::scriptBlock("plugin_formcreator_changeUrgency($rand);");
      echo '</td>';
      echo '<td width="15%">';
      echo '<span id="urgency_question_title" style="display: none">' . __('Question', 'formcreator') . '</span>';
      echo '<span id="urgency_specific_title" style="display: none">' . __('Urgency ', 'formcreator') . '</span>';
      echo '</td>';
      echo '<td width="25%">';

      echo '<div id="urgency_specific_value" style="display: none">';
      Ticket::dropdownUrgency([
         'name' => '_urgency_specific',
         'value' => $this->fields["urgency_question"],
      ]);
      echo '</div>';
      echo '<div id="urgency_question_value" style="display: none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['urgency'],
         ],
         '_urgency_question',
         [
            'value' => $this->fields['urgency_question']
         ]
      );
      echo '</div>';
      echo '</td>';
      echo '</tr>';
   }

   protected function showPluginTagsSettings(PluginFormcreatorForm $form, $rand) {
      global $DB;

      $plugin = new Plugin();
      if ($plugin->isInstalled('tag') && $plugin->isActivated('tag')) {
         echo '<tr class="line1">';
         echo '<td width="15%">' . __('Ticket tags', 'formcreator') . '</td>';
         echo '<td width="25%">';
         Dropdown::showFromArray('tag_type', self::getEnumTagType(),
            [
               'value'     => $this->fields['tag_type'],
               'on_change' => 'change_tag_type()',
               'rand'      => $rand,
            ]
         );

         $tagTypeQuestions = self::TAG_TYPE_QUESTIONS;
         $tagTypeSpecifics = self::TAG_TYPE_SPECIFICS;
         $tagTypeQuestionAndSpecific = self::TAG_TYPE_QUESTIONS_AND_SPECIFIC;
         $tagTypeQuestinOrSpecific = self::TAG_TYPE_QUESTIONS_OR_SPECIFIC;
         $script = <<<SCRIPT
            function change_tag_type() {
               $('#tag_question_title').hide();
               $('#tag_specific_title').hide();
               $('#tag_question_value').hide();
               $('#tag_specific_value').hide();

               switch($('#dropdown_tag_type$rand').val()) {
                  case '$tagTypeQuestions' :
                     $('#tag_question_title').show();
                     $('#tag_question_value').show();
                     break;
                  case '$tagTypeSpecifics' :
                     $('#tag_specific_title').show();
                     $('#tag_specific_value').show();
                     break;
                  case '$tagTypeQuestionAndSpecific' :
                  case '$tagTypeQuestinOrSpecific' :
                     $('#tag_question_title').show();
                     $('#tag_specific_title').show();
                     $('#tag_question_value').show();
                     $('#tag_specific_value').show();
                     break;
               }
            }
            change_tag_type();
SCRIPT;

         echo Html::scriptBlock($script);
         echo '</td>';
         echo '<td width="15%">';
         echo '<div id="tag_question_title" style="display: none">' . _n('Question', 'Questions', 2, 'formcreator') . '</div>';
         echo '<div id="tag_specific_title" style="display: none">' . __('Tags', 'tag') . '</div>';
         echo '</td>';
         echo '<td width="25%">';

         // Tag questions
         echo '<div id="tag_question_value" style="display: none">';
         PluginFormcreatorQuestion::dropdownForForm(
            $this->getForm()->getID(),
            [
               'fieldtype' => ['tag'],
            ],
            '_tag_questions',
            [
               'values' => explode(',', $this->fields['tag_questions']),
               'multiple' => true,
            ]
         );
         echo '</div>';

         // Specific tags
         echo '<div id="tag_specific_value" style="display: none">';

         $result = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => PluginTagTag::getTable(),
            'WHERE'  => [
               'AND' => [
                  'OR' => [
                     ['type_menu' => ['LIKE', '%"' . $this->getTargetItemtypeName() . '"%']],
                     ['type_menu' => ['LIKE', '%"0"%']]
                  ],
                  getEntitiesRestrictCriteria(PluginTagTag::getTable()),
               ]
            ]
         ]);
         $values = [];
         foreach ($result AS $id => $data) {
            $values[$id] = $data['name'];
         }

         Dropdown::showFromArray('_tag_specifics', $values, [
            'values'   => explode(',', $this->fields['tag_specifics']),
            'comments' => false,
            'rand'     => $rand,
            'multiple' => true,
         ]);
         echo '</div>';
         echo '</td>';
         echo '</tr>';
      }
   }

   protected function showActorsSettings() {
      global $DB;

      // Get available questions for actors lists
      $itemActor = $this->getItem_Actor();
      $itemActorTable = $itemActor::getTable();
      $actors = [
         PluginFormcreatorTarget_Actor::ACTOR_ROLE_REQUESTER => [],
         PluginFormcreatorTarget_Actor::ACTOR_ROLE_OBSERVER => [],
         PluginFormcreatorTarget_Actor::ACTOR_ROLE_ASSIGNED => [],
      ];
      $result = $DB->request([
         'SELECT' => ['id', 'actor_role', 'actor_type', 'actor_value', 'use_notification'],
         'FROM' => $itemActorTable,
         'WHERE' => [
            self::getForeignKeyField() => [$this->getID()]
         ]
      ]);
      foreach ($result as $actor) {
         $actors[$actor['actor_role']][$actor['id']] = [
            'actor_type'       => $actor['actor_type'],
            'actor_value'      => $actor['actor_value'],
            'use_notification' => $actor['use_notification'],
         ];
      }

      $img_user     = '<img src="../../../pics/users.png" alt="' . __('User') . '" title="' . __('User') . '" width="20" />';
      $img_group    = '<img src="../../../pics/groupes.png" alt="' . __('Group') . '" title="' . __('Group') . '" width="20" />';
      $img_supplier = '<img src="../../../pics/supplier.png" alt="' . __('Supplier') . '" title="' . __('Supplier') . '" width="20" />';
      $img_mail     = '<img src="../pics/email.png" alt="' . __('Yes') . '" title="' . __('Email followup') . ' ' . __('Yes') . '" />';
      $img_nomail   = '<img src="../pics/email-no.png" alt="' . __('No') . '" title="' . __('Email followup') . ' ' . __('No') . '" />';

      echo '<table class="tab_cadre_fixe">';

      echo '<tr><th colspan="3">' . __('Actors', 'formcreator') . '</th></tr>';

      echo '<tr>';

      // Requester header
      echo '<th width="33%">';
      echo _n('Requester', 'Requesters', 1) . ' &nbsp;';
      echo '<img title="Ajouter" alt="Ajouter" onclick="plugin_formcreator_displayRequesterForm()" class="pointer"
               id="btn_add_requester" src="../../../pics/add_dropdown.png">';
      echo '<img title="Annuler" alt="Annuler" onclick="plugin_formcreator_hideRequesterForm()" class="pointer"
               id="btn_cancel_requester" src="../../../pics/delete.png" style="display:none">';
      echo '</th>';

      // Watcher header
      echo '<th width="34%">';
      echo _n('Watcher', 'Watchers', 1) . ' &nbsp;';
      echo '<img title="Ajouter" alt="Ajouter" onclick="plugin_formcreator_displayWatcherForm()" class="pointer"
               id="btn_add_watcher" src="../../../pics/add_dropdown.png">';
      echo '<img title="Annuler" alt="Annuler" onclick="plugin_formcreator_hideWatcherForm()" class="pointer"
               id="btn_cancel_watcher" src="../../../pics/delete.png" style="display:none">';
      echo '</th>';

      // Assigned header
      echo '<th width="33%">';
      echo __('Assigned to') . ' &nbsp;';
      echo '<img title="Ajouter" alt="Ajouter" onclick="plugin_formcreator_displayAssignedForm()" class="pointer"
               id="btn_add_assigned" src="../../../pics/add_dropdown.png">';
      echo '<img title="Annuler" alt="Annuler" onclick="plugin_formcreator_hideAssignedForm()" class="pointer"
               id="btn_cancel_assigned" src="../../../pics/delete.png" style="display:none">';
      echo '</th>';

      echo '</tr>';

      echo '<tr>';

      // Requester
      echo '<td valign="top">';

      // => Add requester form
      echo '<form name="form_target" id="form_add_requester" method="post" style="display:none" action="'
           . static::getFormURL() . '">';

      $dropdownItems = ['' => Dropdown::EMPTY_VALUE] + $itemActor::getEnumActorType();
      unset($dropdownItems['supplier']);
      unset($dropdownItems['question_supplier']);
      Dropdown::showFromArray(
         'actor_type',
         $dropdownItems, [
            'on_change'         => 'plugin_formcreator_ChangeActorRequester(this.value)'
         ]
      );

      echo '<div id="block_requester_user" style="display:none">';
      User::dropdown([
         'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON,
         'right' => 'all',
         'all'   => 0,
      ]);
      echo '</div>';

      echo '<div id="block_requester_group" style="display:none">';
      Group::dropdown([
         'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP,
      ]);
      echo '</div>';

      echo '<div id="block_requester_question_user" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'OR' => [
               'AND' => [
                  'fieldtype' => ['glpiselect'],
                  'values' => User::class,
               ],
               [
                  'fieldtype' => ['email'],
               ]
            ],
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_requester_question_group" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['glpiselect'],
            'values' => Group::class,
         ],
         'actor_value_question_person',
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_requester_question_actors" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['actor'],
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div>';
      echo __('Email followup');
      Dropdown::showYesNo('use_notification', 1);
      echo '</div>';

      echo '<p align="center">';
      echo '<input type="hidden" name="id" value="' . $this->getID() . '" />';
      echo '<input type="hidden" name="actor_role" value="' . PluginFormcreatorTarget_Actor::ACTOR_ROLE_REQUESTER . '" />';
      echo '<input type="submit" value="' . __('Add') . '" class="submit_button" />';
      echo '</p>';

      echo "<hr>";

      Html::closeForm();

      // => List of saved requesters
      foreach ($actors[PluginFormcreatorTarget_Actor::ACTOR_ROLE_REQUESTER] as $id => $values) {
         echo '<div>';
         switch ($values['actor_type']) {
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR :
               echo $img_user . ' <b>' . __('Form requester', 'formcreator') . '</b>';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR :
               echo $img_user . ' <b>' . __('Form validator', 'formcreator') . '</b>';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON :
               $user = new User();
               $user->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('User') . ' </b> "' . $user->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Person from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP :
               $group = new Group();
               $group->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Group') . ' </b> "' . $group->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_group . ' <b>' . __('Group from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS:
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Actors from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
         }
         echo $values['use_notification'] ? ' ' . $img_mail . ' ' : ' ' . $img_nomail . ' ';
         echo self::getDeleteImage($id);
         echo '</div>';
      }

      echo '</td>';

      // Observer
      echo '<td valign="top">';

      // => Add observer form
      echo '<form name="form_target" id="form_add_watcher" method="post" style="display:none" action="'
           . static::getFormURL() . '">';

      $dropdownItems = [''  => Dropdown::EMPTY_VALUE] + $itemActor::getEnumActorType();
      unset($dropdownItems['supplier']);
      unset($dropdownItems['question_supplier']);
      Dropdown::showFromArray('actor_type',
         $dropdownItems, ['on_change' => 'plugin_formcreator_ChangeActorWatcher(this.value)']
      );

      echo '<div id="block_watcher_user" style="display:none">';
      User::dropdown([
         'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON,
         'right' => 'all',
         'all'   => 0,
      ]);
      echo '</div>';

      echo '<div id="block_watcher_group" style="display:none">';
      Group::dropdown([
         'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP,
      ]);
      echo '</div>';

      echo '<div id="block_watcher_question_user" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'OR' => [
               'AND' => [
                  'fieldtype' => ['glpiselect'],
                  'values' => User::class,
               ],
               [
                  'fieldtype' => ['email'],
               ]
            ],
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_watcher_question_group" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['glpiselect'],
            'values' => Group::class,
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_watcher_question_actors" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['actor'],
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div>';
      echo __('Email followup');
      Dropdown::showYesNo('use_notification', 1);
      echo '</div>';

      echo '<p align="center">';
      echo '<input type="hidden" name="id" value="' . $this->getID() . '" />';
      echo '<input type="hidden" name="actor_role" value="' . PluginFormcreatorTarget_Actor::ACTOR_ROLE_OBSERVER . '" />';
      echo '<input type="submit" value="' . __('Add') . '" class="submit_button" />';
      echo '</p>';

      echo "<hr>";

      Html::closeForm();

      // => List of saved observers
      foreach ($actors[PluginFormcreatorTarget_Actor::ACTOR_ROLE_OBSERVER] as $id => $values) {
         echo '<div>';
         switch ($values['actor_type']) {
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR :
               echo $img_user . ' <b>' . __('Form requester', 'formcreator') . '</b>';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR :
               echo $img_user . ' <b>' . __('Form validator', 'formcreator') . '</b>';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON :
               $user = new User();
               $user->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('User') . ' </b> "' . $user->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Person from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP :
               $group = new Group();
               $group->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Group') . ' </b> "' . $group->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_group . ' <b>' . __('Group from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Actors from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
         }
         echo $values['use_notification'] ? ' ' . $img_mail . ' ' : ' ' . $img_nomail . ' ';
         echo self::getDeleteImage($id);
         echo '</div>';
      }

      echo '</td>';

      // Assigned to
      echo '<td valign="top">';

      // => Add assigned to form
      echo '<form name="form_target" id="form_add_assigned" method="post" style="display:none" action="'
            . static::getFormURL() . '">';

      $dropdownItems = [''  => Dropdown::EMPTY_VALUE] + $itemActor::getEnumActorType();
      Dropdown::showFromArray(
         'actor_type',
         $dropdownItems, [
           'on_change'         => 'plugin_formcreator_ChangeActorAssigned(this.value)'
         ]
      );

      echo '<div id="block_assigned_user" style="display:none">';
      User::dropdown([
            'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON,
            'right' => 'all',
            'all'   => 0,
      ]);
      echo '</div>';

      echo '<div id="block_assigned_group" style="display:none">';
      Group::dropdown([
         'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP,
      ]);
      echo '</div>';

      echo '<div id="block_assigned_supplier" style="display:none">';
      Supplier::dropdown([
         'name' => 'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPPLIER,
      ]);
      echo '</div>';

      echo '<div id="block_assigned_question_user" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'OR' => [
               'AND' => [
                  'fieldtype' => ['glpiselect'],
                  'values' => User::class,
               ],
               [
                  'fieldtype' => ['email'],
               ]
            ],
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_assigned_question_group" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['glpiselect'],
            'values' => Group::class,
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_assigned_question_actors" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['actor'],
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div id="block_assigned_question_supplier" style="display:none">';
      PluginFormcreatorQuestion::dropdownForForm(
         $this->getForm()->getID(),
         [
            'fieldtype' => ['glpiselect'],
            'values' => Supplier::class,
         ],
         'actor_value_' . PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_SUPPLIER,
         [
            'value' => 0
         ]
      );
      echo '</div>';

      echo '<div>';
      echo __('Email followup');
      Dropdown::showYesNo('use_notification', 1);
      echo '</div>';

      echo '<p align="center">';
      echo '<input type="hidden" name="id" value="' . $this->getID() . '" />';
      echo '<input type="hidden" name="actor_role" value="' . PluginFormcreatorTarget_Actor::ACTOR_ROLE_ASSIGNED . '" />';
      echo '<input type="submit" value="' . __('Add') . '" class="submit_button" />';
      echo '</p>';

      echo "<hr>";

      Html::closeForm();

      // => List of saved assigned to
      foreach ($actors[PluginFormcreatorTarget_Actor::ACTOR_ROLE_ASSIGNED] as $id => $values) {
         echo '<div>';
         switch ($values['actor_type']) {
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR :
               echo $img_user . ' <b>' . __('Form requester', 'formcreator') . '</b>';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR :
               echo $img_user . ' <b>' . __('Form validator', 'formcreator') . '</b>';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON :
               $user = new User();
               $user->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('User') . ' </b> "' . $user->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Person from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP :
               $group = new Group();
               $group->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Group') . ' </b> "' . $group->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_group . ' <b>' . __('Group from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_user . ' <b>' . __('Actors from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPPLIER :
               $supplier = new Supplier();
               $supplier->getFromDB($values['actor_value']);
               echo $img_supplier . ' <b>' . __('Supplier') . ' </b> "' . $supplier->getName() . '"';
               break;
            case PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_SUPPLIER :
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($values['actor_value']);
               echo $img_supplier . ' <b>' . __('Supplier from the question', 'formcreator')
               . '</b> "' . $question->getName() . '"';
               break;
         }
         echo $values['use_notification'] ? ' ' . $img_mail . ' ' : ' ' . $img_nomail . ' ';
         echo self::getDeleteImage($id);
         echo '</div>';
      }

      echo '</td>';

      echo '</tr>';

      echo '</table>';
   }

   protected function showLocationSettings(PluginFormcreatorForm $form, $rand) {
      global $DB;

      echo '<tr class="line0">';
      echo '<td width="15%">' . __('Location') . '</td>';
      echo '<td width="45%">';
      Dropdown::showFromArray('location_rule', static::getEnumLocationRule(), [
         'value'                 => $this->fields['location_rule'],
         'on_change'             => "plugin_formcreator_change_location($rand)",
         'rand'                  => $rand
      ]);

      echo Html::scriptBlock("plugin_formcreator_change_location($rand)");
      echo '</td>';
      echo '<td width="15%">';
      echo '<span id="location_question_title" style="display: none">' . __('Question', 'formcreator') . '</span>';
      echo '<span id="location_specific_title" style="display: none">' . __('Location ', 'formcreator') . '</span>';
      echo '</td>';
      echo '<td width="25%">';

      echo '<div id="location_specific_value" style="display: none">';
      Location::dropdown([
         'name' => '_location_specific',
         'value' => $this->fields["location_question"],
      ]);
      echo '</div>';
      echo '<div id="location_question_value" style="display: none">';
      // select all user questions (GLPI Object)
      $questionTable = PluginFormcreatorQuestion::getTable();
      $sectionTable = PluginFormcreatorSection::getTable();
      $sectionFk = PluginFormcreatorSection::getForeignKeyField();
      $formFk = PluginFormcreatorForm::getForeignKeyField();
      $result = $DB->request([
         'SELECT' => [
            $questionTable => ['id', 'name', 'values'],
            $sectionTable => ['name as sname'],
         ],
         'FROM' => $questionTable,
         'INNER JOIN' => [
            $sectionTable => [
               'FKEY' => [
                  $sectionTable => 'id',
                  $questionTable => $sectionFk
               ]
            ],
         ],
         'WHERE' => [
            "$formFk" => $this->getForm()->getID(),
            "$questionTable.fieldtype" => 'dropdown'
         ]
      ]);
      $users_questions = [];
      foreach ($result as $question) {
         $decodedValues = json_decode($question['values'], JSON_OBJECT_AS_ARRAY);
         if (isset($decodedValues['itemtype']) && $decodedValues['itemtype'] === 'Location') {
            $users_questions[$question['sname']][$question['id']] = $question['name'];
         }
      }
      Dropdown::showFromArray('_location_question', $users_questions, [
         'value' => $this->fields['location_question'],
      ]);

      echo '</div>';
      echo '</td>';
      echo '</tr>';
   }

   /**
    * Sets the time to resolve of the target object
    *
    * @param array $data data of the target object
    * @param PluginFormcreatorFormAnswer $formanswer    Answers previously saved
    * @return array updated data of the target object
    */
   protected function setTargetDueDate($data, PluginFormcreatorFormAnswer $formanswer) {
      global $DB;

      $answer  = new PluginFormcreatorAnswer();
      if ($this->fields['due_date_question'] !== null) {
         $request = [
            'FROM' => $answer::getTable(),
            'WHERE' => [
               'AND' => [
                  $formanswer::getForeignKeyField() => $formanswer->fields['id'],
                  PluginFormcreatorQuestion::getForeignKeyField() => $this->fields['due_date_question'],
               ],
            ],
         ];
         $iterator = $DB->request($request);
         if ($iterator->count() > 0) {
            $iterator->rewind();
            $date   = $iterator->current();
         }
      } else {
         $date = null;
      }

      $period = '';
      switch ($this->fields['due_date_period']) {
         case self::DUE_DATE_PERIOD_MINUTE:
            $period = "minute";
            break;
         case self::DUE_DATE_PERIOD_HOUR:
            $period = "hour";
            break;
         case self::DUE_DATE_PERIOD_DAY:
            $period = "day";
            break;
         case self::DUE_DATE_PERIOD_MONTH:
            $period = "month";
            break;
      }
      $str    = "+" . $this->fields['due_date_value'] . " $period";

      switch ($this->fields['due_date_rule']) {
         case PluginFormcreatorTargetBase::DUE_DATE_RULE_ANSWER:
            $due_date = $date['answer'];
            break;
         case PluginFormcreatorTargetBase::DUE_DATE_RULE_TICKET:
            $due_date = date('Y-m-d H:i:s', strtotime($str));
            break;
         case PluginFormcreatorTargetBase::DUE_DATE_RULE_CALC:
            $due_date = date('Y-m-d H:i:s', strtotime($date['answer'] . " " . $str));
            break;
         default:
            $due_date = null;
            break;
      }
      if (!is_null($due_date)) {
         $data['time_to_resolve'] = $due_date;
      }

      return $data;
   }

   public function prepareInputForAdd($input) {
      if (isset($input['_skip_create_actors']) && $input['_skip_create_actors']) {
         $this->skipCreateActors = true;
      }

      $formFk = PluginFormcreatorForm::getForeignKeyField();
      if (!isset($input[$formFk])) {
         Session::addMEssageAfterRedirect(__('A target must be associated to a form.', 'formcreator'));
         return false;
      }
      $form = new PluginFormcreatorForm();
      if (!$form->getFromDB((int) $input[$formFk])) {
         Session::addMEssageAfterRedirect(__('A target must be associated to an existing form.', 'formcreator'));
         return false;
      }

      if (!isset($input['target_name']) || strlen($input['target_name']) < 1) {
         $input['target_name'] = $input['name'];
      }

      // Set default content
      if (!isset($input['content']) || isset($input['content']) && empty($input['content'])) {
         $input['content'] = '##FULLFORM##';
      }

      // generate a unique id
      if (!isset($input['uuid'])
          || empty($input['uuid'])) {
         $input['uuid'] = plugin_formcreator_getUuid();
      }

      return $input;
   }

   public function prepareInputForUpdate($input) {
      if (!isset($input['_skip_checks'])
            || !$input['_skip_checks']) {
         if (isset($input['name'])
            && empty($input['name'])) {
            Session::addMessageAfterRedirect(__('The name cannot be empty!', 'formcreator'), false, ERROR);
            return [];
         }

         // - content is required
         if (strlen($input['content']) < 1) {
            Session::addMessageAfterRedirect(__('The description cannot be empty!', 'formcreator'), false, ERROR);
            return [];
         }
      }

      if (isset($input['_skip_create_actors']) && $input['_skip_create_actors']) {
         $this->skipCreateActors = true;
      }

      // generate a uniq id
      if (!isset($input['uuid'])
          || empty($input['uuid'])) {
         $input['uuid'] = plugin_formcreator_getUuid();
      }

      return $input;
   }

   public function post_addItem() {
      if ($this->skipCreateActors) {
         return;
      }

      $target_actor = $this->getItem_Actor();
      $myFk = self::getForeignKeyField();
      $target_actor->add([
         $myFk                 => $this->getID(),
         'actor_role'          => PluginFormcreatorTarget_Actor::ACTOR_ROLE_REQUESTER,
         'actor_type'          => PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR,
         'use_notification'    => '1',
      ]);
      $target_actor = $this->getItem_Actor();
      $target_actor->add([
         $myFk                 => $this->getID(),
         'actor_role'          => PluginFormcreatorTarget_Actor::ACTOR_ROLE_OBSERVER,
         'actor_type'          => PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR,
         'use_notification'    => '1',
      ]);
   }

   protected static function getDeleteImage($id) {
      global $CFG_GLPI;

      $link  = ' &nbsp;<a href="' . $CFG_GLPI['root_doc'] . '/plugins/formcreator/front/targetticket.form.php?delete_actor=' . $id . '">';
      $link .= '<img src="../../../pics/delete.png" alt="' . __('Delete') . '" title="' . __('Delete') . '" />';
      $link .= '</a>';
      return $link;
   }

   public function pre_purgeItem() {
      $myFk = static::getForeignKeyField();
      // delete actors related to this instance
      $targetItemActor = $this->getItem_Actor();
      if (!$targetItemActor->deleteByCriteria([$myFk => $this->getID()])) {
         $this->input = false;
         return false;
      }

      return true;
   }

   /**
    * Prepare the template of the target
    *
    * @param string $template
    * @param PluginFormcreatorFormAnswer $formAnswer form answer to render
    * @param boolean $richText Disable rich text output
    * @return string
    */
   protected function prepareTemplate($template, PluginFormcreatorFormAnswer $formAnswer, $richText = false) {
      if (strpos($template, '##FULLFORM##') !== false) {
         $template = str_replace('##FULLFORM##', $formAnswer->getFullForm($richText), $template);
      }

      if ($richText) {
         $template = str_replace(['<p>', '</p>'], ['<div>', '</div>'], $template);
         $template = Html::entities_deep($template);
      }

      return $template;
   }

   public function showTagsList() {
      echo '<table class="tab_cadre_fixe">';

      echo '<tr><th colspan="5">' . __('List of available tags') . '</th></tr>';
      echo '<tr>';
      echo '<th width="40%" colspan="2">' . _n('Question', 'Questions', 1, 'formcreator') . '</th>';
      echo '<th width="20%">' . __('Title') . '</th>';
      echo '<th width="20%">' . _n('Answer', 'Answers', 1, 'formcreator') . '</th>';
      echo '<th width="20%">' . _n('Section', 'Sections', 1, 'formcreator') . '</th>';
      echo '</tr>';

      echo '<tr class="line0">';
      echo '<td colspan="2"><strong>' . __('Full form', 'formcreator') . '</strong></td>';
      echo '<td align="center">-</td>';
      echo '<td align="center"><strong>##FULLFORM##</strong></td>';
      echo '<td align="center">-</td>';
      echo '</tr>';

      $question = new PluginFormcreatorQuestion();
      $formFk   = PluginFormcreatorForm::getForeignKeyField();
      $result = $question->getQuestionsFromFormBySection($this->fields[$formFk]);
      $i = 0;
      foreach ($result as $sectionName => $questions) {
         foreach ($questions as $questionId => $questionName) {
            $i++;
            echo '<tr class="line' . ($i % 2) . '">';
            echo '<td colspan="2">' . $questionName . '</td>';
            echo '<td align="center">##question_' . $questionId . '##</td>';
            echo '<td align="center">##answer_' . $questionId . '##</td>';
            echo '<td align="center">' . $sectionName . '</td>';
            echo '</tr>';
         }
      }

      echo '</table>';
   }

   /**
    * Associate tags to the target item
    *
    * @param PluginFormcreatorFormanswer $formanswer the source formanswer
    * @param integer $targetId ID of the generated target
    * @return void
    */
   protected function saveTags(PluginFormcreatorFormanswer $formanswer, $targetId) {
      global $DB;

      // Add tag if presents
      $plugin = new Plugin();
      if ($plugin->isActivated('tag')) {
         $tagObj = new PluginTagTagItem();
         $tags   = [];

         // Add question tags
         if (($this->fields['tag_type'] == self::TAG_TYPE_QUESTIONS
               || $this->fields['tag_type'] == self::TAG_TYPE_QUESTIONS_AND_SPECIFIC
               || $this->fields['tag_type'] == self::TAG_TYPE_QUESTIONS_OR_SPECIFIC)
               && (!empty($this->fields['tag_questions']))) {
            $formAnswerFk = PluginFormcreatorFormAnswer::getForeignKeyField();
            $questionFk = PluginFormcreatorQuestion::getForeignKeyField();
            $result = $DB->request([
               'SELECT' => ['plugin_formcreator_questions_id', 'answer'],
               'FROM' => PluginFormcreatorAnswer::getTable(),
               'WHERE' => [
                  $formAnswerFk => [(int) $formanswer->fields['id']],
                  $questionFk => $this->fields['tag_questions']
               ],
            ]);
            foreach ($result as $line) {
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($line['plugin_formcreator_questions_id']);
               $field = PluginFormcreatorFields::getFieldInstance(
                  $question->fields['fieldtype'],
                  $question
               );
               $field->deserializeValue($line['answer']);
               $tab = $field->getRawValue();
               if (is_integer($tab)) {
                  $tab = [$tab];
               }
               if (is_array($tab)) {
                  $tags = array_merge($tags, $tab);
               }
            }
         }

         // Add specific tags
         if ($this->fields['tag_type'] == self::TAG_TYPE_SPECIFICS
                     || $this->fields['tag_type'] == self::TAG_TYPE_QUESTIONS_AND_SPECIFIC
                     || ($this->fields['tag_type'] == self::TAG_TYPE_QUESTIONS_OR_SPECIFIC && empty($tags))
                     && (!empty($this->fields['tag_specifics']))) {

            $tags = array_merge($tags, explode(',', $this->fields['tag_specifics']));
         }

         $tags = array_unique($tags);

         // Save tags in DB
         foreach ($tags as $tag) {
            $tagObj->add([
               'plugin_tag_tags_id' => $tag,
               'items_id'           => $targetId,
               'itemtype'           => $this->getTargetItemtypeName(),
            ]);
         }
      }
   }

   /**
    * Converts tags in template fields from ID to UUID.
    * Used for export into JSON
    *
    * @return array all fields of the object wih converted template fields
    */
   protected function convertTags($input) {
      $question = new PluginFormcreatorQuestion();
      $questions = $question->getQuestionsFromForm($this->getForm()->getID());

      $taggableFields = $this->getTaggableFields();

      // Prepare array of search / replace
      $ids = [];
      $uuids = [];
      foreach ($questions as $question) {
         $id      = $question->fields['id'];
         $uuid    = $question->fields['uuid'];
         $ids[]   = "##question_$id##";
         $uuids[] = "##question_$uuid##";
         $ids[]   = "##answer_$id##";
         $uuids[] = "##answer_$uuid##";
      }

      // Replace for each field with tags
      foreach ($taggableFields as $field) {
         $content = $this->fields[$field];
         $content = str_replace($ids, $uuids, $content);
         $content = str_replace($ids, $uuids, $content);
         $input[$field] = $content;
      }

      return $input;
   }
}
