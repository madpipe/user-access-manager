<?php
/**
 * MediaAjaxEditFrom.php
 *
 * Shows the group selection form for the ajax attachment from.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminObjectController $controller
 */    $userGroups = $controller->getFilteredUserGroups();
$objectUserGroups = $controller->getFilteredObjectUserGroups();

if (count($userGroups) > 0) {
    ?>
    <ul class="uam_group_selection" style="margin: 0;">
        <?php
        $groupsFormName = 'uam_user_groups';
        $objectType = $controller->getObjectType();
        $objectId = $controller->getObjectId();

        /**
         * @var \UserAccessManager\UserGroup\UserGroup[] $userGroups
         */
        foreach ($userGroups as $userGroup) {
            $addition = '';
            $attributes = '';

            /**
             * @var \UserAccessManager\UserGroup\UserGroup[] $objectUserGroups
             */
            if (isset($objectUserGroups[$userGroup->getId()])) {
                $attributes .= 'checked="checked" ';

                if ($objectUserGroups[$userGroup->getId()]->isLockedRecursive($objectType, $objectId)) {
                    $attributes .= 'disabled="disabled" ';
                    $addition .= ' [LR]';
                }
            }
            ?>
            <li>
                <input type="checkbox"
                       id="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" <?php echo $attributes; ?>
                       value="<?php echo $userGroup->getId(); ?>" name="<?php echo "{$groupsFormName}[{$userGroup->getId()}]"; ?>"
                data-="uam_user_groups"/>
                <label for="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" class="selectit"
                       style="display:inline;">
                    <?php echo htmlentities($userGroup->getName()).$addition; ?>
                </label>
                <a class="uam_group_info_link">(<?php echo TXT_UAM_INFO; ?>)</a>
                <?php include 'GroupInfo.php'; ?>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
} elseif ($controller->checkUserAccess()) {
    ?>
    <a href='?page=uam_user_group'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
    <?php
} else {
    echo TXT_UAM_NO_GROUP_AVAILABLE;
}