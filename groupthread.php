<?php
define("IN_MYBB", 1);
define("THIS_SCRIPT", "groupthread.php");
$templatelist = "socialgroups_post_post,codebuttons, smilieinsert_getmore, smilieinsert_smilie, smilieinsert, socialgroups_groupthread_page";
$templatelist.= ",socialgroups_thread_tools,socialgroups_replybox,socialgroups_replybox_mod,member_profile_userstar, postbit_warninglevel_formatted";
$templatelist .= ",postbit_userstar, postbit_online, postbit_avatar, postbit_find, postbit_pm, postbit_author_user, postbit_edit, postbit_quickdelete";
$templatelist .= ",postbit_quickrestore, postbit_posturl, postbit_report, postbit_iplogged_hiden, newpoints_postbit, postbit_classic";
$templatelist .= ",socialgroups_postbit_report,socialgroups_postbit_delete,showthread_newreply_closed,showthread_classic_header,socialgroups_logo";
$templatelist .= ",socialgroups_groupjump_group,socialgroups_groupjump";
require_once "global.php";
require_once "inc/functions_post.php";
require_once "inc/class_parser.php";
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$lang->load("showthread");
// We'll use the tid to get the group.
$tid = (int) $mybb->input['tid'];
$query = $db->simple_select("socialgroup_threads", "*", "tid=$tid");
$threadinfo = $db->fetch_array($query);
$db->free_result($query);
if(!$threadinfo['tid'])
{
    error_no_permission();
}
$uid= $mybb->user['uid'];
$gid = $threadinfo['gid'];
$socialgroups = new socialgroups($gid, 1);
$cid = $socialgroups->group[$gid]['cid'];
$groupinfo = $socialgroups->load_group($threadinfo['gid']);
if($groupinfo['private'] && !$socialgroups->socialgroupsuserhandler->is_member($groupinfo['gid'], $mybb->user['uid']))
{
    if(!$socialgroups->socialgroupsuserhandler->is_moderator($groupinfo['gid'], $mybb->user['uid']))
    {
        error_no_permission();
    }
}
if($groupinfo['staffonly'] && !$mybb->usergroup['canmodcp'])
{
    error_no_permission();
}
if($groupinfo['logo'])
{
    eval("\$grouplogo =\"".$templates->get("socialgroups_logo")."\";");
}
add_breadcrumb($lang->socialgroups, "groups.php");
add_breadcrumb($socialgroups->category[$cid]['name'], $socialgroups->breadcrumb_link("category", $cid, $socialgroups->category[$cid]['name']));
add_breadcrumb(stripcslashes($socialgroups->group[$gid]['name']), $socialgroups->breadcrumb_link("group", $gid, $groupinfo['name']));
add_breadcrumb(htmlspecialchars_uni($threadinfo['subject']), $socialgroups->breadcrumb_link("groupthread", $tid, $threadinfo['subject']));
$members = $socialgroups->socialgroupsuserhandler->members[$gid];
$leaders = $socialgroups->socialgroupsuserhandler->leaders[$gid];
$socialgroups->load_permissions($gid);
$permissions = $socialgroups->permissions[$gid];
if($mybb->input['action'])
{
    $action = $mybb->get_input("action");
}
$plugins->run_hooks("groupthread_start");
if($action == "reply" && $mybb->request_method== "post" && verify_post_check($mybb->input['my_post_key']))
{
    // We use the thread handler here.
    if(!in_array($uid, $members) && !in_array($uid, $leaders) || $groupinfo['locked']
        && !$socialgroups->socialgroupsuserhandler->is_moderator($groupinfo['gid'], $mybb->user['uid']))
    {
        error_no_permission();
    }
    // A check is required due to XSS attempts.
    if($groupinfo['locked'])
    {
        error_no_permission();
    }
    $socialgroups->socialgroupsthreadhandler->new_post($mybb->input);
}
$verifyactions = array("deletepost", "lock", "unlock", "unapprove", "approve", "sticky", "unsticky");
if(in_array($action, $verifyactions))
{
    verify_post_check($mybb->input['my_post_key']);
}
if($action == "deletepost" && $mybb->input['pid'])
{
    $socialgroups->socialgroupsthreadhandler->delete_post($mybb->get_input("pid", MyBB::INPUT_INT), $gid, 0);
}
if($action == "lock")
{
    $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET closed=1 WHERE tid IN($tid)");
    $message = "The thread has been locked.";
    $modaction = "Locked Thread";
}
if($action == "unlock")
{
    $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET closed=0 WHERE tid IN($tid)");
    $message = "The thread has been unlocked.";
    $modaction = "Unlocked Thread";
}
if($action == "unapprove")
{
    $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET visible=0 WHERE tid IN($tid)");
    $message = "The thread has been unapproved.";
    $modaction = "Unapproved Thread";
    $socialgroups->recount_threads($gid);
}
if($action == "approve")
{
    $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET visible=1 WHERE tid IN($tid)");
    $message = "The thread has been approved.";
    $modaction= "Approved Thread.";
    $socialgroups->recount_threads($gid);
}
if($action == "sticky")
{
    $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET sticky=1 WHERE tid IN($tid)");
    $message = "The thread has been sticked.";
    $modaction = "Stickied Thread";
}
if($action == "unsticky")
{
    $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET sticky=0 WHERE tid IN($tid)");
    $message = "The thread has been unstuck.";
    $modaction = "Unstuck Thread";
}
if($mybb->input['my_post_key'] && $action != "reply")
{
    $data = array(
        "gid" => $gid,
        "groupname" => $db->escape_string($socialgroups->group[$gid]['name']),
        "tids" => $tid,
        "action" => $modaction
    );
    log_moderator_action($data, $modaction);
    redirect("groupthread.php?tid=$tid", $message);
}
// Load our posts
$visible = 1;
if($socialgroups->socialgroupsuserhandler->is_leader($gid, $uid) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
{
    $visible = 0;
}
// How many pages are there?
$query = $db->simple_select("socialgroup_posts", "COUNT(pid) as total", "tid=$tid AND visible >= $visible");
$total = $db->fetch_field($query, "total");
$db->free_result($query);
$pages = ceil($total / 20);
if($mybb->input['page'])
{
    $page = (int) $mybb->input['page'];
}
else
{
    $page = 1;
}
if($page < 1)
{
    $page = 1;
}
if($page > $pages)
{
    $page = $pages;
}
$pagination = multipage($total, 20, $page, "groupthread.php?tid=$tid");
$posts = $socialgroups->socialgroupsthreadhandler->load_posts($gid, $tid, $page, 20);
$parser = new PostParser();
$forum = array(
    "allowhtml" => 0,
    "allowmycode" => 1,
    "allowsmilies" => 1,
    "allowimgcode" => 1,
    "allowvideocode" => 1
);
foreach($posts as $post)
{
    $classic_header = '';
    if($mybb->settings['postlayout'] == "classic")
    {
        eval("\$classic_header = \"".$templates->get("showthread_classic_header")."\";");
    }
    $plugins->run_hooks("groupthread_post_post");
    $postlist .= build_postbit($post);
}
// Figure out if the member can reply to the thread.
$canreply = FALSE;

if($threadinfo['closed'] == 0)
{
    if(in_array($uid,$members) && $permissions['postreplies'] == 1)
    {
        $canreply= TRUE;
    }
    if($groupinfo['locked'])
    {
        $canreply = false;
    }
}
if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid) ||$socialgroups->socialgroupsuserhandler->is_leader($gid, $uid))
{
    $canreply = TRUE;
}
if($groupinfo['locked'])
{
    $canreply = false;
}
if($canreply)
{
    $codebuttons = build_mycode_inserter();
    $smileys = build_clickable_smilies();
    $plugins->run_hooks("groupthread_quickreply");
    if(in_array($uid, $leaders) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid))
    {
        $ismod = TRUE;
        if($threadinfo['closed'])
        {
            $closedchecked = "checked=\"checked\"";
            $trow = "trow_shaded";
        }
        if($threadinfo['sticky'])
        {
            $stickycheck = "checked=\"checked\"";
        }
        eval("\$replyboxmod =\"".$templates->get("socialgroups_replybox_mod")."\";");
    }
    if($threadinfo['closed'] == 1 && !$canreply)
    {
        eval("\$newreply = \"".$templates->get("showthread_newreply_closed")."\";");
    }
    eval("\$replybox =\"".$templates->get("socialgroups_replybox")."\";");
}
if($ismod)
{
    eval("\$modtools =\"".$templates->get("socialgroups_thread_tools")."\";");
}
// Group jump menu
$groupjumpmenu = "";
if($mybb->settings['socialgroups_showgroupjump'] && $mybb->user['uid'] > 0)
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
$db->free_result($query);
$plugins->run_hooks("groupthread_end");
eval("\$groupthreadpage =\"".$templates->get("socialgroups_groupthread_page")."\";");
// Before outputting the page, we need to replace certain values that are spawned from using build_ppostbit.
$groupthreadpage = str_replace(array("editpost.php?pid=", "showthread.php", "newreply.php"), array("groupthread.php?action=edit&amp;pid=", "groupthread.php", "groupthread.php?action=newreply"), $groupthreadpage);
output_page($groupthreadpage);
