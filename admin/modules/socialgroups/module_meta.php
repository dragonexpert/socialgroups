<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

function socialgroups_meta()
{
    global $page, $lang, $plugins, $cache;
    $sub_menu = array();
    $sub_menu[10] = array("id" => "category", "title" => "Category", "link" => "index.php?module=socialgroups-category");
    $sub_menu[20] = array("id" => "groups", "title" => "Groups", "link" => "index.php?module=socialgroups-groups");
    $sub_menu[30] = array("id" => "announcements", "title" => "Announcements", "link" => "index.php?module=socialgroups-announcements");
    $sub_menu[40] = array("id" => "moderators", "title" => "Moderators", "link" => "index.php?module=socialgroups-moderators");
    $sub_menu[50] = array("id" => "leaders", "title" => "Leaders", "link" => "index.php?module=socialgroups-leaders");
    $sub_menu[60] = array("id" => "templates", "title" => "Templates", "link" => "index.php?module=socialgroups-templates");
    $sub_menu[70] = array("id" => "restore", "title" => "Deleted Content", "link" => "index.php?module=socialgroups-restore");
    $sub_menu = $plugins->run_hooks("admin_socialgroups_menu", $sub_menu);
    $plugincache = $cache->read("plugins");
    if(in_array("socialgroups", $plugincache['active']))
    {
        $page->add_menu_item("Social Groups", "socialgroups", "index.php?module=socialgroups", 384, $sub_menu);
    }
    return true;
}

function socialgroups_action_handler($action)
{
    global $page, $lang, $plugins, $db;
    $page->active_module = "socialgroups";

    $actions = array(
        "category" => array("active" => "category", "file" => "category.php"),
        "groups" => array("active" => "groups", "file" => "groups.php"),
        "announcements" => array("active" => "announcements", "file" => "announcements.php"),
        "moderators" => array("active" => "moderators", "file" => "moderators.php"),
        "leaders" => array("active" => "leaders", "file" => "leaders.php"),
        "templates" => array("active" => "templates", "file" => "templates.php"),
        "restore" => array("active" => "restore", "file" => "restore.php")
    );

    $actions = $plugins->run_hooks("admin_socialgroups_action_handler", $actions);

    if(isset($actions[$action]))
    {
        $page->active_action = $actions[$action]['active'];
        return $actions[$action]['file'];
    }
    else
    {
        $page->active_action = "groups";
        return "groups.php";
    }
}

function socialgroups_admin_permissions()
{
    global $lang, $plugins;

    $admin_permissions = array(
        "category" => "Can Manage Social Group Categories?",
        "groups" => "Can Manage Social Groups?",
        "announcements" => "Can Manage Social Group Announcements?",
        "moderators" => "Can Manage Social Group Moderators?",
        "leaders" => "Can Manage Social Group Leaders?",
        "templates" => "Can Manage Social Group Templates?",
        "restore" => "Can Manage Deleted Content?"
    );

    $admin_permissions = $plugins->run_hooks("admin_socialgroups_permissions", $admin_permissions);

    return array("name" => "Social Groups", "permissions" => $admin_permissions, "disporder" => 96);
}
