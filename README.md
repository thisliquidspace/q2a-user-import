=============================
q2a-user-import
=============================

-----------------------------
Compatible with Q2A versions:
-----------------------------

- 1.6.1
- 1.6.3

-----------
Description
-----------

User Import plugin for Question2Answer CMS.

--------
Features
--------

- Bulk import users into Q2A via an uploaded CSV file
- Automatically sends email to each user

------------
Installation
------------
#. Install Question2Answer_
#. Get the source code for this plugin from github_, either using git_, or downloading directly:

   - To download using git, install git and then type 
     ``git clone git@github.com:thisliquidspace/q2a-user-import.git backup``
     at the command prompt (on Linux, Windows is a bit different)
   - To download directly, go to the `project page`_ and click **Download**
   
#. Navigate to your site, go to **Admin > Plugins** on your q2a install and go to section '**User Import**'
#. Select a local CSV file (correctly formatted) via '**Browse**' and then '**Upload to server**'. The file should appear in the plugin window.
#. Select the file via the corresponding radio button and then press '**Test selected file!**'.
#. Check the list generated for any badly formatted emails or illegal usernames.
#. Select the file again (prevents accidents) via the corresponding radio button and then press '**Import selected file!**'.
#. Your users will not receive the system standard sign-up email.

.. _Question2Answer: http://www.question2answer.org/install.php
.. _git: http://git-scm.com/
.. _github:
.. _project page: https://github.com/KrzysztofKielce/q2a-backup

----------
Disclaimer
----------
This is **beta** code.  It is probably okay for production environments, but may not work exactly as expected.  Refunds will not be given.  If it breaks, you get to keep both parts.

-------
Release
-------
GNU GENERAL PUBLIC LICENSE v2 See 'LICENSE' for more information.

---------
Credits
---------
Plugin based on https://github.com/KrzysztofKielce/q2a-backup

Development sponsored by builtintelligence.com.

Development carried out by thisliquidspace.com
---------
About Q2A
---------
Question2Answer is a free and open source platform for Q&A sites. For more information, visit:

http://www.question2answer.org/



