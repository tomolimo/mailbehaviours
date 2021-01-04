<?php

/**
 * Summary of plugin_mailbehaviours_install
 * @return boolean
 */
function plugin_mailbehaviours_install() {

   return true;
}


/**
 * Summary of plugin_mailbehaviours_uninstall
 * @return boolean
 */
function plugin_mailbehaviours_uninstall() {

   // nothing to uninstall
   // do not delete table

   return true;
}


class PluginMailBehaviours {


   /**
    * Summary of plugin_pre_item_add_mailbehaviours_followup
    * @param mixed $parm
    */
   public static function plugin_pre_item_add_mailbehaviours_followup($parm) {
       global $DB;

      if (isset($parm->input['_mailgate'])) {
         // change requester if needed
         $locUser = new User();
         $users_id = self::getUsersID($parm->input['content'], "From");
         if (count($users_id) > 0) {
            if ($locUser->getFromDB($users_id[0])) {
               // set users_id
               $parm->input['users_id'] = $users_id[0];
            }
         }
      }
   }


   /**
    * Summary of getTextFromHtml
    * gets bare text content from HTML
    * deltes HTML entities, but <br>
    * @param mixed $str HTML input
    * @return string bare text
    */
   public static function getTextFromHtml($str) {
      $ret = Toolbox::unclean_html_cross_side_scripting_deep($str);
      $ret = preg_replace("/<(p|br|div)( [^>]*)?".">/i", "\n", $ret);
      $ret = preg_replace("/(&nbsp;| |\xC2\xA0)+/", " ", $ret);
      $ret = strip_tags($ret);
      $ret = html_entity_decode(html_entity_decode($ret, ENT_QUOTES));
      return $ret;
   }


   /**
    * Summary of getUsersID
    * Searches for ##From or ##CC if it exists, then try to find users_id from DB
    * @param string $str
    * @param string $search "from" or "CC"
    * @return array
    */
   public static function getUsersID(string $str, string $search) {
      global $DB;
      $results = [];

      $str = str_replace(['\n', '\r\n'], "\n", $str); // to be sure that \n (end of line) will not be confused with a \ in firstname
      $str = self::getTextFromHtml($str);

      // search for ##From (or ##CC) if it exists, then try to find real requester from DB
      $ptnUserFullName = '/##'.$search.'\s*:\s*(["\']?(?\'last\'[\w.\-\\\\\' ]+)[, ]\s*(?\'first\'[\w+.\-\\\\\' ]+))?.*?(?\'email\'[\w_.+\-]+@[\w\-]+\.[\w\-.]+)?\W*$/imu';

      if (preg_match_all($ptnUserFullName, $str, $matches, PREG_SET_ORDER) > 0) {
         // we found at least one ##From:
         // then we try to get its user id from DB
         // if an email has been found, then we try it
         // else we try with name and firstname in this order
         foreach ($matches as $match) {
            if (isset($match['email'])) {
               $where2 = ['glpi_useremails.email' => trim($match['email'])];
            } elseif (isset($match['last']) && isset($match['first'])) {
               $where2 = ['AND' => ['glpi_users.realname'         => $DB->escape(trim($match['last'])),
                                    'glpi_users.firstname'        => $DB->escape(trim($match['first'])),
                                    'glpi_useremails.is_default'  => 1
                                    ]];
            } else {
               continue;
            }
            $where2['AND']['glpi_users.is_active'] = 1;
            $where2['AND']['glpi_users.is_deleted'] = 0;
            $res = $DB->request([
               'SELECT'    => 'glpi_users.id',
               'FROM'      => 'glpi_users',
               'RIGHT JOIN'=> ['glpi_useremails' => ['FKEY' => ['glpi_useremails' => 'users_id', 'glpi_users' => 'id']]],
               'WHERE'     => $where2,
               'LIMIT'     => 1
               ]);

            if ($row = $res->next()) {
               $results[] = $row['id'];
            }
         }
      }

      return $results;
   }


   /**
    * Summary of getGroupsID
    * Searches for ##CC if it exists, then try to find groups_id from DB
    * @param string $str
    * @return array
    */
   public static function getGroupsID(string $str) {
      global $DB;
      $results = [];

      $str = str_replace(['\n', '\r\n'], "\n", $str); // to be sure that \n (end of line) will not be confused with a \ in firstname
      $str = self::getTextFromHtml($str);

      // search for ##CC if it exists, then try to find real requester from DB
      $ptnGroupName = "/##CC\s*:\s*([_a-z0-9-\\\\* ]+)$/imu";
      if (preg_match_all($ptnGroupName, $str, $matches, PREG_PATTERN_ORDER) > 0) {
         // we found at least one ##CC matching group naming convention:
         $locGroup = new Group;
         foreach ($matches[1] as $match) {
            // then try to get its group id from DB
            if ($locGroup->getFromDBByCrit( ['name' => trim($match)])) {
               $results[] = $locGroup->getID();
            }
         }
      }

      return $results;
   }


   /**
   * Summary of plugin_pre_item_add_mailbehaviours
   * @param mixed $parm
   * @return void
   */
   public static function plugin_pre_item_add_mailbehaviours($parm) {
      global $DB, $mailgate;

      if (isset($parm->input['_mailgate'])) {
         // this ticket have been created via email receiver.

         // change requester if needed
         // search for ##From if it exists, then try to find real requester from DB
         $users_id = self::getUsersID($parm->input['content'], "From");
         if (count($users_id) > 0) {
            $parm->input['users_id_recipient'] = $parm->input['_users_id_requester'];
            $parm->input['_users_id_requester'] = $users_id[0];

            // as we have changed the requester, then we must replay the Rules for assigning a ticket created through a mails receiver
            $mailcollector = new MailCollector();

            $rule_options['ticket']              = $parm->input;
            $rule_options['headers']             = $mailcollector->getHeaders($parm->input['_message']);
            $rule_options['mailcollector']       = $parm->input['_mailgate'];
            $rule_options['_users_id_requester'] = $parm->input['_users_id_requester'];
            $rulecollection                      = new RuleMailCollectorCollection();
            $output                              = $rulecollection->processAllRules([], [], $rule_options);

            // returns the new values in the input field
            foreach ($output as $key => $value) {
               $parm->input[$key] = $value;
            }
         }
      }
   }


   /**
   * Summary of plugin_item_add_mailbehaviours
   * @param mixed $parm
   */
   public static function plugin_item_add_mailbehaviours($parm) {
      if (isset($parm->input['_mailgate'])) {
         // this ticket have been created via email receiver.
         // add watchers if ##CC
         //self::addWatchers($parm, $parm->fields['content']);
         foreach (self::getUsersID($parm->fields['content'], "CC") as $users_id) {
            if (!$parm->isUser(CommonITILActor::OBSERVER, $users_id)) {
               // then we need to add this user as it is not yet in the observer list
               $locTicketUser = new Ticket_User;
               $locTicketUser->add([
                  'tickets_id' => $parm->getId(), 
                  'users_id'   => $users_id, 
                  'type'       => CommonITILActor::OBSERVER,
                  'use_notification' => 1
               ]);
               $parm->getFromDB($parm->getId());
            }
         }

         foreach (self::getGroupsID($parm->fields['content']) as $groups_id) {
            // add group in watcher list
            if (!$parm->isGroup(CommonITILActor::OBSERVER, $groups_id)) {
               // then we need to add this group as it is not yet in the observer list
               $locGroup_Ticket = new Group_Ticket;
               $locGroup_Ticket->add( [
                  'tickets_id' => $parm->getId(), 
                  'groups_id'  => $groups_id, 
                  'type'       => CommonITILActor::OBSERVER
               ]);
               $parm->getFromDB($parm->getId());
            }
         }
      }
   }


   ///**
   // * Summary of addWatchers
   // * @param Ticket $parm    a Ticket
   // * @param string $content content that will be analyzed
   // * @return void
   // */
   //public static function addWatchers($parm, $content) {
   //   // to be sure
   //   if ($parm->getType() == 'Ticket') {

   //      $content = str_replace(['\n', '\r\n'], "\n", $content);
   //      $content = self::getTextFromHtml($content);
   //      $ptnUserFullName = '/##CC\s*:\s*(["\']?(?\'last\'[\w.\-\\\\\' ]+)[, ]\s*(?\'first\'[\w+.\-\\\\\' ]+))?.*?(?\'email\'[\w_.+\-]+@[\w\-]+\.[\w\-.]+)?\W*$/imu';
   //      if (preg_match_all($ptnUserFullName, $content, $matches, PREG_PATTERN_ORDER) > 0) {
   //         // we found at least one ##CC matching user name convention: "Name, Firstname"
   //         for ($i=0; $i<count($matches[1]); $i++) {
   //            // then try to get its user id from DB
   //            //$locUser = self::getFromDBbyCompleteName( trim($matches[1][$i]).' '.trim($matches[2][$i]));
   //            $locUser = self::getFromDBbyCompleteName( trim($matches['last'][$i]).' '.trim($matches['first'][$i]));
   //            if ($locUser) {
   //               // add user in watcher list
   //               if (!$parm->isUser( CommonITILActor::OBSERVER, $locUser->getID())) {
   //                  // then we need to add this user as it is not yet in the observer list
   //                  $locTicketUser = new Ticket_User;
   //                  $locTicketUser->add( [ 'tickets_id' => $parm->getId(), 'users_id' => $locUser->getID(), 'type' => CommonITILActor::OBSERVER, 'use_notification' => 1] );
   //                  $parm->getFromDB($parm->getId());
   //               }
   //            }
   //         }
   //      }

   //      $locGroup = new Group;
   //      $ptnGroupName = "/##CC\s*:\s*([_a-z0-9-\\\\* ]+)$/imu";
   //      if (preg_match_all($ptnGroupName, $content, $matches, PREG_PATTERN_ORDER) > 0) {
   //         // we found at least one ##CC matching group name convention:
   //         for ($i=0; $i<count($matches[1]); $i++) {
   //            // then try to get its group id from DB
   //            if ($locGroup->getFromDBByCrit( ['name' => trim($matches[1][$i])])) {
   //               // add group in watcher list
   //               if (!$parm->isGroup( CommonITILActor::OBSERVER, $locGroup->getID())) {
   //                  // then we need to add this group as it is not yet in the observer list
   //                  $locGroup_Ticket = new Group_Ticket;
   //                  $locGroup_Ticket->add( [ 'tickets_id' => $parm->getId(), 'groups_id' => $locGroup->getID(), 'type' => CommonITILActor::OBSERVER] );
   //                  $parm->getFromDB($parm->getId());
   //               }
   //            }
   //         }
   //      }

   //   }
   //}


   ///**
   // * Summary of getFromDBbyCompleteName
   // * Retrieve an item from the database using its Lastname Firstname
   // * @param string $completename : family name + first name of the user ('lastname firstname')
   // * @return boolean|User a user if succeed else false
   // **/
   //public static function getFromDBbyCompleteName($completename) {
   //   global $DB;
   //   $user = new User();
   //   $res = $DB->request(
   //                  $user->getTable(),
   //                  [
   //                  'AND' => [
   //                     'is_active' => 1,
   //                     'is_deleted' => 0,
   //                     'RAW' => [
   //                        "CONCAT(realname, ' ', firstname)" => ['LIKE', $DB->escape($completename)]
   //                     ]
   //                  ]
   //                  ]);
   //   if ($res) {
   //      if ($res->numrows() != 1) {
   //         return false;
   //      }
   //      $user->fields = $res->next();
   //      if (is_array($user->fields) && count($user->fields)) {
   //         return $user;
   //      }
   //   }
   //   return false;
   //}
}

