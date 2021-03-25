**Edits**
- Minor fixes where it is very few line of code that change or fixing a language variable do not need their own issue.
- Nonminor issues should have an Issue that is referenced.  
- If you are implementing something from the features.txt file, you do not need to post an issue number since those
are all things that should be done before this goes live. In that case, just put added feature x in the description.
- If you need to edit templates for fixing an issue or adding a feature, create a file for just that template in the templates directory so it is easy to copy any code changes done.

**Coding Practices**
- Please try and have code be PHP 8 complient whenever possible. The next release of MyBB is going to be so this way we will be ahead. Due to what the PHP version requirements are
for MyBB, we unfortunately can't use PHP 8 features.  ( I would really like to have named arguments so you don't have to worry about the order for some ).
Once MyBB is fully PHP 8 compatibile, we can consider updating code then.
I know some parts of this extension might not be and we will have to address that gradually.  The main thing to watch out for is undefined variables and illegal offset.
- Any actions that should be performed on MyBB Hooks need to be in inc/plugins/socialgroups/hooks.php.  This keeps it easy to maintain. The function you create
should be named socialgroups_(Hook_Name) because this way it will not intefere with any other plugins and it is easy to know what hook it is attached to.
- When you create hooks, it should follow an easy to decipher system.  If it is a class file the hook should be class_classname_something_descritive.  If it is not a class file, the hook should start with the name of the file and then a descriptor.  Example showgroup_begin.

- Please put comments in the code where things can get complicated.  You don't need to write a novel, but at least something will make it easier to understand.
- I prefer braces on their own line for control statements and functions / methods.  
- Every control statement has an opening and closing brace even if it is just one line of code that goes there.
- Please use $mybb->get_input over $mybb->input because if the offset is not specified, it can lead to warnings and errors depending on the PHP version. For strings you don't need to specify a second parameter.  For integers the second parameter should be MyBB::INPUT_INT.  Other valid types are MyBB::INPUT_FLOAT, MyBB::INPUT_BOOL, and MyBB::INPUT_ARRAY.  As you work on files, please try and fix these gradually.

- Any database structure changes, please use db.php and edit the array $tables.  For the column type, use what the type would be in MySQL. The code is parsed through a function so it will be compatible with PGSQL and SQLite.

**Bugs**
- It is expected that there will be bugs early on.  Make sure to create an issue for them. Even better if you can do a Pull Request that resolves it.
- If you don't have a solve for it, please list how to reproduce so I can verify whether I get that same issue on my site.

**Join Space**
- If you would like to discuss the project or make contributions, you can join in on space (teamdimensional.jetbrains.space)[here].  You could easily work on developing a plugin to extend socialgroups in this environment
while being able to have direct contact with developers of Socialgroups.