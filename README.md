# Viber

Viber Integration

# Requirements

* Min 4.26 Live Helper Chat version.
* https is required

# Install

* Create Viber bot - https://partners.viber.com/account/create-bot-account
* Activate extension by modifying `settings/settings.ini.php`
  * `'extensions' => array ('viber')`
* Modify `Bot Token` at `Modules -> Viber`
* Click `Create/Update Rest API call`
* After that you can go to `https://<change_me>/site_admin/genericbot/listrestapi` and modify `Viber Integration` In the `Body` tab you will find `sender` section where you can set custom Sender instead of `Live Helper Chat`

That's it.
