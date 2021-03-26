<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 */

define("IN_MYBB", 1);
define("THIS_SCRIPT", "showgroup.php");
$templatelist = "socialgroups_member, socialgroups_remaining_members, socialgroups_showgroup_page,socialgroups_announcements,socialgroups_announcement_announcement";
$templatelist .= ",socialgroups_mod_column,socialgroups_inline_checkbox,socialgroups_thread_thread,socialgroups_mod_tools,socialgroups_leave_link,socialgroups_join_link";
$templatelist .= ",socialgroups_new_thread,socialgroups_new_thread_link,codebuttons, smilieinsert_getmore, smilieinsert_smilie, smilieinsert,forumdisplay_sticky_sep";
$templatelist .= ",forumdisplay_threads_sep,socialgroups_no_threads,socialgroups_edit_group_link,socialgroups_announcement_manage,socialgroups_add_announcement_link";
$templatelist .= ",socialgroups_manage_link,socialgroups_logo,socialgroups_groupjump,socialgroups_groupjump_group";
$templatelist .= ",forumdisplay_usersbrowsing_user,socialgroups_avatar,socialgroups_replybox_mod";
require_once "global.php";
require_once "inc/class_parser.php";
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$lang->load("forumdisplay");
$uid = $mybb->user['uid'];
$gid = $mybb->get_input("gid", MyBB::INPUT_INT);
$socialgroups = new socialgroups($gid, 1, 1, 1);
add_breadcrumb($lang->socialgroups, "groups.php");
$groupinfo = $socialgroups->load_group($gid);
$socialgroups_group_cache = $cache->read("socialgroups");
$socialgroups_category_cache = $cache->read("socialgroups_categories");
$cid = $socialgroups_group_cache[$gid]['cid'];
$permissions = $socialgroups->load_permissions($gid);
$title = stripcslashes($socialgroups_group_cache[$gid]['name']);
add_breadcrumb($socialgroups_category_cache[$cid]['name'], $socialgroups->breadcrumb_link("category", $cid, $socialgroups_category_cache[$cid]['name']));
add_breadcrumb(stripcslashes($socialgroups_group_cache[$gid]['name']), $socialgroups->breadcrumb_link("group", $gid, $groupinfo['name']));
$members = $socialgroups->socialgroupsuserhandler->load_members($gid);
$leaders = $socialgroups->socialgroupsuserhandler->load_leaders($gid);
$canviewgroup = 1;
if($groupinfo['private'] && !$socialgroups->socialgroupsuserhandler->is_member($gid, $mybb->user['uid'])
    && !$socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
{
    $canviewgroup = 0;
}
if($groupinfo['staffonly'] && !$mybb->usergroup['canmodcp'])
{
    $canviewgroup = 0;
}
$action = "";
if($mybb->get_input("action"))
{
    $action = $mybb->get_input("action");
}
if($groupinfo['approved'] == 0)
{
    if($action != "approvegroup")
    {
        $socialgroups->error("invalid_group");
    }
}
if($action == "approvegroup")
{
    if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
    {
        $socialgroups->socialgroupsdatahandler->save_group(array("approved" => 1), "update", "gid=" . $gid);
        $data = array(
            "gid" => $gid,
            "groupname" => $groupinfo['name'],
            "action" => "Approved Group: " . $db->escape_string($groupinfo['name'])
        );

        log_moderator_action($data, "Approved Group: " . $db->escape_string($groupinfo['name']));
        $url = "groupcp.php";
        $message = "The group has been approved.";
        redirect($url, $message);
    }
    else
    {
        error_no_permission();
    }
}
if($action == "unapprovegroup")
{
    if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
    {
        $socialgroups->socialgroupsdatahandler->save_group(array("approved" => 0), "update", "gid=" . $gid);
        $data = array(
            "gid" => $gid,
            "groupname" => $groupinfo['name'],
            "action" => "Unapproved Group: " . $db->escape_string($groupinfo['name'])
        );

        log_moderator_action($data, "Unapproved Group: " . $db->escape_string($groupinfo['name']));
        $url = "groupcp.php";
        $message = "The group has been unapproved.";
        redirect($url, $message);
    }
    else
    {
        error_no_permission();
    }
}
if($action == "joingroup")
{
    //Our function does all the hard work of figuring out if a person can join.
    if($groupinfo['jointype'] == 0)
    {
        $socialgroups->socialgroupsuserhandler->join($gid, $uid);
        $message = $lang->socialgroups_joined_group;
        redirect("showgroup.php?gid=$gid", $message);
    }
    if($groupinfo['jointype'] == 1)
    {
        if($socialgroups->socialgroupsuserhandler->can_join($groupinfo['gid'], $mybb->user['uid']))
        {
            $join_request = array(
                "gid" => $groupinfo['gid'],
                "uid" => $mybb->user['uid'],
                "dateline" => time()
            );

            $db->insert_query("socialgroup_join_requests", $join_request);
            $message = "Your join request has been sent.";
            redirect("showgroup.php?gid=$gid", $message);
        }
    }

}
if($action == "leavegroup")
{
    $socialgroups->socialgroupsuserhandler->remove_member($gid, $uid);
    $message = $lang->socialgroups_left_group;
    redirect("showgroup.php?gid=$gid", $message);
}
$announcementmoderator = 0;
if($socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
{
    eval("\$editgrouplink =\"".$templates->get("socialgroups_edit_group_link")."\";");
    $announcementmoderator = 1;
}
if($action == "newthread" && $permissions['postthreads'] != 0)
{
    if(!in_array($uid, $members))
    {
        error_no_permission();
    }
    if($groupinfo['locked'])
    {
        error_no_permission();
    }
    if($mybb->request_method== "post" && verify_post_check($mybb->input['my_post_key']))
    {
        // Use our thread handler to manage this
        $socialgroups->socialgroupsthreadhandler->new_thread($mybb->input);
    }
    else
    {
        add_breadcrumb($lang->socialgroups_post_new_thread, "showgroup.php?gid=$gid&action=newthread");
        $codebuttons = build_mycode_inserter();
        $smileys = build_clickable_smilies();
        if($socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']))
        {
            $stickycheck = $closedchecked = "";
            eval("\$newthread_modoptions = \"".$templates->get("socialgroups_replybox_mod")."\";");
        }
        eval("\$newthreadform =\"".$templates->get("socialgroups_new_thread")."\";");
        output_page($newthreadform);
        exit;
    }
}
if($action && $mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
{
    $allowedaction = array("lock", "unlock", "unapprove", "approve", "sticky", "unsticky", "softdelete", "permdelete");
    $plugins->run_hooks("showgroup_inline_moderation");
    if(!in_array($action, $allowedaction))
    {
        error("Invalid action");
    }
    if(!$socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) && !$socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
    {
        error_no_permission();
    }
    if(!$mybb->input['tidlist'])
    {
        redirect("showgroup.php?gid=$gid", $lang->socialgroups_invalid_thread);
    }
    $tidlist = $db->escape_string($mybb->get_input("tidlist"));
    // Now that we checked permissions, actually do the moderation
    $action = $mybb->get_input("action");
    if($action == "lock")
    {
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET closed=1 WHERE tid IN($tidlist)");
        $message = $lang->socialgroups_threads_locked;
        $modaction = "Locked Threads";
    }
    if($action == "unlock")
    {
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET closed=0 WHERE tid IN($tidlist)");
        $message = $lang->socialgroups_threads_unlocked;
        $modaction = "Unlocked Threads";
    }
    if($action == "unapprove")
    {
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET visible=0 WHERE tid IN($tidlist)");
        $message = $lang->socialgroups_threads_unapproved;
        $modaction = "Unapproved Threads";
        $socialgroups->recount_threads($gid);
    }
    if($action == "approve")
    {
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET visible=1 WHERE tid IN($tidlist)");
        $message = $lang->socialgroups_threads_approved;
        $modaction= "Approved Threads";
        $socialgroups->recount_threads($gid);
    }
    if($action == "sticky")
    {
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET sticky=1 WHERE tid IN($tidlist)");
        $message = $lang->socialgroups_threads_stuck;
        $modaction = "Stickied Threads";
    }
    if($action == "unsticky")
    {
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET sticky=0 WHERE tid IN($tidlist)");
        $message = $lang->socialgroups_threads_unstuck;
        $modaction = "Unstuck Threads";
    }
    if($action == "softdelete")
    {
        $selection = explode(",", $tidlist);
        foreach ($selection as $tid)
        {
            $socialgroups->socialgroupsthreadhandler->delete_thread($tid, $gid, 0);
        }
    }
    if($action == "permdelete")
    {
        $selection = explode(",", $tidlist);
        foreach ($selection as $tid)
        {
            $socialgroups->socialgroupsthreadhandler->delete_thread($tid, $gid, 1);
        }
    }
    $data = array(
        "gid" => $gid,
        "groupname" => $db->escape_string($socialgroups->group[$gid]['name']),
        "tids" => $tidlist,
        "action" => $modaction
    );
    log_moderator_action($data, $modaction);
    redirect("showgroup.php?gid=$gid", $message);
}
// Get the members
$memberquery = $db->simple_select("users", "uid,username,usergroup,displaygroup", "uid IN(" . implode(",", $members) . ")");
$totalusers = $db->num_rows($memberquery);
$doneusers = 0;
$plugins->run_hooks("showgroup_start");
$joinlink = "";
if(in_array($uid, $members) && $uid != $socialgroups_group_cache[$gid]['uid']) // The owner can't leave the group.
{
    eval("\$joinlink =\"".$templates->get("socialgroups_leave_link")."\";");
}
if($socialgroups->socialgroupsuserhandler->can_join($gid, $uid))
{
    eval("\$joinlink =\"".$templates->get("socialgroups_join_link")."\";");
}
$comma = $memberlist = "";
while($groupmember = $db->fetch_array($memberquery))
{
    if($doneusers > 50)
    {
        $remaining = $totalusers - $doneusers;
        eval("\$memberlist =\"".$templates->get("socialgroups_remaining_members")."\";");
        break;
    }
    $groupmember['displayname'] = format_name($groupmember['username'], $groupmember['usergroup'],$groupmember['displaygroup']);
    $groupmember['profilelink'] = build_profile_link($groupmember['displayname'], $groupmember['uid']);
    eval("\$memberlist .= \"".$templates->get("socialgroups_member")."\";");
    $comma = ", ";
}

$comma = $leaderlist = "";
$leaderquery = $db->simple_select("users", "uid,username,usergroup,displaygroup", "uid IN(" . implode(",", $leaders) . ")");
while($groupmember = $db->fetch_array($leaderquery))
{
    $groupmember['displayname'] = format_name($groupmember['username'], $groupmember['usergroup'],$groupmember['displaygroup']);
    $groupmember['profilelink'] = build_profile_link($groupmember['displayname'], $groupmember['uid']);
    eval("\$leaderlist .= \"".$templates->get("socialgroups_member")."\";");
    $comma = ", ";
}

// Gather the who's online
$socialgroups->socialgroupsuserhandler->viewing_group($gid);

if($permissions['postthreads'] == 1 && in_array($uid, $members) || in_array($uid, $leaders)) // Only members can post threads assuming leaders allow this
{
    if(!$groupinfo['locked'])
    {
        eval("\$newthreadbutton =\"" . $templates->get("socialgroups_new_thread_link") . "\";");
    }
}
// Set up the parser
$parser = new PostParser();
$parser_options = array(
    "allow_mycode" => 1,
    "allow_smilies"=> 1,
    "filter_badwords" => 1,
    "allow_html" => 0
);
// Handle announcements
$colspan= 4;
$announcementcolspan = 3;
$threadauthorcolspan = 2;
$announcements =  $announcementlist = $announcementmanage = "";
$modcolumn = "";
$threadcolspan = 1;
if($socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
{
    ++$threadcolspan;
    eval("\$modcolumn =\"".$templates->get("socialgroups_mod_column")."\";");
}
$announcement_count = 0;
$announcements_loaded = $socialgroups->load_announcements($gid);
foreach($announcements_loaded as $announcement)
{
    ++$announcement_count;
    $plugins->run_hooks("showgroup_announcement");
    $announcement['message'] = $parser->parse_message($announcement['message'], $parser_options);
    if($announcementmoderator == 1)
    {
        $threadauthorcolspan = 2;
        if($announcement['gid'] != 0)
        {
            $colspan = 5;
            $announcementcolspan = 1;
            eval("\$announcementmanage =\"" . $templates->get("socialgroups_announcement_manage") . "\";");
        }
        else
        {
            $colspan = 5;
            $announcementcolspan = 2;
        }
    }
    $announcementavatar = "";
    if($mybb->settings['socialgroups_thread_avatar'])
    {
        $avatar = $announcement['avatar'];
        $avatarurl = $avatar['image'];
        $dimensions = $avatar['width_height'];
        eval("\$announcementavatar =\"".$templates->get("socialgroups_avatar")."\";");
    }
    eval("\$announcements .=\"".$templates->get("socialgroups_announcement_announcement")."\";");
}
if($announcement_count == 0 && $announcementmoderator)
{
    $colspan = 5;
}
eval("\$announcementlist =\"".$templates->get("socialgroups_announcements")."\";");
// Thread time
$colspan = 5;
$page = 1;
if($mybb->get_input("page"))
{
    $page = $mybb->get_input("page", MyBB::INPUT_INT);
}
if($page< 1)
{
    $page = 1;
}
if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_leader($gid, $uid))
{
    $visible = -1;
}
else
{
    $visible = 0;
}
$threadcountquery = $db->simple_select("socialgroup_threads", "COUNT(tid) as threads", "gid=$gid AND visible>$visible");
$threadcount = $db->fetch_field($threadcountquery, "threads");
$pages = ceil($threadcount / 20);
if($page > $pages)
{
    $page = $pages;
}
$sorturl = "";
if($mybb->get_input("sort"))
{
    $sorturl = "&sort=" . $mybb->get_input("sort");
}
$directionurl = "";
if($mybb->get_input("direction"))
{
    $directionurl = "&direction=" . $mybb->get_input("direction");
}
$pagination = multipage($threadcount, 20, $page, "showgroup.php?gid=$gid". $sorturl . $directionurl);
$threadlist = $socialgroups->socialgroupsthreadhandler->load_threads($gid, $page, 20, array("field" => $mybb->get_input("sort"), "direction" => $mybb->get_input("direction")));
$colspan = 4;
$threadcolspan = 1;
if($socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
{
    ++$colspan;
    //$threadcolspan = 2;
}
$threads = "";
if(!is_array($threadlist))
{
    $trow = alt_trow();
    $threadlist = array();
    eval("\$threads =\"".$templates->get("socialgroups_no_threads")."\";");
}
$inlinecheckbox = "";
foreach($threadlist as $thread)
{
    $trow = alt_trow();
    if($thread['visible'] == 0)
    {
        $unapprovedshade = "trow_shaded";
    }
    else
    {
        $unapprovedshade = $trow;
    }
    if($thread['closed'])
    {
        $lockicon = "<span class=\"thread_status closefolder\" title=\"locked thread\">&nbsp;</span>";
    }
    else
    {
        $lockicon = "";
    }
    if($socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
    {
        eval("\$inlinecheckbox =\"".$templates->get("socialgroups_inline_checkbox")."\";");
    }
    if($thread['sticky'])
    {
        $stickybit = "<strong>Sticky:</strong>";
        if(!isset($donestickysep))
        {
            eval("\$threads .= \"".$templates->get("forumdisplay_sticky_sep")."\";");
            $shownormalsep = true;
            $donestickysep = true;
        }
    }
    else if($thread['sticky'] == 0 && !empty($shownormalsep))
    {
        eval("\$threads .= \"".$templates->get("forumdisplay_threads_sep")."\";");
        $shownormalsep = false;
    }
    $plugins->run_hooks("showgroup_thread");
    $threadavatar = "";
    if($mybb->settings['socialgroups_thread_avatar'])
    {
        $avatar = $thread['avatar'];
        $avatarurl = $avatar['image'];
        $dimensions = $avatar['width_height'];
        $profilelink = $thread['profilelink'];
        eval("\$threadavatar =\"".$templates->get("socialgroups_avatar")."\";");
    }
    $thread['message'] = $parser->parse_message($thread['message'], $parser_options);
    $thread['threadlink'] = $socialgroups->groupthreadlink($thread['tid'], $thread['subject']);
    eval("\$threads .=\"".$templates->get("socialgroups_thread_thread")."\";");
    $stickybit= "";
}
$modtools = $addannouncementlink = $managegrouplink = "";
if($socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
{
    eval("\$modtools =\"".$templates->get("socialgroups_mod_tools")."\";");
    eval("\$addannouncementlink =\"".$templates->get("socialgroups_add_announcement_link")."\";");
    if($mybb->user['uid'] == $socialgroups_group_cache[$gid]['uid'])
    {
        eval("\$managegrouplink =\"".$templates->get("socialgroups_manage_link")."\";");
    }
}
if($groupinfo['logo'])
{
    eval("\$grouplogo =\"".$templates->get("socialgroups_logo")."\";");
}
if(!$canviewgroup)
{
    $memberlist = $announcementlist = $threads = "";
    if($groupinfo['staffonly'] && $mybb->usergroup['canmodcp'])
    {
        $joinlink = "";
    }
}
// Group jump menu
$groupjumpmenu = $groupoptions = $groupjumpmenu = "";
if($mybb->settings['socialgroups_showgroupjump'] && $uid > 0)
{
    $query = $db->query("SELECT m.gid, g.name FROM " . TABLE_PREFIX . "socialgroup_members m
    LEFT JOIN " . TABLE_PREFIX . "socialgroups g ON(m.gid=g.gid)
    WHERE m.uid=" . $mybb->user['uid']);
    while($groupdata = $db->fetch_array($query))
    {
        $groupdata['name'] = htmlspecialchars_uni($groupdata['name']);
        eval("\$groupoptions .=\"".$templates->get("socialgroups_groupjump_group")."\";");
    }
    eval("\$groupjumpmenu =\"".$templates->get("socialgroups_groupjump")."\";");
}
if(isset($groupinfo['style']))
{
    $styleattribute = $groupinfo['style'];
}
else
{
    $styleattribute = "";
}
$lockedwarning = "";
if($groupinfo['locked'])
{
    $lockedwarning = $lang->socialgroups_group_locked_warning;
}
$plugins->run_hooks("showgroup_end");
eval("\$showgrouppage =\"".$templates->get("socialgroups_showgroup_page")."\";");
output_page($showgrouppage);