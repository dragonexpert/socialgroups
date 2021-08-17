<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
require_once "socialgroups/hooks.php";


function socialgroups_info()
{
    $donation_link = "<a href='https://www.paypal.com/donate?hosted_button_id=EYFBDTL42YYDW' target='_blank'>Donating</a>";
    return array(
        "name" => "Social Groups",
        "description" => "Allows users to create their own groups. If this plugin helps you, please consider " . $donation_link . " to help support the developer." ,
        "author" => "Mark Janssen",
        "version" => "1827",
        "codename" => "socialgroups",
        "lastupdated" => 1629217122
    );
}


function socialgroups_install()
{
    global $db;
    require_once "socialgroups/db.php";
    socialgroups_create_tables();
    require_once "socialgroups/settings.php";
    socialgroups_insert_settings();
    require_once "socialgroups/classes/socialgroupsapi.php";
    $socialgroups = new socialgroups(0, false, false, false, false);
    $socialgroups->socialgroupsapi->send_server_info();
    // Create the task
    $socialgroups_task = array(
        "title" => "Socialgroups Stats",
        "description" => "Updates the stats for socialgroups.",
        "file" => "socialgroups_stats",
        "minute" => 0,
        "hour" => 0,
        "day" => "*",
        "month" => "*",
        "weekday" => "*",
        "nextrun" => time() + 86400,
        "lastrun" => time(),
        "enabled" => 1,
        "logging" => 1,
        "locked" => 0
    );
    $db->insert_query($socialgroups_task);
}

function socialgroups_is_installed()
{
    global $db;
    return $db->table_exists("socialgroups");
}

function socialgroups_activate()
{
    require_once "socialgroups/templates.php";
    socialgroups_insert_templates();
}

function socialgroups_deactivate()
{
    require_once "socialgroups/templates.php";
    socialgroups_delete_templates();
}

function socialgroups_uninstall()
{
    global $db;
    require_once "socialgroups/db.php";
    socialgroups_drop_tables();
    require_once "socialgroups/settings.php";
    socialgroups_delete_settings();
    $db->delete_query("tasks", "title='Socialgroups Stats'");
}

function update_socialgroups()
{
    require_once "socialgroups/classes/socialgroups.php";
    $socialgroups = new socialgroups(0, false, false, false, false);
    $socialgroups->update_cache();
}

function update_socialgroups_categories()
{
    require_once "socialgroups/classes/socialgroups.php";
    $socialgroups = new socialgroups(0, false, false, false, false);
    $socialgroups->update_socialgroups_category_cache();
}
