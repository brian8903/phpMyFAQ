<?php

/**
 * AJAX: handling of Ajax group calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-04-06
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$groupId = Filter::filterInput(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

if ($user->perm->checkRight($user->getUserId(), 'add_user') ||
    $user->perm->checkRight($user->getUserId(), 'edit_user') ||
    $user->perm->checkRight($user->getUserId(), 'delete_user') ||
    $user->perm->checkRight($user->getUserId(), 'editgroup')) {

    // pass the user id of the current user so it'll check which group he belongs to
    $groupList = ($user->perm instanceof MediumPermission) ? $user->perm->getAllGroups($user->getUserId()) : [];
    $userList = $user->getAllUsers(true, false);

    if (!$faqConfig->get('main.enableCategoryRestrictions')) {
        $user = new User($faqConfig);
        $groupList = ($user->perm instanceof MediumPermission) ? $user->perm->getAllGroups() : [];
    }

    // Returns all groups
    if ('get_all_groups' == $ajaxAction) {
        $groups = [];
        foreach ($groupList as $groupId) {
            $data = $user->perm->getGroupData($groupId);
            $groups[] = array(
                'group_id' => $data['group_id'],
                'name' => $data['name'],
            );
        }
        $http->sendJsonWithHeaders($groups);
    }

    // Return the group data
    if ('get_group_data' == $ajaxAction) {
        $http->sendJsonWithHeaders($user->perm->getGroupData($groupId));
    }

    // Return the group rights
    if ('get_group_rights' == $ajaxAction) {
        $http->sendJsonWithHeaders($user->perm->getGroupRights($groupId));
    }

    // Return all users
    if ('get_all_users' == $ajaxAction) {
        $users = [];
        foreach ($userList as $singleUser) {
            $user->getUserById($singleUser, true);
            $users[] = array(
                'user_id' => $user->getUserId(),
                'login' => $user->getLogin(),
            );
        }
        $http->sendJsonWithHeaders($users);
    }

    // Returns all group members
    if ('get_all_members' == $ajaxAction) {
        $memberList = $user->perm->getGroupMembers($groupId);
        $members = [];
        foreach ($memberList as $singleMember) {
            $user->getUserById($singleMember, true);
            $members[] = array(
                'user_id' => $user->getUserId(),
                'login' => $user->getLogin(),
            );
        }
        $http->sendJsonWithHeaders($members);
    }
}
