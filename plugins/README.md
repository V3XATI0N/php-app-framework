# Plugins

Plugins do things, unlike the Core platform which does nothing by itself really. A plugin consists of a few basic elements Core expects to find.

[[_TOC_]]

## `plugin.json`

The `plugin.json` file defines the plugin and its assets so Core can load it
and serve its contents and stuff. Put it in the root folder of the plugin.
At a minimum, you need to include the `name`, `version`, and `depends`
parameters.

### `plugin.json` parameters

* `name` - string - The name of your plugin. The value is arbitrary.
* `description` - string - a description.
* `depends` - object - a list of components the plugin depends on and their miniumum versions your plugin needs. A plugin will not load at all unless these requisites are met. The `core` item refers to the version of the core framework, otherwise each item should reference a plugin by its respective `name` parameter.
* `composer` - object - Specify Composer packages the plugin depends on here. List them the same way they would be listed in a `composer.json` file (package name and version). You do not need to include a full Composer configuration or any packages inside your plugin code - packages will be installed when your plugin is enabled (unless Composer automation is disabled in System Settings). (Composer is reconfigured with packages installed/removed anytime any plugin is enabled or disabled).
* `version` - string - the version of your plugin.
* `maintainer` - string - Who is responsible for this.
* `logo_image` - path - relative path (from the root of your plugin folder) to your plugin's logo. Should be located in the `resources` directory because otherwise Core can't serve it.
* `enabled` - boolean - True or False depending on whether the plugin is enabled. Core will manage this item through the Admin settings.
* `scripts` - object - A list of javascript files to include in the application. Each file is an object consisting of two parameters:
    - `source` - relative path to the js file from the plugin root, but it has to be in the `/resource` folder (otherwise Core will not allow direct access).
    - `access` - Access Control String defining the minimum group/user access level required to access the file. Public or user requests that don't meet this standard will not see the file in the `HEAD` tag and will not be allowed to access the file directly.
    - `ondemand` - (optional) Set this to `true` to prevent the script from loading on every page, so you can specify it on demand in `<script>` tags elsewhere. Helps to keep the application's footprint small by only loading javascript when you need it.
    - `load_first` - (optional) Load this script before other scripts (except jQuery). If multiple scripts have this set, they will be loaded in the order they appear in the conf file; if multiple plugins have `load_first` scripts, they will be loaded in alphabetical order by plugin name.
    - `eval` - (optional) if set to `true`, the script will be evaluated as a PHP template.
* `styles` - object - CSS files to include in the application. This works exactly the same as the `scripts` parameter, except without support for `ondemand`, `load_first`, or `eval` parameters.
* `api` - definitions defining API endpoints and controlling access to them.
* `account_settings_modules` - a list of modules that define options visible on a user's Settings page. Consists of these keywords:
    - `access` - Access Control string limiting visiblity and access to this module by user/group access level.
    - `href` - the URL path to be used for this module (appended to the `/settings/` page path).
    - `name` - the title of the module as shown on the Settings page.
    - `icon` - an image file in the plugin's `/resource` folder to be used as the icon for the module link.
* `nav_menu` - object - Define top-level application pages controlled by the plugin. Page definitions support these keywords:
    - `href` (required) - the path to this page as appended to the application's root URL. Also corresponds to the folder in the `/init/` directory that contains `GET.php`.
    - `access` - an Access Control String that controls who sees the page in the Nav Menu. **This does not restrict access** - users can manually type in the path. To limit access by user/group rank, use the `access.json` file in the folder for this page in `/init/`.
    - `menu_id` - an arbitrary string that will become the `id` attribute of the link in the Nav Menu.
    - `icon` - path to an image file to use as the icon for this page, relative to the plugin's root directory.
* `login_extensions` - specify PHP pages to include in the login page.
* `includes` - specify PHP files to include in the application generally.
* `group_model_extensions` - define extensions to the user group definition model.
* `user_model_extensions` - define extensions to the user definition model.
* `field_validators` - define additional type validators for fields

## directory layout

### `/resource` - static content

Static content (images, scripts, styles, etc.) must be placed in the `/resource` folder (otherwise Core will refuse access to it). Valid file types include:

* Images
    * .jpeg/.jpg
    * .png
    * .svg (SVG files can include PHP tags)
    * .gif
* Scripts (.js)
* Stylesheets (.css)
* Other files
    * .pdf
    * .narf

You **cannot** serve PHP files this way. There are no ".php" extensions allowed on any URL anywhere in the application (because it looks embarrassing, seriously just stop).

### `/api` - API endpoints

Use the `/api` folder for API operations. In the root of your `/api` directory, place a file called `index.php` to direct clients to the appropriate place. Generally, you should create a separate subfolder under `/api` for each separate endpoint, then a separate file inside that folder for each supported HTTP method, but it's up to you if you want to do something else, this isn't a dictatorship.

If there is no `index.php` file defined to direct requests, then Core will default to searching recursively for a file that matches the endpoint and method of the request. If there is no endpoint folder, the client will receive a `404` error; if there is a folder but no file corresponding to the HTTP method, clients will receive a `405` error.

Inside each endpoint directory, you can create an `access.json` file to control access to the API resource by method, but really you should just do that in `plugin.json` because it's better.

**Endpoints set up in this directory will be served whether or not they are defined in `plugin.json` but will be restricted to users in admin-level groups and higher.**

#### BUILT-IN API VARIABLES

Core provides a few variables for you to work with in your endpoints so you don't have to do all the input gathering and such for you. Actually, these are available anywhere, not just the API, but I'm putting them here to confuse you.

* `$api_path` - literally just $_SERVER['REQUEST_URI'] exploded by `/`
* `$api_data` - any data POSTed with the request, whether through JSON or a form or whatever
* `$api_method` - the HTTP method of the request
* `$url_query` - any query parameters appended to the request URI

### `/init` - top-level pages

To serve top-level pages in the application (`www.site.com/mypage` or whatever), create a folder for the page under the `/init` directory, then a PHP page corresponding to the HTTP method you expect. Yes, you can do any HTTP method here just like in the API. Should you? Probably not (REST is better than your bad idea), but again, you're free to make poor life choices if you want.

Along with a method page (like `GET.php`, keep up), each folder under `/init` should also have an `access.json` file to define access controls for your page (like if you want to allow everyone in the world to GET, but only registered users to POST, etc.).

Pages that exist in the `/init` directory are accessible regardless of whether they are defined in `plugin.json`, but only pages with corresponding entries in `plugin.json` will be shown in the Navigation Menu.

#### `access.json`

The `access.json` file inside the `/init/{{page}}` directory controls access and options for a plugin's page. Valid parameters for this file are:

* `methods` - A key:value list defining access levels for each HTTP method that can be used for this page
* `skip_template` - If `true`, this page will not include the regular header or footer code (no navigation menu, etc.).
* `direct_output` - If `true`, this page will not automatically include any HTML or HTTP headers at all.
* `html_meta` - Control the HTTP Meta tags that define how HTML5 previews for this page are displayed. Supported tags are `title`, `image`, `description`, and `url`.
* `page_title` - The title that should be shown in the browser (and browser history) for this page.

## Core Platform Feature Extensions

Plugins can extend the functionality of various built-in features of the core application.

### System Settings Options

Define additional options to be included in the global application settings and set their values from the `/admin/settings` page along with everything else.

### Admin Pages

Add entries to the `/admin` page navigation menu and create pages to handle those functions.

### Field input validators

Provide validation for value types on object model definitions.

### User and Group Model Extensions

Add additional fields to be included in the profile data for users and groups.

## Interfacing with Other Plugins

All plugins are stored in the system variable `$plugins` in the format `$pluginName => $pluginConf`. This variable is universally accessible (no, it isn't actually a global var), so any plugin can easily access the configuration of any other plugin. Do with that what you will.

## Direct Fileserver Mode

As of version 4.99.71, you can use Direct Fileserver Mode (DFM) for plugins. Do this by enabling the `plugin_direct_fileserver` option in system settings, and adding the `serve_direct` option to your `plugin.json` configuration. When this mode is enabled, the system themes, javascript files, etc. are bypassed by default (including resources defined by other plugins), and the system will serve files *directly* from the root of your plugin folder. If you have a file called `index.php` in your plugin folder, users can access it directly by going to `<hostname>/index.php`.

### Supported Filetypes

Any file that could be served from a normal plugin's `resource` folder can be served with DFM. Some filetypes are handled with specific rules:

### PHP

PHP files are executed with the `include()` function. The code you put in here is live. Such files have access to all the usual variables and classes you'd have in any other plugin. These will ultimately be delivered with the `Content-Type: text/html` header.

### HTML

HTML files are simply spat out verbatim to clients. No special handling or anything. No PHP code in files with the `.html` or `.htm` extension will be executed. Files are delivered with `Content-Type: text/html`.

### JavaScript

These will be delivered as plaintext but with the `Content-Type: application/javascript` header. If you need this code to be executed with the PHP engine, add `eval_javascript: true` to the `direct_fileserver_options` section of your plugin configuration file.

### CSS

This does not get minimized or anything, just returned as-is with `Content-Type: text/css`. If you want to execute these files as PHP templates, add `eval_css: true` to the `direct_fileserver_options` section of your  plugin configuration.

### Images

Images are handled like they would be if they were in a standard `resource` folder (i.e. just given away with the (hopefully appropriate) mime type headers)

### JSON

JSON files are delivered as JSON data with `Content-Type: application/json`. Note that you are **not allowed** to serve the standard plugin configuration files: `plugin.json`, `models.json`, `settings_override.json`, and `settings_schema.json` are all explicitly off-limits (the same goes for YAML files, by the way).

### Access Controls

Controlling access to resources works differently with Direct Fileserver Mode plugins. Plugins can include an `access.json` file in the root folder with straightforward access rules. The structure of this file differs for DFM plugins from the standard format used in other places. For DFM plugins, add the `direct_fileserver_rules` directive containing a list of resources inside the plugin folder with access rules defined on a per-method basis, like this:

```json
{
    "direct_fileserver_rules": {
        "/index.html": {
            "GET": "public:public",
            "DELETE": "moderator:admin"
        },
        "/gallery.html": {
            "GET": "moderator:user",
            "POST": "admin:user"
        }
    }
}
```

Each item in the `direct_fileserver_rules` key refers to a URI as it would be addressed by a client. Files in subfolders should be named as a full URI path, not nested inside JSON objects or something weird, you weirdo.

Any request received using a method that hasn't been defined in the access.json file will be assigned a default access string as below:

```yaml
GET: "public:public"
PUT: "admin:user"
POST: "admin:user"
DELETE: "admin:user"
OPTIONS: "moderator:user"
```

Other/custom methods will default to `admin:user`.