#!/usr/bin/env bash

THEME_NAME=Zentora
THEME_URL=https://github.com/auxio/Zentora/archive/master.zip
THEME_GITHUB=1

echo "###########################################################"
echo "#     $THEME_NAME Theme installer/updater for Sentora     #" 
echo "###########################################################"
echo "#  THE SCRIPT IS DISTRIBUTED IN THE HOPE THAT IT WILL BE  #" 
echo "# USEFUL, IT IS PROVIDED 'AS IS' AND WITHOUT ANY WARRANTY #"
echo "###########################################################"
	
while true; do
        read -e -p "Do you want to install/update the $THEME_NAME theme for Sentora (y/n)? " yn
        case $yn in
            [Yy]* ) break;;
            [Nn]* ) exit;
        esac
    done
fi	
	

if [ -d "/etc/sentora/panel/etc/styles/$THEME_NAME" ]; then
	THEME_UPDATE=1
	echo "You have the $THEME_NAME theme already installed, Theme theme will be updated."
else
	THEME_UPDATE=0
	echo "You have not jet installed the $THEME_NAME theme, Theme theme will be installed."
fi

cd /etc/sentora/panel/etc/styles/

echo "Downloading the $THEME_NAME theme..."
while true; do
	wget -nv -O $THEME_NAME.zip $THEME_URL
	if [[ -f $THEME_NAME.zip ]]; then
		break;
	else
		echo "Failed to download the $THEME_NAME theme from Github."
	exit 1
	fi 
done

if [[ "$THEME_UPDATE" = "1" ]]; then
	echo "Updating the $THEME_NAME theme..."
else
	echo "Installing the $THEME_NAME theme..."
fi

if [[ "$THEME_GITHUB" = "1" ]]; then
	unzip -q $THEME_NAME.zip
	rm -f $THEME_NAME.zip
	
	if [[ "$THEME_UPDATE" = "1" ]]; then
		rm "$THEME_NAME"
	fi
	
	mv -u -f $THEME_NAME-master $THEME_NAME
else
	if [[ "$THEME_UPDATE" = "1" ]]; then
		rm "$THEME_NAME"
	fi
	
	unzip -q $THEME_NAME.zip
	rm -f $THEME_NAME.zip
fi

echo "##########################################################################################"
if [[ "$THEME_UPDATE" = "1" ]]; then
	echo " Congratulations the $THEME_NAME theme for Sentora has now been updated on your server."
else
	echo " Congratulations the $THEME_NAME theme for Sentora has now been installed on your server."
fi
echo "##########################################################################################"