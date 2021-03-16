<?php
/**
 * Socialgroup plugin created by Mark Janssen.
 * This is not a free plugin.
 */
define("IN_MYBB", 1);
$templatelist = "socialgroups_group_cp,socialgroups_join_request_request,socialgroups_join_request_page,socialgroups_join_request_no_requests";
$templatelist .= ",socialgroups_groupcp_page,socialgroups_add_leader_page,socialgroups_add_remove_leaders,socialgroups_groupcp_group";
$templatelist .= ",socialgroups_remove_leader_page,socialgroups_unlock_group,socialgroups_lock_group,socialgroups_groupcp_modcolumn";
$templatelist .= ",socialgroups_join_request_page,socialgroups_join_request_no_requests,socialgroups_join_request_request";
$templatelist .= ",socialgroups_add_member_form,socialgroups_remove_member_form";
define("THIS_SCRIPT", "groupcp.php");
require_once "global.php";
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$uid = $mybb->user['uid'];
$socialgroups = new socialgroups();
$groups = $socialgroups->socialgroupsuserhandler->can_groupcp($mybb->user['uid']);
if(!$groups)
{
    error_no_permission();
}
$title = "Group CP";
add_breadcrumb($lang->socialgroups, "groups.php");
add_breadcrumb("Group CP", "groupcp.php");
if($mybb->get_input("gid"))
{
    $gid = $mybb->get_input("gid", MyBB::INPUT_INT);
    $where = "gid=" . $gid;
    $groupinfo = $socialgroups->load_group($gid);
    add_breadcrumb(htmlspecialchars_uni($groupinfo['name'], "groupcp.php?gid=" . $gid));
    // Validate if the person is in fact a group leader for the specific group
    if(!$socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']))
    {
        // Allow moderators for socialgroups to still have access.
        if(!$socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
        {
            error_no_permission();
        }
    }

    if($mybb->get_input("action") == "add_member")
    {
        $title = "Add Member";
        if($mybb->request_method == "post" && verify_post_check($mybb->get_input("my_post_key")))
        {
            if($mybb->get_input("userid") != 0)
            {
                $userid = $mybb->get_input("userid", MyBB::INPUT_INT);
            }
            else if($mybb->get_input("member_username"))
            {
                $query = $db->simple_select("users", "uid, username", "username='" . $db->escape_string($mybb->get_input("member_username")) . "'");
                $member = $db->fetch_array($query);
                if(!isset($member['uid']))
                {
                    $socialgroups->error("invalid_member");
                }
                $userid = $member['uid'];
            }
            else
            {
                $socialgroups->error("invalid_member");
            }

            if(!$socialgroups->socialgroupsuserhandler->is_member($gid, $userid))
            {
                $socialgroups->socialgroupsuserhandler->join($gid, $userid, 1);
                $url = $mybb->settings['bburl'] . "/groupcp.php";
                $message = "The member has been added to the group.";
                redirect($url, $message);
                exit;
            }
            else
            {
                $socialgroups->error("already_member");
            }
        }
        else
        {
            add_breadcrumb("Add Member", "groupcp.php?action=add_member");
            eval("\$add_member_form = \"".$templates->get("socialgroups_add_member_form")."\";");
            output_page($add_member_form);
            exit;
        }
    }

    if($mybb->get_input("action") == "remove_member")
    {
        $title = "Remove Member";
        if($mybb->request_method == "post" && verify_post_check($mybb->get_input("my_post_key")))
        {
            if($mybb->get_input("userid") != 0)
            {
                $userid = $mybb->get_input("userid", MyBB::INPUT_INT);
            }
            else if($mybb->get_input("member_username"))
            {
                $query = $db->simple_select("users", "uid, username", "username='" . $db->escape_string($mybb->get_input("member_username")) . "'");
                $member = $db->fetch_array($query);
                if(!isset($member['uid']))
                {
                    $socialgroups->error("invalid_member");
                }
                $userid = $member['uid'];
            }
            else
            {
                $socialgroups->error("invalid_member");
            }
            // The remove_member function validates if the member is in the group.
            $socialgroups->socialgroupsuserhandler->remove_member($gid, $userid);
            $url = $mybb->settings['bburl'] . "/groupcp.php";
            $message = "The member has been removed from the group.";
            redirect($url, $message);
            exit;
        }
        else
        {
            add_breadcrumb("Remove Member", "groupcp.php?action=remove_member");
            eval("\$remove_member_form = \"".$templates->get("socialgroups_remove_member_form")."\";");
            output_page($remove_member_form);
            exit;
        }
    }

    if($mybb->get_input("action") == "approve_join_request" && $mybb->get_input("rid"))
    {
        $title = "Join Request Approved";
        $rid = $mybb->get_input("rid", MyBB::INPUT_INT);
        $query = $db->query("SELECT r.*, u.username FROM " . TABLE_PREFIX . "socialgroup_joinrequests 
        LEFT JOIN " . TABLE_PREFIX . "users u ON(r.uid=u.uid)
        WHERE r.rid=" . $rid . " AND r.gid=" . $gid);
        $request = $db->fetch_array($query);
        if(!isset($request['rid']))
        {
            error($lang->socialgroups_invalid_request);
        }
        $socialgroups->socialgroupsuserhandler->join($gid, $request['uid']);

        $data = array(
            "gid" => $request['gid'],
            "groupname" => $groupinfo['name'],
            "action" => "Approved Join Request: " . $db->escape_string($request['username'])
        );

        log_moderator_action($data, "Approved Join Request: " . $db->escape_string($request['username']));

        $update_request = array(
            "approved" => 1
        );
        $db->update_query("socialgroup_joinrequests", $update_request, "rid=" . $rid);

        $url = "groupcp.php?gid=$gid&amp;action=join_requests";
        $message = $lang->socialgroups_request_approved;
        // Set up a PM handler
        $pm = array(
            "subject" => $lang->socialgroups_join_request_accepted,
            "message" => $lang->sprintf($lang->socialgroups_join_request_message, $groupinfo['name']),
            "touid" => $request['uid'],
        );

        send_pm($pm, $uid, true);
        redirect($url, $message);
    }
    if($mybb->get_input("action") == "delete_request" && $mybb->get_input("rid"))
    {
        $title = "Join Request Deleted";
        $rid = $mybb->get_input("rid", MyBB::INPUT_INT);
        $query = $db->simple_select("socialgroup_join_requests", "*", "rid=$rid AND gid=" . $groupinfo['gid']);
        $request = $db->fetch_array($query);
        if(!isset($request['rid']))
        {
            error($lang->socialgroups_invalid_request);
        }
        $db->delete_query("socialgroup_join_requests", "rid=$rid");
        $url = "groupcp.php?gid=$gid&amp;action=join_requests";
        $message = $lang->socialgroups_join_request_rejected;
        redirect($url, $message);
    }
    if($mybb->get_input("action") == "deny_request" && $mybb->get_input("rid"))
    {
        $title = "Join Request Denied";
        $rid = $mybb->get_input("rid", MyBB::INPUT_INT);
        $query = $db->query("SELECT r.*, u.username FROM " . TABLE_PREFIX . "socialgroup_joinrequests 
        LEFT JOIN " . TABLE_PREFIX . "users u ON(r.uid=u.uid)
        WHERE r.rid=" . $rid . " AND r.gid=" . $gid);
        $request = $db->fetch_array($query);
        if (!isset($request['rid']))
        {
            error($lang->socialgroups_invalid_request);
        }
        $data = array(
            "gid" => $request['gid'],
            "groupname" => $groupinfo['name'],
            "action" => "Denied Join Request: " . $db->escape_string($request['username'])
        );

        log_moderator_action($data, "Denied Join Request: " . $db->escape_string($request['username']));

        $update_request = array(
            "approved" => 0
        );
        $db->update_query("socialgroup_joinrequests", $update_request, "rid=" . $rid);

        $url = "groupcp.php?gid=$gid&amp;action=join_requests";
        $message = $lang->socialgroups_request_approved;
        // Set up a PM handler
        $pm = array(
            "subject" => "Request Denied",
            "message" => "Your request to join " . htmlspecialchars_uni($groupinfo['name']) . " has been denied.",
            "touid" => $request['uid'],
        );

        send_pm($pm, $uid, true);
        redirect($url, $message);
    }
    if($mybb->get_input("action") == "join_requests")
    {
        $title = "Join Request Management";
        add_breadcrumb("Join Requests", "groupcp.php?action=join_requests");
        $query = $db->query("SELECT r.*, u.username FROM " . TABLE_PREFIX . "socialgroup_join_requests r
        LEFT JOIN " . TABLE_PREFIX . "users u ON(r.uid=u.uid)
        WHERE r.gid=$gid
        ORDER BY r.dateline DESC
        LIMIT 50");
        $num_requests = $db->num_rows($query);
        while($request = $db->fetch_array($query))
        {
            $request['time'] = my_date("relative", $request['dateline']);
            $request['profilelink'] = build_profile_link($request['username'], $request['uid']);
            eval("\$join_requests .=\"".$templates->get("socialgroups_join_request_request")."\";");
        }
        if($num_requests == 0)
        {
            eval("\$join_requests =\"".$templates->get("socialgroups_join_request_no_requests")."\";");
        }
        eval("\$join_request_page =\"".$templates->get("socialgroups_join_request_page")."\";");
        output_page($join_request_page);
        exit;
    }
    if($mybb->get_input("action") == "lockgroup" && $socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
    {
        $update_group = array(
            "locked" => 1
        );
        $db->update_query("socialgroups", $update_group, "gid=" . $gid);
        // Log the action
        $data = array(
            "gid" => $gid,
            "groupname" => $db->escape_string($groupinfo['name']),
            "action" => "Locked Group"
        );
        log_moderator_action($data, "Locked Group");
        $url = "groupcp.php";
        $message = "The group has been locked.";

        // Send a message to the group owner so they know it has been locked.
        $pm = array(
            "subject" => "Group Locked",
            "message" => "The group " . $db->escape_string($groupinfo['name']) . " has been locked.",
            "touid" => $groupinfo['uid']
        );
        send_pm($pm, $uid, true);

        redirect($url, $message);
    }
    if($mybb->get_input("action") == "unlockgroup" && $socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
    {
        $update_group = array(
            "locked" => 0
        );
        $db->update_query("socialgroups", $update_group, "gid=" . $gid);
        // Log the action
        $data = array(
            "gid" => $gid,
            "groupname" => $db->escape_string($groupinfo['name']),
            "action" => "Unlocked Group"
        );
        log_moderator_action($data, "Unlocked Group");
        $url = "groupcp.php";
        $message = "The group has been unlocked.";

        // Send a message to the group owner so they know it has been locked.
        $pm = array(
            "subject" => "Group Locked",
            "message" => "The group " . $db->escape_string($groupinfo['name']) . " has been unlocked.",
            "touid" => $groupinfo['uid']
        );
        send_pm($pm, $uid, true);

        redirect($url, $message);
    }

    if($mybb->get_input("action") == "add_leader")
    {
        $title = "Add Leader";
        if($mybb->request_method == "POST" && verify_post_check($mybb->get_input("my_post_key")))
        {
            // This function handles validation.
            $success = $socialgroups->socialgroupsuserhandler->add_leader($gid, $mybb->get_input("leader", MyBB::INPUT_INT));
            $message = "Added leader successfully.";
            if(!$success)
            {
                $message = "Error adding leader.";
            }
            $url = $mybb->settings['bburl'] . "/groupcp.php";
            redirect($url, $message);
            exit;
        }
        else
        {
            add_breadcrumb("Add Leader", "groupcp.php?action=add_leader");
            $query = $db->query("SELECT sgm.uid, u.username FROM " . TABLE_PREFIX . "socialgroup_members sgm
            LEFT JOIN " . TABLE_PREFIX . "users u ON(sgm.uid=u.uid)
            ORDER BY u.username ASC");
            $leader_selection = "";
            while($choice = $db->fetch_array($query))
            {
                if(!$socialgroups->socialgroupsuserhandler->is_leader($choice['uid']) && $choice['uid'] != $groupinfo['uid'])
                {
                    $leader_selection .= "<option value=\"" . $choice['uid'] . "\">" . htmlspecialchars_uni($choice['username']) . "</option>";
                }
            }
            $db->free_result($query);
            eval("\$add_leader_page =\"".$templates->get("socialgroups_add_leader_page")."\";");
            output_page($add_leader_page);
            exit;
        }
    }
    if($mybb->get_input("action") == "remove_leader")
    {
        $title = "Remove Leader";
        if($mybb->request_method == "POST" && verify_post_check($mybb->get_input("my_post_key")))
        {
            $success = $socialgroups->socialgroupsuserhandler->remove_leader($gid, $mybb->input['leader']);
            $message = "Removed leader successfully.";
            if(!$success)
            {
                $message = "Error removing leader.";
            }
            $url = $mybb->settings['bburl'] . "/groupcp.php";
            redirect($url, $message);
            exit;
        }
        else
        {
            add_breadcrumb("Remove Leader", "groupcp.php?action=remove_leader");
            $query = $db->query("SELECT l.uid, u.username FROM " . TABLE_PREFIX . "socialgroup_leaders l
            LEFT JOIN " . TABLE_PREFIX . "users u ON(l.uid=u.uid)
            ORDER BY u.username ASC");
            $leader_selection = "";
            while($choice = $db->fetch_array($query))
            {
                if($choice['uid'] != $mybb->user['uid'] && $choice['uid'] != $groupinfo['uid'])
                {
                    $leader_selection .= "<option value=\"" . $choice['uid'] . "\">" . htmlspecialchars_uni($choice['username']) . "</option>";
                }
            }
            $db->free_result($query);
            eval("\$remove_leader_page =\"".$templates->get("socialgroups_remove_leader_page")."\";");
            output_page($remove_leader_page);
            exit;
        }
    }
}
else
{
    $where = "";
    if (is_array($groups))
    {
        $gids = implode(",", $groups);
        $where = "gid IN (" . $gids . ")";
    }
}
$groupquery = $db->simple_select("socialgroups", "*", $where);
$addremoveleaders = $lockgroup = $grouplist = "";
while($group = $db->fetch_array($groupquery))
{
    $group['name'] = htmlspecialchars_uni($group['name']);
    $addremoveleaders = "";
    if($group['uid'] == $mybb->user['uid'])
    {
        eval("\$addremoveleaders =\"".$templates->get("socialgroups_add_remove_leaders")."\";");
    }
    if($socialgroups->socialgroupsuserhandler->is_moderator($group['gid'], $mybb->user['uid']))
    {
        if($group['locked'])
        {
            eval("\$lockgroup =\"" . $templates->get("socialgroups_unlock_group") . "\";");
        }
        else
        {
            eval("\$lockgroup =\"" . $templates->get("socialgroups_lock_group") . "\";");
        }
    }
    eval("\$grouplist .=\"".$templates->get("socialgroups_groupcp_group")."\";");
}
$db->free_result($groupquery);
$colspan = 4;
if($socialgroups->socialgroupsuserhandler->is_moderator(1, $mybb->user['uid']))
{
    ++$colspan;
    eval("\$groupcpmod =\"" . $templates->get("socialgroups_groupcp_modcolumn") . "\";");
}
eval("\$groupcppage =\"".$templates->get("socialgroups_groupcp_page")."\";");
output_page($groupcppage);
