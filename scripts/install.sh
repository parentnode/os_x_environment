#!/bin/bash -e

echo "--------------------------------------------------------------"
echo ""
echo "Installing parentNode in mac"
echo "DO NOT CLOSE UNTIL INSTALL ARE COMPLETE" 
echo "You will see 'Install complete' message once it's done"
echo ""
echo ""


source /srv/tools/scripts/functions.sh

install_user=$(getCurrentUser)

guiText "Installing system for $install_user" "Comment"

read -p "Do something: " something
echo $something













echo "Install complete"
echo "--------------------------------------------------------------"
echo ""