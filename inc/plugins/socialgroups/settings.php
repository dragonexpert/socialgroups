<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 * This file handles all new settings.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

function socialgroups_insert_settings()
{
    global $db;

    $new_setting_group = array(
        "name" => "socialgroups",
        "title" => "Social Group Settings",
        "description" => "Customize various aspects of social groups",
        "disporder" => 1,
        "isdefault" => 0
    );

    $gid = $db->insert_query("settinggroups", $new_setting_group);

    $new_settings = array();
    /*  All settings should start with socialgroups_.  Make sure all keys are defined or SQL errors will occur. */
    $new_settings[] = array(
        "name" => "socialgroups_enable",
        "title" => "Social Groups Enabled",
        "description" => "If Social Groups should be active.",
        "optionscode" => "yesno",
        "disporder" => 1,
        "value" => 1,
        "gid" => $gid
    );

    $new_settings[] = array(
        "name" => "socialgroups_moderate_groups",
        "title" => "Moderate New Groups?",
        "description" => "When set to yes, new groups must be approved for them to show up.",
        "optionscode" => "yesno",
        "disporder" => 2,
        "value" => 0,
        "gid" => $gid
    );

    $new_settings[] = array(
        "name" => "socialgroups_showgroupjump",
        "title" => "Show Group Jump Menu",
        "optionscode" => "yesno",
        "disporder" => 3,
        "value" => 1,
        "gid" => $gid
    );

    $db->insert_query_multiple("settings", $new_settings);
    rebuild_settings();
}

function socialgroups_delete_settings()
{
    global $db;

    $query = $db->simple_select("settinggroups", "gid", "name='socialgroups'");
    $gid = $db->fetch_field($query, "gid");
    if($gid) {
        $db->delete_query("settinggroups", "gid=$gid");
        $db->delete_query("settings", "gid=$gid");
    }
    rebuild_settings();
}