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
// Lets show off the groups
// .htaccess stuff
if($mybb->input['category'])
{
    $category = $mybb->get_input("category");
    $query = $db->simple_select("socialgroup_categories", "*", "name='$category'");
    $categoryinfo = $db->fetch_array($query);
    if($categoryinfo['staffonly'] > $mybb->usergroup['canmodcp']) // Eliminate the filter if they don't have permission to view.
    {
        $mybb->input['cid'] = 0;
    }
    if($categoryinfo['cid'])
    {
        $mybb->input['cid'] = $categoryinfo['cid'];
        add_breadcrumb(stripcslashes($categoryinfo['name']), "groups.php?cid=" . $mybb->input['cid']);
    }
    else
    {
        $mybb->input['cid'] = 0;
    }
}

// Is there a category already set?
$mybb->input['cid'] = $db->escape_string($mybb->get_input("cid")); // Escape String is used to allow for csv lists of allowed categories
if($mybb->input['cid'] >= 0)
{
    $cidonly = $mybb->input['cid'];
    add_breadcrumb(stripcslashes($categoryinfo['name']), "groups.php?cid=" . $mybb->input['cid']);
}
else
{
    $cidonly = 0;
}
if($mybb->input['sort'])
{
    $sort = $db->escape_string($mybb->get_input("sort"));
    $sorturl = "&sort=$sort";
}
if($mybb->input['keywords'])
{
    $keywords = $mybb->input['keywords'];
    $keywordsurl = "&keywords=$keywords";
}
$groups = $socialgroups->list_groups($cidonly, $sort, $keywords);
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