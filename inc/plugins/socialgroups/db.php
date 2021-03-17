<?php
/**
 * Socialgroups plugin created by Mark Janssen.
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
    ),
    "style" => array(
        "type" => "TEXT"
    ),
    "lastposttime" => array(
        "type" => "INT",
        "default" => 0
    ),
    "lastposttid" => array(
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
        "type" => "INT",
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
    global $db, $cache, $mybb, $socialgroups, $tables;
    foreach($tables as $tablename => $definition)
    {
        $result = socialgroups_generate_table_sql($tablename, $definition);
        $db->query($result);
    }

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

    if(!is_object($socialgroups))
    {
        require_once "classes/socialgroups.php";
        $socialgroups = new socialgroups();
    }

    $socialgroups->socialgroupsdatahandler->save_group($new_group, "insert");

    // User stats
    $db->add_column("users", "socialgroups_posts", "INT NOT NULL DEFAULT 0");
    $db->add_column("users", "socialgroups_threads", "INT NOT NULL DEFAULT 0");

    // Usergroup permissions
    $db->add_column("usergroups", "maxsocialgroups_create", "INT NOT NULL DEFAULT 5");
    $db->add_column("usergroups", "cancreatesocialgroups", "INT NOT NULL DEFAULT 1");
    $db->add_column("usergroups", "socialgroups_auto_approve", "INT NOT NULL DEFAULT 0");
    $db->update_query("usergroups", array("cancreatesocialgroups" => 0), "isbannedgroup=1");
    $db->update_query("usergroups", array("maxsocialgroups_create" => 0), "cancp=1");
    $db->update_query("usergroups", array("socialgroups_auto_approve" => 1), "cancp=1 OR canmodcp=1");
    $cache->update_usergroups();
}

function socialgroups_drop_tables()
{
    global $db, $cache, $tables;
    // Delete the tables
    foreach(array_keys($tables) as $table)
    {
        if($db->table_exists($table))
        {
            $db->drop_table($table);
        }
    }
    /* Now Columns
    * Use the format $column => $table
    */
    $columns = array(
        "cancreatesocialgroups" => "usergroups",
        "maxsocialgroups_create" => "usergroups",
        "socialgroups_auto_approve" => "usergroups",
        "socialgroups_posts" => "users",
        "socialgroups_threads" => "users"
    );
    foreach($columns as $column => $table)
    {
        if($db->field_exists($column, $table))
        {
            $db->drop_column($table, $column);
        }
    }
    $cache->update_usergroups();
}
