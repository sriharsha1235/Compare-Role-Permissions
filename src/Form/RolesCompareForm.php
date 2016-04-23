<?php

/**
 * @file
 * Contains \Drupal\compare_role_permissions\Form\RolesCompareForm.
 */

namespace Drupal\compare_role_permissions\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RolesCompareForm.
 *
 * @package Drupal\compare_role_permissions\Form
 */
class RolesCompareForm extends FormBase {

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler) {
    $this->permissionHandler = $permission_handler;
    $this->roleStorage = $role_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('user.permissions'), $container->get('entity.manager')->getStorage('user_role'), $container->get('module_handler')
    );
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRolePermission($role_name = '') {
    return $this->roleStorage->load($role_name)->getPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'roles_compare_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select_role1'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select Role'),
      '#options' => user_role_names(),
      '#required' => TRUE,
    );
    $form['select_role2'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Select Role'),
      '#options' => user_role_names(),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );
    if ($form_state->isExecuted()) {
      $this->printRoleDiff($form, $form_state);
    }
    return $form;
  }

  /**
   * Helper function to print role differences
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function printRoleDiff(array &$form, FormStateInterface $form_state) {

    $role1 = $form_state->getValue('select_role1');
    $role2 = $form_state->getValue('select_role2');

    $role1_permissions = $this->getRolePermission($role1);
    $role2_permissions = $this->getRolePermission($role2);

    $role_diff = array_merge(array_diff($role2_permissions, $role1_permissions), array_diff($role1_permissions, $role2_permissions));
    if (!empty($role_diff)) {
      $permissions = $this->permissionHandler->getPermissions();
      $form['role_diff'] = array(
        '#type' => 'table',
        '#header' => array('Permission', $role1, $role2),
      );
      for ($i = 0; $i < count($role_diff); $i++) {
        $form['role_diff'][$i]['permission'] = array(
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="module_name">' . $this->moduleHandler->getName($permissions[$role_diff[$i]]['provider']) . '<br></span><span class="title">{{ title }}</span></div>',
          '#context' => array(
            'title' => $permissions[$role_diff[$i]]['title']
          ),
        );
        $role1_has = $role2_has = 'No';
        if (in_array($role_diff[$i], $role1_permissions)) {
          $role1_has = 'Yes';
        }
        if (in_array($role_diff[$i], $role2_permissions)) {
          $role2_has = 'Yes';
        }
        $form['role_diff'][$i][$role1] = array(
          '#markup' => $role1_has,
        );

        $form['role_diff'][$i][$role2] = array(
          '#markup' => $role2_has
        );
      }
    }
    else {
      $form['no_diff'] = array(
        '#markup' => 'Both the selected roles has same permissions'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
