<?php
/*
-------------------------------------------------------------------------
MailBehaviours plugin for GLPI
Copyright (C) 2020-2025 by Raynet SAS a company of A.Raymond Network.
https://www.araymond.com/
-------------------------------------------------------------------------
LICENSE

This file is part of MailBehaviours plugin for GLPI.

This file is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
 */

define ("PLUGIN_MAILBEHAVIOURS_VERSION", "2.1.2");
define ("PLUGIN_MAILBEHAVIOURS_MIN_GLPI", "10.0.18");
define ("PLUGIN_MAILBEHAVIOURS_MAX_GLPI", "10.1");
/**
 * Summary of plugin_init_mailbehaviours
 * Init the hooks of the plugins
 */
function plugin_init_mailbehaviours() {

   global $PLUGIN_HOOKS;

   Plugin::registerClass('PluginMailBehaviours');

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
            'min' => PLUGIN_MAILBEHAVIOURS_MIN_GLPI,
            'max' => PLUGIN_MAILBEHAVIOURS_MAX_GLPI
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
   if (version_compare(GLPI_VERSION, PLUGIN_MAILBEHAVIOURS_MIN_GLPI, 'lt')
       && version_compare(GLPI_VERSION, PLUGIN_MAILBEHAVIOURS_MAX_GLPI, 'ge')) {
      echo "This plugin requires GLPI >= " . PLUGIN_MAILBEHAVIOURS_MIN_GLPI . "and < " . PLUGIN_MAILBEHAVIOURS_MAX_GLPI;
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

