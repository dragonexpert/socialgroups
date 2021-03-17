<?php
/* Socialgroups Plugin
* Author: Mark Janssen
*/
define("IN_MYBB", 1);
define("THIS_SCRIPT", "groups.php");
$templatelist = "socialgroups_category,socialgroups_group,socialgroups_groups,socialgroups_category_split";
$templatelist .= ",socialgroups_clear,socialgroups_create_group_button,socialgroups_logo,index_whosonline_memberbit,forumbit_moderators_group,forumbit_moderators_user";
$templatelist .= ",socialgroups_group_lastpost,socialgroups_group_lastpost_never";
require_once "global.php";
$lang->load("socialgroups");
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$usergroups = $cache->read("usergroups");
$categoryinfo = $cache->read("socialgroups_categories");
$members = $socialgroups->socialgroupsuserhandler->load_moderators();
add_breadcrumb($lang->socialgroups, "groups.php");
// This code fetches the moderators
$moderators = $comma = "";
if(count($members['users']) >= 1)
{
    $query = $db->simple_select("users", "uid, username, usergroup, displaygroup", "uid IN(" . implode($members['users']) . ")");
    while($moderator = $db->fetch_array($query))
    {
        $moderator['formattedname'] = format_name(htmlspecialchars_uni($moderator['username']), $moderator['usergroup'], $moderator['displaygroup']);
        $moderator['profilelink'] = $comma . build_profile_link($moderator['formattedname'], $moderator['uid']);
        eval("\$moderators .= \"".$templates->get("forumbit_moderators_user")."\";");
        $comma = ", ";
    }
}

$moderator = array();
foreach($members['usergroups'] as $usergroup => $value)
{
    $moderator['title'] = format_name($usergroups[$value]['title'], $value, $value);
    eval("\$moderators .= \"".$templates->get("forumbit_moderators_group")."\";");
    $comma = ", ";
}
$comma = "";

$cutoff = TIME_NOW - 900; // 15 minutes
// Lets show off the users browsing.
$query = $db->query("SELECT s.*, u.username, u.usergroup, u.displaygroup, u.invisible FROM
		" . TABLE_PREFIX . "sessions s
		LEFT JOIN " . TABLE_PREFIX . "users u ON s.uid=u.uid
		WHERE s.time >= $cutoff AND s.uid !=0 AND s.location LIKE '%groups.php%'
		ORDER BY u.username ASC");

$userbrowsing = $invisiblemark = "";
while($user = $db->fetch_array($query))
{
    $profilelink = build_profile_link(format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']), $user['uid']);
    if($user['invisible'] == 1 && $mybb->usergroup['canviewwolinvis'] || $mybb->user['uid'] == $user['uid'])
    {
        $invisiblemark = "*";
    }
    if($user['invisible'] == 1 && $mybb->usergroup['canviewwolinvis'] || $user['invisible'] != 1)
    {
        $user['profilelink'] = $comma . $profilelink;
        eval("\$userbrowsing .= \"" . $templates->get("index_whosonline_memberbit") . "\";");
    }
    $comma = ", ";
}
$db->free_result($query);

// Is there a category already set?
if($mybb->get_input("cid"))
{
    $cidonly = $db->escape_string($mybb->get_input("cid"));
    // If it is staff only and they aren't staff, unset it.
    if($categoryinfo[$cidonly]['staffonly'] && !$mybb->usergroup['canmodcp'])
    {
        $cidonly = 0;
        $mybb->input['cid'] = 0;
    }
    else
    {
        add_breadcrumb(stripcslashes($categoryinfo[$cidonly]['name']), $socialgroups->breadcrumb_link("category", $cidonly, $categoryinfo[$cidonly]['name']));
    }
}
else
{
    $cidonly = "";
}

if($mybb->get_input("sort"))
{
    $sort = $db->escape_string($mybb->get_input("sort"));
    $sorturl = "&sort=$sort";
}
else
{
    $sort = $sorturl = "";
}

$keywords = $keywordsurl = "";
if($mybb->get_input("keywords"))
{
    $keywords = $mybb->get_input("keywords");
    $keywordsurl = "&keywords=$keywords";
}

$groups = $socialgroups->list_groups((string) $cidonly, $sort, $keywords);
$grouphtml = $socialgroups->render_groups();
$clearfilter = "";
if($cidonly || $keywords)
{
    eval("\$clearfilter =\"".$templates->get("socialgroups_clear")."\";");
}
$query = $db->simple_select("socialgroups", "COUNT(gid) as totalgroups", "uid=" . $mybb->user['uid']);
$totalgroups = $db->fetch_field($query, "totalgroups");
$creategroupbutton = "";
if($mybb->usergroup['maxsocialgroups_create'] == 0 || $totalgroups < $mybb->usergroup['maxsocialgroups_create'])
{
    eval("\$creategroupbutton =\"".$templates->get("socialgroups_create_group_button")."\";");
}
eval("\$socialgrouppage =\"".$templates->get("socialgroups_groups")."\";");
output_page($socialgrouppage);
