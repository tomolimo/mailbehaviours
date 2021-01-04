<?php

define ("PLUGIN_MAILBEHAVIOURS_VERSION", "1.0.1");

/**
 * Summary of plugin_init_mailbehaviours
 * Init the hooks of the plugins
 */
function plugin_init_mailbehaviours() {

   global $PLUGIN_HOOKS;

   Plugin::registerClass('PluginMailBehaviours', ['classname' => 'PluginMailBehaviours']);

   $PLUGIN_HOOKS['csrf_compliant']['mailbehaviours'] = true;

   $PLUGIN_HOOKS['pre_item_add']['mailbehaviours'] = [
      'Ticket'       => ['PluginMailBehaviours', 'plugin_pre_item_add_mailbehaviours'],
      'ITILFollowup' => ['PluginMailBehaviours', 'plugin_pre_item_add_mailbehaviours_followup']
   ];

   $PLUGIN_HOOKS['item_add']['mailbehaviours'] = [
      'Ticket' => ['PluginMailBehaviours', 'plugin_item_add_mailbehaviours']
   ];
}


/**
 * Summary of plugin_version_mailbehaviours
 * Get the name and the version of the plugin
 * @return array
 */
function plugin_version_mailbehaviours() {
   return [
      'name'         => __('Mail Behaviours'),
      'version'      => PLUGIN_MAILBEHAVIOURS_VERSION,
      'author'       => 'Olivier Moron',
      'license'      => 'GPLv2+',
      'homepage'     => 'https://github.com/tomolimo/mailbehaviours',
      'requirements' => [
         'glpi' => [
            'min' => '9.5',
            'max' => '9.6'
            ]
         ]
   ];
}


/**
 * Summary of plugin_mailbehaviours_check_prerequisites
 * check prerequisites before install : may print errors or add to message after redirect
 * @return bool
 */
function plugin_mailbehaviours_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.5', 'lt')
       && version_compare(GLPI_VERSION, '9.6', 'ge')) {
      echo "This plugin requires GLPI >= 9.5 and < 9.6";
      return false;
   } else {
      return true;
   }
}


/**
 * Summary of plugin_mailbehaviours_check_config
 * @return bool
 */
function plugin_mailbehaviours_check_config() {
   return true;
}

