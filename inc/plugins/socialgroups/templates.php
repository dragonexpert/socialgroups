<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */

/* This file handles all templates for Social Groups. Additional templates should be added at the bottom of socialgroups_insert_templates.*/


if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

function socialgroups_insert_templates()
{
    global $db;
    // Create a template group just for social groups since this will get big.

    $template_group = array(
        "prefix" => "socialgroups",
        "title" => "<lang:socialgroup>",
        "isdefault" => 0
    );

    $db->insert_query("templategroups", $template_group);

    // Templates go here.  The key should start with socialgroups_.

    // Now go through each of the themes
    $themequery = $db->simple_select("themes", "*");
    $sids = array();
    $first = true;
    $template_json = json_decode(file_get_contents(MYBB_ROOT . "/inc/plugins/socialgroups/templates.json", true), true);
    while($theme = $db->fetch_array($themequery))
    {
        //$my_template = $my_new_template = array();
        $properties = unserialize($theme['properties']);
        $sid = $properties['templateset'];
        if(!in_array($sid, $sids))
        {
            array_push($sids, $sid);

            foreach ($template_json as $key)
            {
                $my_template[] = array(
                    "title" => $db->escape_string($key['title']),
                    "template" => $db->escape_string($key['template']),
                    "sid" => $sid,
                    "version" => '1824',
                    "dateline" => TIME_NOW
                );
                if($first)
                {
                    $my_new_template[] = array(
                        "title" => $db->escape_string($key['title']),
                        "template" => $db->escape_string($key['template']),
                        "sid" => -2,
                        "version" => "1824",
                        "dateline" => TIME_NOW
                    );
                }
            }
            // Now that that theme is done, insert all templates for that theme
            $db->insert_query_multiple("templates", $my_template);
            $db->insert_query_multiple("templates", $my_new_template);
            unset($my_template, $my_new_template);
            $first = false;
        }
    }

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    // Use this area for modifying existing templates.
    $indexquery = $db->simple_select("templates", "*", "title IN('header_welcomeblock_member','index')");
    $welcomeblockdone = $indexdone = $usercpdone = false;
    while($template_info = $db->fetch_array($indexquery))
    {
        if (!strpos($template_info['template'], "{\$socialgroupslink}") && $template_info['title'] == "header_welcomeblock_member" && !$welcomeblockdone)
        {
            find_replace_templatesets('header_welcomeblock_member', "#" . preg_quote('{$usercplink}') . "#i", "{\$usercplink}\n{\$socialgroupslink}");
            $welcomeblockdone = true;
        }
        if (!strpos($template_info['template'], "{\$socialgroupslink}") && $template_info['title'] == "header_welcomeblock_member" && !$indexdone)
        {
            find_replace_templatesets('index', "#" . preg_quote('{$forums}') . "#i", "{\$forums}\n<div id=\"socialgroups_mygroups\"><h2>My Groups</h2>{\$mygroups}");
            $indexdone = true;
        }
    }
    $db->free_result($indexquery);
}

function socialgroups_delete_templates()
{
    global $db;
    $db->delete_query("templategroups", "title='<lang:socialgroup>'");
    $db->delete_query("templates", "title LIKE 'socialgroups_%'");

    // Use this area for undoing template changes.
    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    find_replace_templatesets('header_welcomeblock_member', "#" . preg_quote("\n{\$socialgroupslink}") . "#i", '');
    find_replace_templatesets('index', "#" . preg_quote("\n<div id=\"socialgroups_mygroups\"><h2>My Groups</h2>{\$mygroups}") . "#i", '');
}