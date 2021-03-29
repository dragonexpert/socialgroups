<?php
/**
 * Socialgroups plugin is created by Mark Janssen.
 * This page should be used for handling all hooks.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

$plugins->add_hook("admin_load", "socialgroups_admin_load");
$plugins->add_hook("global_start", "socialgroups_global_start");
$plugins->add_hook("global_intermediate", "socialgroups_global_intermediate");
$plugins->add_hook("admin_formcontainer_end", "socialgroups_admin_formcontainer_end");
$plugins->add_hook("admin_user_groups_edit_commit", "socialgroups_admin_user_groups_edit_commit");
$plugins->add_hook("modcp_modlogs_start", "socialgroups_modcp_modlogs_start");
$plugins->add_hook("modcp_modlogs_result", "socialgroups_modcp_modlogs_result");
$plugins->add_hook("postbit", "socialgroups_postbit");
$plugins->add_hook("xmlhttp", "socialgroups_xmlhttp");
$plugins->add_hook("fetch_wol_activity_end", "socialgroups_fetch_wol_activity_end");
$plugins->add_hook("build_friendly_wol_location_end", "socialgroups_build_friendly_wol_location_end");
$plugins->add_hook("admin_tools_get_admin_log_action", "socialgroups_admin_tools_get_admin_log_action");

function socialgroups_admin_load()
{
    global $lang;
    $lang->load("socialgroups");
}

function socialgroups_admin_tools_get_admin_log_action($plugin_array)
{
    // Announcements
    if($plugin_array['logitem']['module'] == "socialgroups-announcements")
    {
        if($plugin_array['logitem']['data']['action'] == "announcements_add")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_announcements_announcements_add";
        }
        if($plugin_array['logitem']['data']['action'] == "announcements_edit")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_announcements_announcements_edit";
        }
        if($plugin_array['logitem']['data']['action'] == "announcements_delete")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_announcements_announcements_delete";
        }
    }

    // Categories
    if($plugin_array['logitem']['module'] == "socialgroups-category")
    {
        if($plugin_array['logitem']['data']['action'] == "category_add")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_category_category_add";
        }
        if($plugin_array['logitem']['data']['action'] == "category_edit")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_category_category_edit";
        }
        if($plugin_array['logitem']['data']['action'] == "category_delete")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_category_category_delete";
        }
        if($plugin_array['logitem']['data']['action'] == "category_merge")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_category_category_merge";
        }
    }

    // Groups
    if($plugin_array['logitem']['module'] == "socialgroups-groups")
    {
        if($plugin_array['logitem']['data']['action'] == "groups_add")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_groups_groups_add";
        }
        if($plugin_array['logitem']['data']['action'] == "groups_edit")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_groups_groups_edit";
        }
        if($plugin_array['logitem']['data']['action'] == "groups_delete")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_groups_groups_delete";
        }
    }

    // Leaders
    if($plugin_array['logitem']['module'] == "socialgroups-leaders")
    {
        if($plugin_array['logitem']['data']['action'] == "leaders_add")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_leaders_leaders_add";
        }
        if($plugin_array['logitem']['data']['action'] == "leaders_delete")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_leaders_leaders_delete";
        }
    }

    // Moderators
    if($plugin_array['logitem']['module'] == "socialgroups-moderators")
    {
        if($plugin_array['logitem']['data']['action'] == "moderators_add")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_moderators_moderators_add";
        }
        if($plugin_array['logitem']['data']['action'] == "moderators_delete")
        {
            $plugin_array['lang_string'] = "admin_log_socialgroups_moderators_moderators_delete";
        }
    }


    return $plugin_array;
}

function socialgroups_global_start()
{
    global $groupzerogreater, $mybb, $mybbgroups, $templatelist;
    $groupzerogreater[] = "maxsocialgroups_create";
//    $mybb->usergroup = usergroup_permissions($mybbgroups);
    $templatelist .= ",socialgroups_modcp_logitem,socialgroups_welcomeblock_member,socialgroups_welcomeblock_admin";
}

function socialgroups_global_intermediate()
{
    global $mybb, $socialgroups, $templates, $socialgroupslink;
    if(!is_object($socialgroups))
    {
        require_once MYBB_ROOT . "/inc/plugins/socialgroups/classes/socialgroups.php";
        $socialgroups = new socialgroups();
    }
    if($socialgroups->socialgroupsuserhandler->can_groupcp($mybb->user['uid']))
    {
        eval("\$socialgroupscplink = \"".$templates->get("socialgroups_welcomeblock_admin")."\";");
    }
    eval("\$socialgroupslink = \"".$templates->get("socialgroups_welcomeblock_member")."\";");
}

function socialgroups_admin_formcontainer_end()
{
    global $run_module, $form_container, $lang, $form, $mybb;
    if($run_module == 'user' && !empty($form_container->_title) & !empty($lang->users_permissions) & $form_container->_title == $lang->users_permissions)
    {
        // Load the language
        $lang->load('socialgroups');
        $socialgroups_options = array();
        $socialgroups_options[] = $form->generate_check_box('cancreatesocialgroups', 1, $lang->can_create_social_groups, array('checked' => $mybb->get_input('cancreatesocialgroups', 1)));
        $form_container->output_row($lang->social_groups, '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $socialgroups_options).'</div>');
        $form_container->output_row($lang->socialgroups_max_create, $lang->socialgroups_max_create_description, $form->generate_numeric_field('maxsocialgroups_create', $mybb->get_input('maxsocialgroups_create', 1), array('id' => 'maxsocialgroups_create')), 'maxsocialgroups_create');
        $form_container->output_row($lang->socialgroups_auto_approve, $lang->socialgroups_auto_approve_description, $form->generate_yes_no_radio("socialgroups_auto_approve", $mybb->get_input('socialgroups_auto_approve'), true));
    }
}

function socialgroups_admin_user_groups_edit_commit()
{
    global $updated_group, $mybb;
    $updated_group['cancreatesocialgroups'] = $mybb->get_input('cancreatesocialgroups', 1);
    $updated_group['maxsocialgroups_create'] = $mybb->get_input('maxsocialgroups_create', 1);
    $updated_group['socialgroups_auto_approve'] = $mybb->get_input('socialgroups_auto_approve', 1);
}

function socialgroups_modcp_modlogs_start()
{
    global $mybb, $where;
    if($mybb->input['gid'])
    {
        $where .= " AND l.gid=" . $mybb->get_input("gid") . " ";
    }
}

function socialgroups_modcp_modlogs_result()
{
    global $mybb, $logitem, $information, $logdata, $templates;
    $logdata = my_unserialize($logitem['data']);
    if($logdata['gid'])
    {
        // Involves a social group
        $gid = (int) $logdata['gid'];
        $groupname = htmlspecialchars_uni($logdata['groupname']);
        if($logdata['tidlist'])
        {
            $threadheader = "<br /><strong>Threads: </strong>";
            $tidlist = $logdata['tids'];
        }
        eval("\$information.=\"".$templates->get("socialgroups_modcp_logitem")."\";");
    }
}

function socialgroups_postbit(&$post)
{
    global $mybb, $cache, $usergroups, $socialgroups, $templates, $lang;
    if(!$socialgroups)
    {
        return;
    }
    $socialgroups->load_permissions($post['gid']);
    if(!is_array($usergroups))
    {
        $usergroups = $cache->read("usergroups");
    }
    if(THIS_SCRIPT != "groupthread.php") // Only run on groupthread.php
    {
        return;
    }
    // button_edit
    // todo delete button, report button
    $badkeys = array("button_report", "button_multiquote", "button_quickrestore", "button_quickdelete");
    foreach($badkeys as $key)
    {
        $post[$key] = "";
    }
    // Now our custom templates
    eval("\$post['button_report'] =\"".$templates->get("socialgroups_postbit_report")."\";");
    $postusergroups = $post['usergroup'];
    if($post['additionalgroups'])
    {
        $postusergroups .= "," . $post['additionalgroups'];
    }
    // Now loop through the cache to determine permissions
    $exgroups = explode(",", $postusergroups);
    foreach($exgroups as $group)
    {
        if(!$usergroups[$group]['canbereported'])
        {
            $post['button_report'] = "";
        }
        $permissions = $socialgroups->load_permissions($post['gid']);
        if($permissions[$post['gid']]['deleteposts'] == 1 && $post['uid'] == $mybb->user['uid'] ||
            $socialgroups->socialgroupsuserhandler->is_moderator($post['gid'], $mybb->user['uid']) ||
            $socialgroups->socialgroupsuserhandler->is_leader($post['gid'], $mybb->user['uid']))
        {
            if($post['pid'] != $post['firstpost']) // Don't allow the deletion of the first post.  Use the delete thread tool instead.
            {
                eval("\$post['button_quickdelete'] =\"".$templates->get("socialgroups_postbit_delete")."\";");
            }
        }
    }
}

function socialgroups_xmlhttp()
{
    global $mybb;
    if($mybb->input['action'] == "socialgroups_manage_leaders")
    {
        $userid = (int) $mybb->input['uid'];
        $gid = (int) $mybb->input['gid'];
        require_once "inc/plugins/socialgroups/classes/socialgroups.php";
        $socialgroups = new socialgroups($gid);
        $is_leader = $socialgroups->socialgroupsuserhandler->is_leader($gid, $userid);
        echo $is_leader;
    }
    else
    {
        return;
    }
}

function socialgroups_fetch_wol_activity_end($user_activity)
{
    global $parameters;
    if(stripos($user_activity['location'], "groups.php"))
    {
        $user_activity['activity'] = "groups";
    }
    if(stripos($user_activity['location'], "showgroup.php"))
    {
        $user_activity['activity'] = "showgroup";
        $user_activity['gid'] = $parameters['gid'];
    }
    if(stripos($user_activity['location'], "groupthread.php"))
    {
        $user_activity['activity'] = "groupthread";
        $user_activity['tid'] = $parameters['tid'];
    }
    if(stripos($user_activity['location'], "groupcp.php"))
    {
        $user_activity['activity'] = "groupcp";
    }
    if(stripos($user_activity['location'], "editgroup.php"))
    {
        $user_activity['activity'] = "editgroup";
    }
    return $user_activity;
}

function socialgroups_build_friendly_wol_location_end($array)
{
    global $db, $cache, $mybb, $socialgroups, $groupthreads;
    if(!is_object($socialgroups))
    {
        require_once $mybb->settings['bburl'] . "/inc/plugins/socialgroups/classes/socialgroups.php";
        $socialgroups = new socialgroups(0, false, false, false, false);
    }
    $socialgroupsinfo = $cache->read("socialgroups");
    if(!is_array($groupthreads))
    {
        $query = $db->simple_select("socialgroup_threads", "tid, subject");
        while($thread = $db->fetch_array($query))
        {
            $groupthreads[$thread['tid']] = $thread;
        }
        $db->free_result($query);
    }
    if($array['user_activity']['activity'] == "groups")
    {
        $array['location_name'] = "<a href='" . $mybb->settings['bburl'] . "/groups.php'>Viewing Groups</a>";
    }
    if($array['user_activity']['activity'] == "showgroup")
    {
            $gid = $array['user_activity']['gid'];
            $link = $socialgroups->grouplink($gid, $socialgroupsinfo[$gid]['name']);
            $array['location_name'] = "Viewing Group: " . $link;
    }
    if($array['user_activity']['activity'] == "groupthread")
    {
        $tid = $array['user_activity']['tid'];
        $link = $socialgroups->groupthreadlink($tid, $groupthreads[$tid]['subject']);
        $array['location_name'] = "Viewing Thread: " . $link;
    }
    if($array['user_activity']['activity'] == "groupcp")
    {
        if($socialgroups->socialgroupsuserhandler->can_groupcp($mybb->user['uid']))
        {
            $link = $mybb->settings['bburl'] . "/groupcp.php";
        }
        $array['location_name'] = "Group CP";
    }
    if($array['user_activity']['activity'] == "editgroup")
    {
        $array['location_name'] = "Editing Group";
    }
    return $array;
}