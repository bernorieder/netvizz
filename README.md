# netvizz
A collection of scripts that help with downloading data from the Facebook platform for research purposes.

This should be relatively easy to set up:
- You need a publicly accessible server running PHP (no database involved, but apparently Windows does not work - I've only tested this on Linux);
- Put the files in a directory of your choice, e.g. http://yourserver.com/netvizz/;
- In this folder, create a subdirectory named "data" and make sure that it is writeable;
- Head over to https://developers.facebook.com/ and create a new app, using the public path (e.g. http://yourserver.com/netvizz/) as the Canvas URL in the "settings" pane; there you'll also find your App ID and App Secret;
- Rename ini_example.php to ini.php and fill in the $appid and $secret variables with your values; change the $canvasurl variable to your public path (e.g. http://yourserver.com/netvizz/);
