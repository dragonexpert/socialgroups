**Edits**
- Minor fixes where it is very few line of code that change or fixing a language variable do not need their own issue.
- Nonminor issues should have an Issue that is referenced.  
- If you are implementing something from the features.txt file, you do not need to post an issue number since those
are all things that should be done before this goes live. In that case, just put added feature x in the description.

** Coding Practices **
- Please try and have code be PHP 8 complient whenever possible. The next release of MyBB is going to be so this way we will be ahead. Due to what the PHP version requirements are
for MyBB, we unfortunately can't use PHP 8 features.  ( I would really like to have named arguments so you don't have to worry about the order for some ).
Once MyBB is fully PHP 8 compatibile, we can consider updating code then.
I know some parts of this extension might not be and we will have to address that gradually.  The main thing to watch out for is undefined variables and illegal offset.

- Please put comments in the code where things can get complicated.  You don't need to write a novel, but at least something will make it easier to understand.
- I prefer braces on their own line for control statements and functions / methods.  
- Every control statement has an opening and closing brace even if it is just one line of code that goes there.

** Bugs **
- It is expected that there will be bugs early on.  Make sure to create an issue for them. Even better if you can do a Pull Request that resolves it.
- If you don't have a solve for it, please list how to reproduce so I can verify whether I get that same issue on my site.
