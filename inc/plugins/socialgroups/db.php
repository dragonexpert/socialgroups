<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin
 * This file handles all database changes.
 */

if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

// Table definition
$tables = array();

$tables['socialgroups'] = array(
    "gid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "cid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "name" => array(
        "type" => "TEXT"
    ),
    "approved" => array(
        "type" => "INT",
        "default" => 1
    ),
    "description" => array(
        "type" => "TEXT"
    ),
    "logo" => array(
        "type" => "TEXT"
    ),
    "private" => array(
        "type" => "INT",
        "default" => 0
    ),
    "staffonly" => array(
        "type" => "INT",
        "default" => 0
    ),
    "inviteonly" => array(
        "type" => "INT",
        "default" => 0,
    ),
    "jointype" => array(
        "type" => "INT",
        "default" => 0
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "threads" => array(
        "type" => "INT",
        "default" => 0
    ),
    "posts" => array(
        "type" => "INT",
        "default" => 0,
    ),
    "locked" => array(
        "type" => "INT",
        "default" => 0
    )
);

$tables['socialgroup_categories'] = array(
    "cid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "disporder" => array(
        "type" => "INT",
        "default" => 1
    ),
    "name" => array(
        "type" => "TEXT",
    ),
    "groups" => array(
        "type" => "INT",
        "default" => 0
    ),
    "staffonly" => array(
        "type" => "INT",
        "default" => 0
    )
);

$tables['socialgroup_members'] = array(
    "mid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "dateline" => array(
        "type" => "BIGINT",
        "default" => 0
    )
);

$tables['socialgroup_member_permissions'] = array(
    "pid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true,
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "postthreads" => array(
        "type" => "INT",
        "default" => 1
    ),
    "postreplies" => array(
        "type" => "INT",
        "default" => 1
    ),
    "inviteusers" => array(
        "type" => "INT",
        "default" => 1
    ),
    "deleteposts" => array(
        "type" => "INT",
        "default" => 0
    )
);

$tables['socialgroup_moderators'] = array(
    "mid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    )
);

$tables['socialgroup_leaders'] = array(
    "lid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    )
);

$tables['socialgroup_invites'] = array(
    "id" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "touid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "fromuid" => array(
        "type" => "INT",
        "default" => 1
    )
);

$tables['socialgroup_announcements'] = array(
    "aid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "dateline" => array(
        "type" => "INT",
        "default" => 0
    ),
    "subject" => array(
        "type" => "TEXT",
    ),
    "message" => array(
        "type" => "TEXT"
    ),
    "active" => array(
        "type" => "INT",
        "default" => 1
    )
);

$tables['socialgroup_threads'] = array(
    "tid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "firstpost" => array(
        "type" => "INT",
        "default" => 0
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "subject" => array(
        "type" => "TEXT"
    ),
    "dateline" => array(
        "type" => "INT",
        "default" => 0
    ),
    "sticky" => array(
        "type" => "INT",
        "default" => 0
    ),
    "visible" => array(
        "type" => "INT",
        "default" => 1
    ),
    "closed" => array(
        "type" => "INT",
        "default" => 0
    ),
    "replies" => array(
        "type" => "INT",
        "default" => 0
    ),
    "views" => array(
        "type" => "INT",
        "default" => 0
    ),
    "lastposttime" => array(
        "type" => "INT",
        "default" => 0
    ),
    "lastpostuid" => array(
        "type" => "INT",
        "default" => 0
    ),
    "lastpostusername" => array(
        "type" => "TEXT"
    )
);

$tables['socialgroup_posts'] = array(
    "pid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "tid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 1
    ),
    "dateline" => array(
        "type" => "INT",
        "default" => 0
    ),
    "lastedit" => array(
        "type" => "INT",
        "default" => 0
    ),
    "lasteditby" => array(
        "type" => "INT",
        "default" => 0
    ),
    "editcount" => array(
        "type" => "INT",
        "default" => 0
    ),
    "reported" => array(
        "type" => "INT",
        "default" => 0
    ),
    "visible" => array(
        "type" => "INT",
        "default" => 1
    ),
    "ipaddress" => array(
        "type" => "VARBINARY",
        "length" => 16,
        "default" => "''"
    ),
    "message" => array(
        "type" => "TEXT"
    )
);

$tables['socialgroup_reported_posts'] = array(
    "rid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "pid" => array(
        "type" => "INT",
        "default" => 0
    ),
    "tid" => array(
        "type" => "INT",
        "default" => 0
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 0
    ),
    "dateline" => array(
        "type" => "INT",
        "default" => 0
    ),
    "reason" => array(
        "type" => "TEXT"
    ),
    "status" => array(
        "type" => "INT",
        "default" => 0
    ),
    "handledby" => array(
        "type" => "INT",
        "default" => 0
    ),
    "handledate" => array(
        "type" => "INT",
        "default" => 0
    )
);

$tables['socialgroup_join_requests'] = array(
    "rid" => array(
        "type" => "INT",
        "autoinc" => true,
        "isprimarykey" => true
    ),
    "gid" => array(
        "type" => "INT",
        "default" => 0
    ),
    "uid" => array(
        "type" => "INT",
        "default" => 0
    ),
    "dateline" => array(
        "type" => "INT",
        "default" => 0
    ),
    "approved" => array(
        "type" => "INT",
        "default" => 0
    )
);


function socialgroups_generate_table_sql(string $table_name, $array=array())
{
    global $db, $config;
    $type = $default = $autoinc = $isprimarykey = $length = $null = $unsigned = $comma = "";
    $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . $table_name . " (\n";
    if($config['database']['type'] == "mysqli")
    {
        foreach($array as $key => $value)
        {
            if($value['type'] == "INT")
            {
                $type = "INT";
            }
            if($value['type'] == "TEXT")
            {
                $type = "TEXT";
            }
            if($value['type'] == "VARCHAR")
            {
                $type = "VARCHAR";
            }
            if($value['type'] == "VARBINARY")
            {
                $type = "VARBINARY";
            }
            if(isset($value['length']))
            {
                $type .= "(" . $value['length'] . ")";
            }
            if(isset($value['unsigned']))
            {
                $unsigned = " UNSIGNED ";
            }
            if(isset($value['nullable']))
            {
                $null = " NULL ";
            }
            else
            {
                $null = " NOT NULL ";
            }
            if(isset($value['default']))
            {
                $default = " DEFAULT " . $value['default'];
            }
            if(isset($value['autoinc']))
            {
                $autoinc = " AUTO_INCREMENT ";
            }
            if(isset($value['isprimarykey']))
            {
                $null = $default = "";
                $isprimarykey = " PRIMARY KEY ";
            }
            $sql .= $comma . $key . " " . $type . $unsigned . $null . $default . $autoinc . $isprimarykey;
            $comma = ",\n";
            $type = $unsigned = $autoinc = $isprimarykey = $null = $default = "";
        }
        // The loop is now ended.
        $sql .= "\n) ENGINE = Innodb " . $db->build_create_table_collation();
    } // Ends MySQLi Database Type
    if($config['database']['type'] == "sqlite")
    {
        foreach($array as $key => $value)
        {
            if($value['type'] == "INT")
            {
                $type = "INT";
            }
            if($value['type'] == "TEXT")
            {
                $type = "TEXT";
            }
            if($value['type'] == "VARCHAR")
            {
                $type = "VARCHAR";
            }
            if($value['type'] == "VARBINARY")
            {
                $type = "BLOB";
            }
            if(isset($value['length']))
            {
                $type .= "(" . $value['length'] . ")";
            }
            if(isset($value['nullable']))
            {
                $null = " NULL ";
            }
            else
            {
                $null = " NOT NULL ";
            }
            if(isset($value['default']))
            {
                $default = " DEFAULT " . $value['default'];
            }
            if(isset($value['isprimarykey']))
            {
                $type = "INTEGER";
                $isprimarykey = " PRIMARY KEY ";
                $null = $default= "";
            }
            $sql .= $comma . $key . " " . $type . $null . $default . $isprimarykey;
            $comma = ",\n";
            $type = $unsigned = $autoinc = $isprimarykey = "";
        }
        // The loop is now ended.
        $sql .= "\n) " . $db->build_create_table_collation() . ";";
    } // Ends SQLite Database Type
    if($config['database']['type'] == "pgsql")
    {
        foreach ($array as $key => $value)
        {
            if ($value['type'] == "INT")
            {
                $type = "INT";
            }
            if ($value['type'] == "TEXT")
            {
                $type = "TEXT";
            }
            if ($value['type'] == "VARCHAR")
            {
                $type = "VARCHAR";
            }
            if ($value['type'] == "VARBINARY")
            {
                $type = "BYTEA";
            }
            if (isset($value['length']))
            {
                if ($type != "BYTEA")
                {
                    $type .= "(" . $value['length'] . ")";
                }
            }
            if (isset($value['nullable']))
            {
                $null = " NULL ";
            }
            else
            {
                $null = " NOT NULL ";
            }
            if (isset($value['default']))
            {
                $default = " DEFAULT " . $value['default'];
            }
            if (isset($value['isprimarykey']))
            {
                $null = $default = "";
                $type = "SERIAL";
                $isprimarykey = "PRIMARY KEY (" . $key . ")";
            }
            $sql .= $comma . $key . " " . $type . $null . $default;
            $comma = ",\n";
            $type = $null = $default = "";
        }
        // The loop is now ended.
        if ($isprimarykey != "")
        {
            $sql .= $comma . $isprimarykey;
        }
        $sql .= "\n) " . $db->build_create_table_collation() . ";";
    } // Ends PGSQL Database Type
    return $sql;
}


function socialgroups_create_tables()
{
    global $db, $cache, $mybb, $socialgroups;
    $charset = $db->build_create_table_collation();

    // TODO run the actual arrays through the function.

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroups (
    gid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cid INT NOT NULL DEFAULT 1,
    name TEXT,
    approved INT NOT NULL DEFAULT 1,
    description TEXT,
    logo TEXT,
    private INT NOT NULL DEFAULT 0,
    staffonly INT NOT NULL DEFAULT 0,
    inviteonly INT NOT NULL DEFAULT 0,
    jointype INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 1,
    threads INT NOT NULL DEFAULT 0,
    posts INT NOT NULL DEFAULT 0,
    locked INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_categories (
    cid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    disporder INT NOT NULL DEFAULT 1,
    name TEXT,
    groups INT NOT NULL DEFAULT 0,
    staffonly INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_members (
    mid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    uid INT NOT NULL DEFAULT 1,
    dateline BIGINT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_member_permissions (
    pid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    postthreads INT NOT NULL DEFAULT 1,
    postreplies INT NOT NULL DEFAULT 1,
    inviteusers INT NOT NULL DEFAULT 1,
    deleteposts INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_moderators (
    mid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");


    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_leaders (
    lid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL DEFAULT 1,
    gid INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_invites (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    touid INT NOT NULL DEFAULT 1,
    fromuid INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_announcements (
    aid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    uid INT NOT NULL DEFAULT 1,
    dateline BIGINT NOT NULL DEFAULT 0,
    subject TEXT,
    message TEXT,
    active INT NOT NULL DEFAULT 1
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_threads (
    tid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 1,
    firstpost INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 1,
    subject TEXT,
    dateline BIGINT NOT NULL DEFAULT 0,
    sticky INT NOT NULL DEFAULT 0,
    visible INT NOT NULL DEFAULT 1,
    closed INT NOT NULL DEFAULT 0,
    replies INT NOT NULL DEFAULT 0,
    views INT NOT NULL DEFAULT 0,
    lastposttime BIGINT NOT NULL DEFAULT 0,
    lastpostuid INT NOT NULL DEFAULT 1,
    lastpostusername TEXT
    ) ENGINE = Innodb$charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_posts (
    pid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tid INT NOT NULL DEFAULT 1,
    gid INT NOT NULL DEFAULT 1,
    uid INT NOT NULL DEFAULT 1,
    dateline BIGINT NOT NULL DEFAULT 0,
    lastedit BIGINT NOT NULL DEFAULT 0,
    lasteditby INT NOT NULL DEFAULT 0,
    editcount INT NOT NULL DEFAULT 0,
    reported INT NOT NULL DEFAULT 0,
    visible INT NOT NULL DEFAULT 1,
    ipaddress VARBINARY(16) NOT NULL DEFAULT '',
    message TEXT
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_reported_posts (
    rid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pid INT NOT NULL DEFAULT 0,
    tid INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 0,
    dateline BIGINT NOT NULL DEFAULT 0,
    reason TEXT,
    status TINYINT(1) NOT NULL DEFAULT 0,
    handledby INT NOT NULL DEFAULT 0,
    handledate BIGINT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    $db->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "socialgroup_join_requests (
    rid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    gid INT NOT NULL DEFAULT 0,
    uid INT NOT NULL DEFAULT 0,
    dateline BIGINT NOT NULL DEFAULT 0,
    approved INT NOT NULL DEFAULT 0
    ) ENGINE = Innodb $charset");

    // Create a category.  We are setting groups to 1 here because we are creating a group automatically.
    $new_category = array(
        "disporder" => 1,
        "name" => "General Category",
        "groups" => 1,
        "staffonly" => 0,
    );

    $cid = $db->insert_query("socialgroup_categories", $new_category);

    // Now create a group.
    $new_group = array(
        "cid" => $cid,
        "name" => "General Group",
        "approved" => 1,
        "description" => "This is a general group.",
        "logo" => "",
        "private" => 0,
        "staffonly" => 0,
        "locked" => 0,
        "uid" => (int) $mybb->user['uid']
    );

    $gid = $db->insert_query("socialgroups", $new_group);

    if(!is_object($socialgroups))
    {
        require_once "classes/socialgroups.php";
        $socialgroups = new socialgroups();
    }
    $socialgroups->socialgroupsuserhandler->join($gid, $mybb->user['uid'], 1);

    $new_leader = array(
        "gid" => $gid,
        "uid" => $mybb->user['uid']
    );

    $db->insert_query("socialgroup_leaders", $new_leader);

    $socialgroups->update_cache();
    $socialgroups->update_socialgroups_category_cache();

    // Usergroup permissions
    $db->write_query("ALTER TABLE " . TABLE_PREFIX . "usergroups ADD maxsocialgroups_create INT UNSIGNED DEFAULT 5, ADD cancreatesocialgroups INT UNSIGNED DEFAULT 1, ADD socialgroups_auto_approve INT UNSIGNED DEFAULT 0");
    $db->write_query("UPDATE " . TABLE_PREFIX . "usergroups SET cancreatesocialgroups=0 WHERE isbannedgroup=1");
    $db->write_query("UPDATE " . TABLE_PREFIX . "usergroups SET maxsocialgroups_create=0 WHERE cancp=1");
    $db->write_query("UPDATE " . TABLE_PREFIX . "usergroups SET socialgroups_auto_approve=1 WHERE cancp=1 OR canmodcp=1");
    $cache->update_usergroups();
}

function socialgroups_drop_tables()
{
    global $db, $cache;
    $tables = array("socialgroups", "socialgroup_categories", "socialgroup_members", "socialgroup_member_permissions", "socialgroup_leaders",
        "socialgroup_moderators", "socialgroup_invites", "socialgroup_announcements", "socialgroup_threads", "socialgroup_posts");
    foreach($tables as $table)
    {
        if($db->table_exists($table))
        {
            $db->drop_table($table);
        }
    }
    // Now permissions
    $columns = array("cancreatesocialgroups","maxsocialgroups_create","socialgroups_auto_approve");
    foreach($columns as $column)
    {
        if($db->field_exists($column, "usergroups"))
        {
            $db->write_query("ALTER TABLE " . TABLE_PREFIX . "usergroups DROP " . $column);
        }
    }
    $cache->update_usergroups();
}
