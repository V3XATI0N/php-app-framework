# REST API

The REST API is an extensible system for interacting programmatically with the application and plugins. All endpoints are in the `/api` path.

## Authenticating with the API

The *most* preferred way to authenticate with the API is by using OAuth. However, since I have not even begun to implement that, it isn't an option.

The second most preferred way to authenticate is by generating an API key at `<url>/account/plugin/core_account/core_account_api_keys` (you'll need to keep the `core_account` plugin enabled for this). Put the generated key in the `X-TOXAPI-AUTH` header. Note that API keys grant whatever privileges you have, there is no way to assign/restrict specific permissions for a key (yeah yeah, if you don't like it, fix it).

The *least* preferred way to authenticate is with plain old basic HTTP authentication using your own username and password. This is a bad idea, but it's convenient so whatever. **You can disable basic auth for the API** by setting the `disable_api_basic_auth` system setting on the Admin page or via the `/api/admin/settings` endpoint.

## Core API Endpoints

### **`/account`** - User Account Settings

Manage your own user account attributes.

#### **Account Info**

View and manage your own account and profile.

* `GET /api/account` - Retrieve all the information for your user account.

##### **SUBMODULES**

The `account` endpoint has various submodules for actually doing things:

##### `settings` (Not applicable to user accounts managed by plugin authentication)
* `GET /api/account/settings` - View your actual session data, which contains your preferences.
* `PATCH /api/account/settings/{field}`  `{{data}}` - Update (most) user data. `{field}` should be one of the values returned under `userdata` in the session data, by default these include

    - `password`
    - `email`
    - `fullname`
    - `theme`*

Other `userdata` fields (user/group rank, group membership, access level, etc) are immutable and cannot be changed by yourself.

*Theme options include booleans for `local_dark_theme`, `vertical_layout`, `show_nav_text`, `dark_theme`, and `show_app_title`; and a hex color code for `theme_color`.

#### **Theme Settings**
* `GET /api/account/theme` - Return theme settings
* `PATCH /api/account/theme` `{JSON data}` - send new theme settings

#### **Account Settings**
* `GET /api/account/settings` - Return account settings
* `PATCH /api/account/settings` `{JSON data}` - update settings

### **`/api/admin`** - Admin Options

By default, you must have at least a `moderator:user` access level to access anything here, and `admin:user` to make most changes.

#### `/api/admin/audit_log` - **Audit Log**

* `GET /api/admin/audit_log` - Return the entire Audit Log.

#### `/api/admin/users` - **User Accounts**

* `GET /api/admin/users` - List user accounts and their attributes
* `IMPORT /api/admin/users` `{JSON data}` - Import user data from ancient versions of the application, never used in real life.
* `OPTIONS /api/admin/users` - Return the complete user account model specifying the information needed to create or modify user accounts.
* `PATCH /api/admin/users/<id>` `{JSON data}` - Submit updates to one or more attributes for a user account.
* `POST /api/admin/users` `{JSON data}` - Submit a new user account for creation.
* `DELETE /api/admin/users/<id>` - Delete the specified user account.

#### `/api/admin/groups` - **User Groups**

* `GET /api/admin/groups` - List group accounts and their attributes
* `IMPORT /api/admin/groups` `{JSON data}` - Import group data from ancient versions of the application, never used in real life.
* `OPTIONS /api/admin/groups` - Return the complete group account model specifying the information needed to create or modify group accounts.
* `PATCH /api/admin/groups/<id>` `{JSON data}` - Submit updates to one or more attributes for a group account.
* `POST /api/admin/groups` `{JSON data}` - Submit a new group account for creation.
* `DELETE /api/admin/groups/<id>` - Delete the specified group account.

#### `/api/admin/models` - **Object Models**

Manage the models maintained by **Core** (this endpoint _will not_ change models defined by plugins).

* `GET /api/admin/models` - List available models, along with all their definition data.
* `GET /api/admin/models/modelname` - Get the definition of the specified `modelname`.
* `POST /api/admin/models` `{{definition}}` - Create a new model by sending a complete, valid definition as a JSON object.
* `PATCH /api/admin/models/modelname` `{{changes}}` - Send a JSON object containing changes in the form of a definition (you don't need to include the entire definition, only changes, but make sure the keys/values are in the right hierarchy).
* `DELETE /api/admin/models/modelname` - Delete the specified `modelname`.

#### `/api/admin/plugins` - **Plugin System**

Manage installed plugins.

* `GET /api/admin/plugins` - List installed plugins
* `LOCK /api/admin/plugins/plugin_name` - Lock (disable) the plugin `plugin_name`.
* `UNLOCK /api/admin/plugins/plugin_name` - Unlock (enable) the plugin `plugin_name`.

> Eventually the plan is to include POST, PATCH, and DELETE endpoints here for installing, updating, and uninstalling plugins, but this does not exist yet.

#### `/api/admin/settings` - **System Settings**

Manage system settings, including those defined by plugins. **This endpoint is really intended for use by the Admin page**, but there's no reason you can't use it too.

* `GET /api/admin/settings` - List all settings, including definitions and current values. **This will likely contain sensitive information like database passwords**, so be careful, etc. The data returned is organized into 3 objects:
    * `groups` - The groups into which system settings are organized
    * `core` - The actual system settings themselves
    * `lists` - not actually used, whatever
* `PATCH /api/admin/settings/core/setting_name` `{{instructions}}` - Change a system settings option. `instructions` is a JSON object in the format:

```json
{
    "source": "core",
    "state": "cool whatever"
}
```
> "`source`" should be "core" for core settings, or the name of the plugin that defines the option. Using the wrong source value will probably break something.

#### `/api/admin/update` - **System Updates**

Someday this will (might?) allow you to update the app somehow. For now it just spits out the current version and the changelog.

### **`/tools`** - Utilities & Resources

#### `/api/tools/html2pdf` - **Generate a PDF of some HTML**

For no particular reason, you can use this endpoint to convert arbitrary HTML to PDF.

* `POST /api/tools/html2pdf` `{{html}}` - Send some HTML in the POST data and get a PDF back (probably). Note that you can't use this HTML to retrieve anything from this app that isn't accessible to any random public user. Also for self-explanitory reasons this requires the `mpdf` package installed as defined in `/composer/composer.json`.

* `GET /api/tools/html2pdf` - Get a PDF with curse words in it because srsly what are you even doing.

## Extending the API

Plugins can add top-level API endpoints. At a minimum, include an `/api` folder in the plugin with a subfolder for each endpoint you want to create. Note that plugin-provided endpoint names can't conflict with core endpoints. In the root of each subfolder, you can create PHP scripts for each HTTP method you want to support (`GET.php` for GET requests, `POST.php` for POST requests, etc.), or if more complex behavior (deeper UNC folder paths, for example) is required, you can put an `index.php` file in the method folder to direct requests however you need.

Access control for plugin-provided endpoints are defined in the plugin's `plugin.json` configuration file under the `api` directive, with a subheading for each endpoint containing an access control string for each supported HTTP method:

```json
...
"api": {
    "endpoint_name": {
        "access": {
            "GET": "public:public",
            "POST": "user:user",
            "DELETE": "admin:user"
        }
    }
}
---
```
If this configuration is missing from your plugin configuration, all actions will be limited to `admin:user` users, but endpoints will still be accessible.
