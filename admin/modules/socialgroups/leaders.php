<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
if(!$mybb->input['action'])
{
    $mybb->input['action'] = "browse";
}

require_once MYBB_ROOT . "/inc/plugins/socialgroups/classes/socialgroups.php";
$socialgroups = new socialgroups();
$page->output_header("Social Group Leaders");

$baseurl = "index.php?module=socialgroups-leaders";

// Default Routes Always There
$sub_tabs['browse'] = array(
    'title'         => 'Browse',
    'link'          => $baseurl,
    'description'   => 'Browse Leaders'
);

$sub_tabs['create'] = array(
    'title'         => 'Add Leader',
    'link'          => $baseurl . '&action=add',
    'description'   => 'Add a Leader'
);

$table = new TABLE;

switch($mybb->input['action'])
{
    case "browse":
        $page->output_nav_tabs($sub_tabs, 'browse');
        socialgroups_leaders_browse();
        break;
    case "add":
        $page->output_nav_tabs($sub_tabs, 'create');
        socialgroups_leaders_add();
        break;
    case "delete":
        $sub_tabs['delete'] = array(
            'title'         => 'Remove Leader',
            'link'          => $baseurl . '&action=delete&lid='.$mybb->input['lid'],
            'description'   => 'Remove a Leader'
        );

        $page->output_nav_tabs($sub_tabs, 'delete');
        socialgroups_leaders_delete($mybb->input['lid']);
        break;
    default:
        $page->output_nav_tabs($sub_tabs, 'browse');
        socialgroups_leaders_browse();
        break;
}

function socialgroups_leaders_browse()
{
    global $db, $table, $baseurl;
    $query = $db->query("SELECT l.*, g.name, u.username, u.usergroup, u.displaygroup FROM " . TABLE_PREFIX . "socialgroup_leaders l
    LEFT JOIN " . TABLE_PREFIX . "socialgroups g ON (l.gid=g.gid)
    LEFT JOIN " . TABLE_PREFIX . "users u on (l.uid=u.uid) ORDER BY u.username ASC");
    $table->construct_header("Username");
    $table->construct_header("Group");
    $table->construct_header("Manage");
    $table->construct_row();
    while($leader = $db->fetch_array($query))
    {
        $table->construct_cell(format_name($leader['username'], $leader['usergroup'],$leader['displaygroup']));
        $editgrouplink = "index.php?module=socialgroups-groups&action=edit&gid=" . $leader['gid'];
        $table->construct_cell("<a href='" . $editgrouplink . "'>" . htmlspecialchars_uni($leader['name']) . "</a>");
        $deletelink = $baseurl . "&action=delete&lid=" . $leader['lid'];
        $edituserlink = "index.php?module=user-users&action=edit&uid=" . $leader['uid'];
        $table->construct_cell("<a href='" . $edituserlink . "'>Edit User</a><br /><a href='" . $deletelink . "'>Delete</a>");
        $table->construct_row();
    }
    $table->output("Social Group Leaders");
}

function socialgroups_leaders_add()
{
    global $mybb, $db, $baseurl, $cache;
    if($mybb->request_method == "post")
    {
        // First we need to get a userid from username
        $options = array("fields" => "uid", "username", "usergroup");
        $user = get_user_by_username($mybb->input['username'], $options);
        $gid = (int) $mybb->input['gid'];
        if(!$user['uid'])
        {
            flash_message("Invalid User.", "error");
            admin_redirect($baseurl);
        }
        // Load the $cache and make sure the user isn't in a banned group.
        $usergroups = $cache->read("usergroups");
        if($usergroups[$user['usergroup']]['isbannedgroup'] == 1)
        {
            flash_message("The user specified is banned.", "error");
            admin_redirect($baseurl);
        }
        // Are they already a leader?
        $query = $db->simple_select("socialgroup_leaders", "gid, uid", "uid=" . $user['uid'] . " AND gid=" . $gid);
        if($db->num_rows($query) == 1)
        {
            flash_message("The user is already a leader for this group.", "error");
            admin_redirect($baseurl);
        }
        // We are good to go.
        $new_leader = array(
            "uid" => $user['uid'],
            "gid" => $gid
        );
        $db->insert_query("socialgroup_leaders", $new_leader);
        flash_message("The user has been added as a leader successfully.", "success");
        admin_redirect($baseurl);
    }
    else
    {
        $form = new DefaultForm("index.php?module=socialgroups-leaders&action=add", "post");
        $form_container = new FormContainer("Add Leader");
        $form_container->output_row("Username", "", $form->generate_text_box("username"));
        $query = $db->simple_select("socialgroups", "gid, name", "", array("sortby" => "name", "sort_dir" => "asc"));
        while($group = $db->fetch_array($query))
        {
            $grouplist[$group['gid']] = $group['name'];
        }
        $form_container->output_row("Group", "", $form->generate_select_box("gid", $grouplist));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Add Leader")));
        $form->end();
    }
}

function socialgroups_leaders_delete($lid)
{
    global $mybb, $db, $baseurl;
    $lid = (int) $lid;
    if($mybb->request_method == "post")
    {
        if($mybb->input['confirm'] == 1)
        {
            $db->delete_query("socialgroup_leaders", "lid=$lid");
            flash_message("The leader has been deleted.","success");
        }
        admin_redirect($baseurl);
    }
    else
    {
        $form = new Form($baseurl . "&action=delete&lid=$lid", "post");
        $form_container = new FormContainer("Confirm Deletion");
        $form_container->output_row("Are you sure?", "", $form->generate_yes_no_radio("confirm", 0));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Delete Leader")));
        $form->end();
    }
}

$page->output_footer();