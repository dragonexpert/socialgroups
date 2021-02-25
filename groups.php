<?php
/* This is a sample page that shows how to get the moderators and display them.
* Normally you would evaluate templates, but for demonstration purposes echo is used.*/
define("IN_MYBB", 1);
define("THIS_SCRIPT", "groups.php");
$templatelist = "socialgroups_category,socialgroups_group,socialgroups_groups,socialgroups_category_split,socialgroups_clear,socialgroups_create_group_button";
require_once "global.php";
$lang->load("socialgroups");
require_once "inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$usergroups = $cache->read("usergroups");
$categoryinfo = $cache->read("socialgroups_categories");
$members = $socialgroups->socialgroupsuserhandler->load_moderators();
add_breadcrumb($lang->socialgroups, "groups.php");
if(count($members['users']) >= 1)
{
    $query = $db->simple_select("users", "uid, username, usergroup, displaygroup", "uid IN(" . implode($members['users']) . ")");
    $comma = "";
    while($user = $db->fetch_array($query))
    {
        $user['formattedname'] = format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']);
        $user['profilelink'] = build_profile_link($user['formattedname'], $user['uid']);
        $moderators .= $comma . $user['profilelink'];
        $comma = ", ";
    }
}

foreach($members['usergroups'] as $usergroup => $value)
{
    $formatted_usergroup = format_name($usergroups[$value]['title'], $value, $value);
    $moderators .= $comma . $formatted_usergroup;
    $comma = ", ";
}
unset($comma);

$cutoff = TIME_NOW - 900; // 15 minutes
// Lets show off the users browsing.
$query = $db->query("SELECT s.*, u.username, u.usergroup, u.displaygroup FROM
		" . TABLE_PREFIX . "sessions s
		LEFT JOIN " . TABLE_PREFIX . "users u ON s.uid=u.uid
		WHERE time >= $cutoff AND s.uid !=0 AND location LIKE '%groups.php\?'
		ORDER BY u.username ASC");

while($user = $db->fetch_array($query))
{
    $profilelink = build_profile_link(format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']), $user['uid']);
    $userbrowsing .= $comma . $profilelink;
    $comma = ", ";
}

// Count guests now.
$countquery = $db->simple_select("sessions", "COUNT(sid) as guestcount", "time >=$cutoff AND uid=0 AND location LIKE '%groups.php\?'");
$guestcount = $db->fetch_field($countquery, "guestcount");
if($guestcount)
{
    $userbrowsing .= ", and $guestcount guests";
}

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

if($mybb->input['keywords'])
{
    $keywords = $mybb->input['keywords'];
    $keywordsurl = "&keywords=$keywords";
}
else
{
    $keywords = $keywordsurl = "";
}

$groups = $socialgroups->list_groups((string) $cidonly, $sort, $keywords);
$grouphtml = $socialgroups->render_groups();
if($cidonly || $keywords)
{
    eval("\$clearfilter =\"".$templates->get("socialgroups_clear")."\";");
}
$query = $db->simple_select("socialgroups", "COUNT(gid) as totalgroups", "uid=" . $mybb->user['uid']);
$totalgroups = $db->fetch_field($query, "totalgroups");
if($mybb->usergroup['maxsocialgroups_create'] == 0 || $totalgroups < $mybb->usergroup['maxsocialgroups_create'])
{
    eval("\$creategroupbutton =\"".$templates->get("socialgroups_create_group_button")."\";");
}
eval("\$socialgrouppage =\"".$templates->get("socialgroups_groups")."\";");
output_page($socialgrouppage);
