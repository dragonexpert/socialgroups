<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
define("IN_MYBB", 1);
define("THIS_SCRIPT", "editgrup.php");
$templatelist = "socialgroups_edit_group_page,socialgroups_staffonly,socialgroups_category_select,socialgroups_announcement_form,socialgroups_announcement_delete_confirm";
$templatelist .= ",socialgroups_member_option,socialgroups_manage_leaders_page,socialgroups_transfer_group";
require_once "global.php";
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$haspermission = 0;
if($socialgroups->socialgroupsuserhandler->is_leader($mybb->user['uid'], $mybb->input['gid']) || $socialgroups->socialgroupsuserhandler->is_moderator($mybb->input['gid'], $mybb->user['uid']))
{
    $haspermission = 1;
}
if(!$haspermission)
{
    error_no_permission();
}
add_breadcrumb($lang->socialgroups, "groups.php");
if($mybb->input['action'] == "editgroup")
{
    if (!$mybb->input['gid'])
    {
        error_no_permission();
    }
    if ($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        // The data is escaped in the save function
        $updated_group = array(
            "name" => $mybb->input['name'],
            "description" => $mybb->input['description'],
            "staffonly" => $mybb->input['staffonly'],
            "private" => $mybb->input['private'],
            "inviteonly" => $mybb->input['inviteonly'],
            "cid" => $mybb->input['cid'],
            "logo" => $mybb->input['logo'],
            "jointype" => $mybb->input['jointype']
        );
        if(isset($mybb->input['locked']))
        {
            $updated_group['locked'] = (int) $mybb->input['locked'];
        }
        if ($mybb->input['background_image'])
        {
            $updated_group['background_image'] = "url(\"" . $mybb->input['background_image'] . "\")";
        }
        $socialgroups->socialgroupsdatahandler->save_group($updated_group, "update", "gid=" . (int)$mybb->input['gid']);
        $url = "showgroup.php?gid=" . (int)$mybb->input['gid'];
        $message = $lang->socialgroups_group_updated;
        redirect($url, $message);
    }
    else
    {
        $query = $db->simple_select("socialgroups", "*", "gid=" . $mybb->input['gid']);
        $group = $db->fetch_array($query);
        if (!$group['gid'])
        {
            error_no_permission();
        }
        add_breadcrumb(htmlspecialchars($group['name']), "showgroup.php?gid=" . $group['gid']);
        add_breadcrumb($lang->sprintf($lang->socialgroups_editing_group, htmlspecialchars($group['name'])), "editgroup.php?action=editgroup&amp;gid=" . $group['gid']);
        if ($group['private'] == 1)
        {
            $privateyes = 'selected="selected"';
        }
        else
        {
            $privateno = 'selected="selected"';
        }
        if ($group['inviteonly'] == 1)
        {
            $inviteyes = 'selected="selected"';
        }
        else
        {
            $inviteno = 'selected="selected"';
        }
        if($group['jointype'] == 1)
        {
            $jointype = 'selected="selected"';
        }
        $viewablecategories = $socialgroups->get_viewable_categories();
        $categoryselect = "<option value='" . $group['cid'] . "'>" . $viewablecategories[$group['cid']] . "</option>";
        foreach ($viewablecategories as $cid => $name)
        {
            eval("\$categoryselect .=\"" . $templates->get("socialgroups_category_select") . "\";");
        }
        if ($mybb->usergroup['canmodcp'])
        {
            $locked = "";
            if($group['locked'])
            {
                $locked = " selected=\"selected\" ";
            }
            eval("\$staffonly =\"" . $templates->get("socialgroups_staffonly") . "\";");
        }
        eval("\$editgrouppage =\"" . $templates->get("socialgroups_edit_group_page") . "\";");
        output_page($editgrouppage);
    }
}
if($mybb->input['action'] == "addannouncement")
{
    if($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        $new_announcement = array(
            "gid" => $mybb->input['gid'],
            "uid" => $mybb->user['uid'],
            "dateline" => TIME_NOW,
            "subject" => $db->escape_string($mybb->input['subject']),
            "message" =>$db->escape_string($mybb->input['message']),
            "active" => $mybb->input['active']
        );
        $db->insert_query("socialgroup_announcements", $new_announcement);
        $url = "showgroup.php?gid=" . $mybb->input['gid'];
        $message = $lang->socialgroups_announcement_added;
        redirect($url, $message);
    }
    else
    {
        $gid = (int) $mybb->input['gid'];
        $action = "addannouncement";
        $groupinfo = $socialgroups->load_group($gid);
        add_breadcrumb($groupinfo['name'], "showgroup.php?gid=$gid");
        add_breadcrumb($lang->socialgroups_adding_announcement, "editgroup.php?action=addannouncement");
        eval("\$addannouncementpage =\"".$templates->get("socialgroups_announcement_form")."\";");
        output_page($addannouncementpage);
    }
}
if($mybb->input['action'] == "editannouncement")
{
    $aid = (int) $mybb->input['aid'];
    if($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        $updated_announcement = array(
            "gid" => $mybb->input['gid'],
            "uid" => $mybb->user['uid'],
            "dateline" => TIME_NOW,
            "subject" => $db->escape_string($mybb->input['subject']),
            "message" =>$db->escape_string($mybb->input['message']),
            "active" => $mybb->input['active']
        );
        $db->update_query("socialgroup_announcements", $updated_announcement, "aid=$aid");
        $url = "showgroup.php?gid=" . $mybb->input['gid'];
        $message = $lang->socialgroups_announcement_edited;
        redirect($url, $message);
    }
    else
    {
        $query = $db->simple_select("socialgroup_announcements", "*","aid=$aid");
        $announcement = $db->fetch_array($query);
        $aidinput = "<input type='hidden' name='aid' value='{$aid}' />";
        $gid = (int) $announcement['gid'];
        $groupinfo = $socialgroups->load_group($gid);
        if(!$socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']))
        {
            error_no_permission();
        }
        add_breadcrumb($groupinfo['name'], "showgroup.php?gid=$gid");
        add_breadcrumb($lang->socialgroups_editing_announcement, "editgroup.php?action=editannouncement&amp;aid=$aid");
        $action = "editannouncement";
        eval("\$editannouncementpage =\"".$templates->get("socialgroups_announcement_form")."\";");
        output_page($editannouncementpage);
    }
}
if($mybb->input['action'] == "deleteannouncement")
{
    $aid = (int) $mybb->input['aid'];
    $query = $db->simple_select("socialgroup_announcements", "aid,gid", "aid=$aid");
    $announcement = $db->fetch_array($query);
    /* Don't allow the deletion of global announcements. */
    if(!$announcement['aid'] || $announcement['gid'] == 0)
    {
        error($lang->socialgroups_invalid_announcement);
    }
    if(!$socialgroups->socialgroupsuserhandler->is_leader($announcement['gid'], $mybb->user['uid']))
    {
        error_no_permission();
    }
    if($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        if($mybb->input['confirm'] == 1) {
            $db->delete_query("socialgroup_announcements", "aid=$aid");
            $url = "showgroup.php?gid=" . $announcement['gid'];
            $message = $lang->socialgroups_announcement_deleted;
            redirect($url, $message);
        }
        else
        {
            $url = "showgroup.php?gid=" . $announcement['gid'];
            $message = $lang->socialgroups_return_to_group;
            redirect($url, $message);
        }
    }
    else
    {
        $groupinfo = $socialgroups->load_group($announcement['gid']);
        add_breadcrumb($groupinfo['name'], "showgroup.php?gid=" . $announcement['gid']);
        add_breadcrumb($lang->socialgroups_delete_announcement, "editgroup.php?action=deleteannouncement&amp;aid=$aid");
        eval("\$announcementdelete =\"".$templates->get("socialgroups_announcement_delete_confirm")."\";");
        output_page($announcementdelete);
    }
}
if($mybb->input['action'] == "manage_leaders")
{
    $gid = (int) $mybb->input['gid'];
    if(!$gid)
    {
        error($lang->socialgroups_invalid_group);
    }
    $groupinfo = $socialgroups->load_group($gid);
    if($mybb->user['uid'] != $groupinfo['uid'])
    {
        error_no_permission();
    }
    add_breadcrumb($groupinfo['name'], "showgroup.php?gid=" . $gid);
    add_breadcrumb($lang->socialgroups_manage_leaders, "editgroup.php?action=manage_leaders&amp;gid=$gid");
    $members = $socialgroups->socialgroupsuserhandler->load_members($gid);
    $query = $db->simple_select("users", "uid,username", "uid IN(" . implode(",", $members) . ")", array("order_by" => "username"));
    while($member = $db->fetch_array($query))
    {
        if($member['uid'] != $mybb->user['uid'])
        {
            eval("\$memberlist .=\"" . $templates->get("socialgroups_member_option") . "\";");
        }
    }
    eval("\$manageleaderpage =\"".$templates->get("socialgroups_manage_leaders_page")."\";");
    output_page($manageleaderpage);
}
if($mybb->input['action'] == "add_leader")
{
    if($mybb->request_method == "post" && $mybb->input['uid'] && $mybb->input['gid'])
    {
        $gid = $mybb->get_input("gid", MyBB::INPUT_INT);
        $userid = $mybb->get_input("uid", MyBB::INPUT_INT);
        $groupinfo = $socialgroups->load_group($gid);
        if($mybb->user['uid'] != $groupinfo['uid'])
        {
            error_no_permission();
        }
        $socialgroups->socialgroupsuserhandler->add_leader($gid, $userid);
        $url = "showgroup.php?gid=" . $mybb->input['gid'];
        $message = $lang->socialgroups_added_leader;
        redirect($url, $message);
    }
}
if($mybb->input['action'] == "delete_leader")
{
    if($mybb->request_method == "post" && $mybb->input['uid'] && $mybb->input['gid'])
    {
        $gid = $mybb->get_input("gid", MyBB::INPUT_INT);
        $userid = $mybb->get_input("uid", MyBB::INPUT_INT);
        $groupinfo = $socialgroups->load_group($gid);
        if($mybb->user['uid'] != $groupinfo['uid'])
        {
            error_no_permission();
        }
        $db->delete_query("socialgroup_leaders", "uid=" .  $userid . " AND gid=" . $gid);
        $url = "showgroup.php?gid=" . $mybb->input['gid'];
        $message = $lang->socialgroups_deleted_leader;
        redirect($url, $message);
    }
}
if($mybb->input['action'] == "transfer_ownership")
{
    $gid = (int) $mybb->input['gid'];
    $groupinfo = $socialgroups->load_group($gid);
    if($mybb->user['uid'] != $groupinfo['uid'])
    {
        error_no_permission();
    }
    if($mybb->request_method == "post")
    {
        if($mybb->input['confirm'] == 1)
        {
            $socialgroups->socialgroupsuserhandler->transfer_ownership($mybb->user['uid'], $mybb->input['new_owner'], $gid, $mybb->input['stay_leader']);
            $url = "showgroup.php?gid=$gid";
            $message = $lang->socialgroups_transfer_ownership;
            redirect($url, $message);
        }
        else
        {
            $url = "showgroup.php?gid=$gid";
            $message = $lang->socialgroups_return_to_group;
            redirect($url, $message);
        }
    }
    add_breadcrumb($groupinfo['name'], "showgroup.php?gid=" . $gid);
    add_breadcrumb($lang->socialgroups_transfer_owner, "editgroup.php?action=transfer_ownership&amp;gid=$gid");
    $members = $socialgroups->socialgroupsuserhandler->load_members($gid);
    $query = $db->simple_select("users", "uid,username", "uid IN(" . implode(",", $members) . ")", array("order_by" => "username"));
    while($member = $db->fetch_array($query))
    {
        if($member['uid'] != $mybb->user['uid'])
        {
            eval("\$memberlist .=\"" . $templates->get("socialgroups_member_option") . "\";");
        }
    }
    eval("\$transferpage =\"".$templates->get("socialgroups_transfer_group")."\";");
    output_page($transferpage);
}
