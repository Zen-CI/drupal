#!/bin/sh

echo "Installing drupal to " . $DOCROOT

# Go to domain directory.
cd $DOCROOT

#link all core files
ln -s $ZENCI_DEPLOY_DIR/* ./
ln -s $ZENCI_DEPLOY_DIR/.htaccess ./

#copy sites directory instead of linking to move it out of git repo
rm -f sites
cp -r $ZENCI_DEPLOY_DIR/sites ./

#Install drupal

drush site-install standard -y --root=$DOCROOT --account-mail=$ACCOUNT_MAIL --account-name=$ACCOUNT_USER --account-pass="$ACCOUNT_PASS" --site-mail=$SITE_MAIL --site-name="$SITE_NAME" --uri=http://$DOMAIN_NAME --db-url=mysql://$DATABASE_USER:$DATABASE_PASS@localhost/$DATABASE_NAME

echo "user: $ACCOUNT_USER pass: $ACCOUNT_PASS"
