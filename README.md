GMdotnet_MagerrunAddons
=======================

# IMPORTANT! This is an experimental project! DO NOT USE ON PRODUCTION ENVIRONMENT!!

MageRun Addons
==============

Some additional commands for the excellent N98-MageRun Magento command-line tool.

The purpose of this project is just to have an easy way to deploy new, custom
commands that I need to use in various places.  It's easier for me to do this
than to maintain a fork of n98-magerun, but I'd be happy to merge any of these
commands into the main n98-magerun project if desired.

Installation
------------
There are a few options.  You can check out the different options in the [MageRun
docs](http://magerun.net/introducting-the-new-n98-magerun-module-system/).

Here's the easiest:

1. Create ~/.n98-magerun/modules/ if it doesn't already exist.

        mkdir -p ~/.n98-magerun/modules/

2. Clone the magerun-addons repository in there

        cd ~/.n98-magerun/modules/
        git clone git@github.com:kalenjordan/magerun-addons.git

3. It should be installed.  To see that it was installed, check to see if one of the new commands is in there, like `diff:files`.

        n98-magerun diff:files

Commands
--------

### Bust Frontend Browser Caches ###

This is very experimental

    $ n98-magerun product:create:dummy