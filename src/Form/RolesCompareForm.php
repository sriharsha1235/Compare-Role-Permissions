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
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler
  ) {
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
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRoleLabel($role_name = '') {
    return $this->roleStorage->load($role_name)->label();
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
      '#title' => $this->t('Role'),
      '#options' => user_role_names(),
      '#required' => TRUE,
    );
    $form['select_role2'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Role'),
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

    $role_diff = array_merge(
        array_diff($role2_permissions, $role1_permissions), array_diff($role1_permissions, $role2_permissions)
    );
    if (!empty($role_diff)) {
      $permissions = $this->permissionHandler->getPermissions();
      $form['role_diff'] = array(
        '#type' => 'table',
        '#header' => array('Module', 'Permission', $this->getRoleLabel($role1), $this->getRoleLabel($role2)),
      );

      for ($i = 0; $i < count($role_diff); $i++) {
        $module_proiver = $permissions[$role_diff[$i]]['provider'];
        $permission_module_list[$module_proiver][$i]['module'] = $this->moduleHandler->getName($permissions[$role_diff[$i]]['provider']);
        $permission_module_list[$module_proiver][$i]['permission'] = $permissions[$role_diff[$i]]['title'];
        $role1_has = $role2_has = 'No';
        if (in_array($role_diff[$i], $role1_permissions)) {
          $role1_has = 'Yes';
        }
        if (in_array($role_diff[$i], $role2_permissions)) {
          $role2_has = 'Yes';
        }
        $permission_module_list[$module_proiver][$i]['role1'] = $role1_has;
        $permission_module_list[$module_proiver][$i]['role2'] = $role2_has;
      }
      foreach ($permission_module_list as $provider => $module_list) {
        foreach ($module_list as $key => $permission_list) {
          $form['role_diff'][$key]['module'] = array(
            '#markup' => $permission_list['module']
          );
          $form['role_diff'][$key]['permission'] = array(
            '#type' => 'inline_template',
            '#template' => '<div class="permission"><span class="title">{{ title }}</span></div>',
            '#context' => array(
              'title' => $permission_list['permission']
            ),
          );
          $form['role_diff'][$key][$role1] = array(
            '#markup' => $permission_list['role1'],
          );

          $form['role_diff'][$key][$role2] = array(
            '#markup' => $permission_list['role2']
          );
        }
      }
    }
    else {
      $form['no_diff'] = array(
        '#markup' => 'Both the selected roles has similar permissions'
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
