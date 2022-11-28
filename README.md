# Ridiculous PHP App Framework

A general-purpose PHP application framework. Includes tools for simply managing REST endpoints, database connections, object definition & storage, etc. etc. Doesn't actually do much of anything without plugins, though. Read about installing plugins somewhere farther down this needlessly long and complicated file.

[[_TOC_]]

## General Repository Notes

### License Violations?

This project has been developed in an actively used dev environment. As such there may besome things included in this repository that (probably) shouldn't be. For example, `reource/ckeditor` and `resource/jquery` include CKEditor 4 and JQuery, but these probably should be added by you yourself. Maybe this should get cleaned up, idk.

## Installation

### Requirements

#### PHP

You should use *at least* PHP `7.1`. Once in a while I will spin up an instance and make sure this app also works with `8.0+` but a lot of the Composer dependencies get mad about that and I'm not going to spend a
lot of time on that for now.

#### Libaries and extensions

* `composer` `2.0+` (see composer note below)
* `gd`
* `zip`
* `mcrypt`
* `json`
* `mysqli`
* `mbstring`

Additionally, the `yaml` extension is required if you want to parse
or emit yaml files.

### A note about Composer automation

Because I wanted plugins to be able to include Composer dependencies, the application will (sort of) combine Composer requirements and handle the installation of these automatically. The way it does this is by compiling all the requirements specified by **enabled** plugins, adding these to the few dependencies required by Core, and installing all of them in the `/composer` folder.

Every time a plugin is enabled, any Composer dependencies it lists that aren't already installed will be installed. When a plugin is disabled, any Composer dependencies it lists that aren't listed by anyone else will be removed.

For this to work, you need to ensure that the Composer is installed and the `composer_path` setting is set  correctly (or that the default value `/usr/local/bin/composer` is adequate for your environment).

You can set the system settings `disable_composer` to `true` if you don't want the wacky Composer behavior at all (I wouldn't blame you you to be honest). But if you do this, you should at least ensure the core Composer dependencies are in place by copying `/utils/composer_base.json` to `/composer/composer.json` and running `composer update` from that directory. Otherwise some things will probably break.

#### Web Server

This really only works on NGiNX because NGiNX is cool and I don't feel like screwing around with Apache. I'm sure it would work on Apache, but again, I don't care and you can't make me. ANYWAY, to run it on NGiNX you just need to set up a virtual server that directs absolutely everything to `index.php`. Here is the config I use on my dev box:

```nginx
server {
        listen                  80;
        server_name             fancy.url;
        return                  302     https://fancy.url,,$request_uri;
}
server {
        listen                  443 ssl http2;
        access_log              /var/log/nginx/salt3-access.log;
        error_log               /var/log/nginx/salt3-error.log;
        server_name             fancy.url;
        index                   index.html index.htm index.php;
        root                    /var/www/salt3;
        ssl_certificate         /etc/letsencrypt/live/fancy.url/fullchain.pem;
        ssl_certificate_key     /etc/letsencrypt/live/fancy.url/privkey.pem;
        location ~*\ \.(png|jpg|jpeg|svg|gif|ico|js)$ {
                expires 7d;
        }
        location /              {
                try_files       index.php @dynamic;
        }
        location @dynamic       {
                fastcgi_pass                unix:/run/php/php7.2-fpm.sock;
                fastcgi_buffers             16  32k;
                fastcgi_buffer_size         64k;
                fastcgi_busy_buffers_size   64k;
                include                     global/fastcgi-params.conf;
                fastcgi_param               PATH_INFO       $uri;
                fastcgi_param               REQUEST_URI     $request_uri;
                fastcgi_param               SCRIPT_NAME     /index.php;
                fastcgi_param               SCRIPT_FILENAME /var/www/salt3/index.php;
        }
}
```
Adjust for your own purposes. `fastcgi-params.conf` just has the usual stuff in it.

Once this is working, you will get a useless page that tells you to install a plugin. But FIRST, you need to do the rest of the setup things, or you'll be sorry.

1. Enable the default user account `admin` by renaming `data/users.json.setup` to `data/users.json`. This should allow you to log in with `admin`/`password`. **Once you login, go to your profile settings and change your password.**

2. Rename `utils/settings.json.setup` to `utils/settings.json` or nothing will work correctly.

### Installing plugins

Plugins are required to actually do things. All plugins are installed by extracting or cloning them into the `/plugins` directory. You can log in as an admin account and go to the `<url>/admin/plugins` page to enable/disable them. Refer to the respective plugin's documentation for further instructions.

## Access Control Concepts

Access to pages, objects, and actions are controlled by a simple (dare I say laughably rudimenatary) access control system. Most things (pages, objects, API actions, etc.) have an associated **Access Control String** assigned to them. These are located either in an object definition or a strategically located `access.json` file.

Access is first organized by HTTP method, so for each access-controlled object, there are (or can be) different permissions based on what a client is attempting to do as determined by the HTTP method they're using. You can limit any HTTP method, so custom/non-standard methods are fine.

Once the method is determined, the Access Control String is applied to an action to determine whether or not it is allowed to proceed. An Access Control String is a declaration of two **Access Levels**, separated by a colon. For example: `admin:moderator`. The first field refers to the access level assigned to a user's group; the second is the access level assigned to the user themselves.

A user's effective access level is a combination of these two values. To perform an action limited to an `admin:moderator` level, the user must belong to a group with an access level of `admin` or higher; then the user must themselves have an access level of `moderator` or higher. Group levels grant access to everything with a lower value, even if a user's individual level does not meet the user level defined by an action; group levels also *deny* access to everything with a higher value, so a user cannot perform any action limited to a higher group level, no matter what their own individual level is.

An "Access Level" is just a number between 0 and 99, with a name assigned to make it easier to keep track of it. The standard levels are:

* public: level 0
* user: level 25
* moderator: level 50
* admin: level 75
* owner: level 99

If you really need to change these numbers for some reason, you can do that by editing the `access_levels` object in `settings.json`. The values defined there aren't hardcoded anywhere, so you should be fine.

Everyone, including unauthenticated users, have access to any object or action with a `public:public` access string. When a user is authenticated, they will have access to any action with a group access level  below their own group's access level regardless of their own user access level, and any action with a group access level equal to their own group's level and a user level equal to or below their own user level.

### Plugin Access Level Extensions

Plugins can define additional access levels with their own numeric values to provide more granular access control. This is done by adding an `access_levels` object in the plugin's `plugin.json` file with numerical values between the standard levels. For example:

```json
...
"access_levels": {
    "super_moderator": 55,
    "super_admin": 80
}
...
```

## Object Model System

The Object Model system manages data objects defined by Core and plugins. Models defined by Core are related to user accounts and groups and UI elements. A plugin can define any sort of object that it needs.

A **Model** is a definition that describes a type of object, including its name, where it should be stored, access control rules, and its attributes. Core models (except for user accounts and groups, which are specialized types of objects) are defined in `/utils/models.json`. Plugins define their models in `/plugins/{{plugin}}/models.json`.

This system is intended to make it easier for plugins to extend the application by defining object types without needing to manage their creation/deletion/updating. Simply define a model type and Core will give users the tools needed to interact with them.

A model-specific REST interface is published at the URI `{{url}}/models`, and the Admin page includes a module for managing model items.

### DEFINING A MODEL TYPE

A model definition is a JSON object included in the appropriate `models.json` file. Currently, there is no UI facility for creating new model definitions, only for creating items. Because the `/utils/models.json` provided with the platform may be changed by platform code updates, if you want to define a new model type, you should do it by creating a simple plugin and adding your models there. This will probably get better in the distant future.

```json
// models.json
{
    "models": [
        {
            "name": "object_name", // used in code
            "humanname": "Object Name", // displayed to users
            "admin_manage": true, // can be managed from Admin page
            "store": "/models/object_name.json", // data store
            "access": { // access rules
                "OPTIONS": "user:user",
                "GET": "public:public",
                "POST": "admin:user",
                "DELETE": "admin:admin",
                "PATCH": "admin:user"
            },
            "fields": [ // model attributes
                {
                    "name": "name", // used in code
                    "display": "Human Name", // displayed to users
                    "type": "str", // data type
                    "required": true // error if absent
                },
                {
                    "name": "method",
                    "display": "HTTP Method",
                    "type": "multi", // multiple-choice
                    "options": {
                        "POST": "POST",
                        "GET": "GET",
                        "DELETE": "DELETE"
                    }
                }
            ]
        }
    ]
}
```
#### Top-level parameters for the model definition:

* `name` (required) - the internal name. Must be unique among object models.
* `humanname` (required) - user-facing name. arbitrary.
* `store` (required) - path to data store. relative to plugin root.
* `admin_manage` (optional) - if `true`, the Admin page will allow (admin-level) users to add/remove/modify objects of this type.
* `hide_on_admin_page` (optional) - if `true`, this type of model will not be shown on the Object Models admin page at all.
* `assign_by_group` (optional) - if `true`, items of thist type will belong to the same group as the user who creates them, users can only enumerate or manage items of this type that belong to the same group as them, unless their group is admin-level or above.
* `access` (optional) - access control rules. if not specified, unauthenticated requests are blocked, all users can list the items, and a user must be at least `moderator:user` to manage them.
* `fields` (required) - list of attributes for the object.

#### Model field (attribute) parameters:

* `name` (required) - internal name. must be unique to among fields for this model.
* `display` (required) - user-facing name, arbitrary.
* `type` (required) - data type for the field (see valid types below)
* `validate` (optional) - extra validation for field types (see validator types below)
* `required` (optional) - whether this field must be included when adding/modifying an item. default is `false`.
* `unique` (optional) - whether the value supplied for this field must be unique among all items of this type. default `false`
* `label` (optional) - if `true`, when listing items of this type, use this field as the label. By default, the field with the name `name` is used (if there is one). If this is not `true` *and* there is no field with the name `name`, then there will be no label and users will be sad.
* `options` (conditional) - a key:value list that defines the options from which a user can select a value for a field of type `select` or `multi`.
* `do_not_hash` (optional) - if data is stored in a database, the default behavior is to hash the values for extra sql injection protection. this disables that and stores data as plain text in the database. has no effect on models using flat-file storage.
* `transform` (optional) - declare PHP functions to transform the data for a field. see example below.
* `access` (optional) - define an Access Control string to limit who is allowed to read the value of this field. This way you can have model items that are generally visible to everyone but only users of at least the given rank can see this one particular attribute of the item.

```json
/*
ADVANCED CONFIGURATION EXAMPLES
*/

"fields": [
    // field data transformation
    {
        "name": "field_name",
        "transform": {
            "on_write": "myTransformFunction",
            "on_read": "myUntransformFunction"
        }
    },
    {
        "name": "field2_name",
        "transform": {
            "on_write": {
                "function": "complexTransformFunction",
                "args": {
                    "arg1": "item::some_other_field",
                    "arg2": "oset::system_settings_option"
                }
            },
            "on_read": {
                "function": "complexUntransformFunction",
                "args": {
                    "arg1": "item::some_other_field"
                }
            }
        }
    },
    // field access restriction
    {
        "name": "field3_name",
        "access": "admin:user" // only admin-group and higher users can read or the value of this field.
    }
]
```

#### Valid field data types:

* `str` - a string. any string.
* `bool` - boolean.
* `select` - a drop-down list (field must include the `options` parameter).
* `multi` - a multi-select list (field must include the `options` parameter).
* `password` - a string that will be hashed using `password_hash()` when stored.

#### Validating user input in fields

Core provides the following standard field validators. Plugins can extend this list by supplying additional validators.

* `email` - value must be a valid (by format) email address.
* `phone` - value must be a number 9 or 10 digits long.
* `ip` - value must be a valid IPv4 or CIDR (v4 or v6) address
* `url` - value must be a valid URL

### STORAGE BACK-ENDS

By default, a Model definition's `store` is a relative path (from the plugin root) to a JSON or YAML file where the objects are stored in a flat-file format. Alternatively you can give it a database connection string, in which case object data will be stored in the database instead. This has a few moving parts:

1. A connection string is required. This looks like `__cooldb__://myTable` where `cool` is an arbitrary string that fits in the pattern `^__.*db__:\/\/.*$` (see the little `.*` there, that's the arbitrary bit). This points Core to a `db_sources` item you define in `plugin.json` (see #2). The `myTable` string is the name of a table in the database. Use this connection string instead of a file path in the Model's `store` parameter.

2. The `db_sources` parameter in `plugin.json` tells Core how to find your database. It looks like this:

```json
"db_sources": {
    "__cooldb__": {
        "type": "mysql",
        "host": "oset::plugin_dbhost",
        "name": "oset::plugin_dbname",
        "user": "oset::plugin_dbuser",
        "pass": "oset::plugin_dbpass"
    }
}
```
The name of the source ("`__cooldb__`") matches the connection string prefix. The `type` parameter is optional (only `mysql` is supported for now anyway). The weird syntax for those values allows you to use system settings extensions as the values for these items. If a value is prepended with `oset::`, Core will fill that parameter with the system setting option you specify in the 2nd segment. Otherwise, you can just specify a simple string for each/any of these items and that will be used. You can have more than one `db_sources` objects, and they can all have their own authentication (and even host/db name) settings.

3. Core will store object data in the database with one row per object instance and a column per field. It will create new columns in the database to accommodate changes to your model definition. It will NOT delete existing columns if you remove a column. All data stored in the database will be JSON-encoded *and* base64-encoded (unless a field has the `do_not_hash` parameter set).

4. If your Model has a `file` type field, **file data will still be stored outside the database**. As with the JSON backend, only a pointer to the files is saved in the database.

You should be able to utilize the usual `/models` API to view, create, delete, and update objects stored in the database. No other database handling is necessary for these.

### EXTENDING MODELS

Plugins can extend an existing model definition by replacing the top-level `name` parameter with the `extends` parameter whose value is the internal name of the model to be extended.

A plugin can add additional fields to the model being extended. If a field name in the extension conflicts with a field name in the original model, the extension's version wins.

As elsewhere in the app, plugins are additive. This means more than one plugin can extend the same model, so it's possible plugins can interfere with each other (so don't screw everything up). They are processed in alphabetical order by plugin folder name.

### DEFINING OBJECTS THAT BELONG TO SOMEONE ELSE'S MODEL

A plugin can define additional objects of a type defined by Core or by another plugin. To do this, add another item to the `models.json` file called `append`. This is a list of sub-objects consisting of a `model` field naming the type of objects you're defining and a `store` naming a path (relative to the plugin's root folder) to a JSON or YAML file containing the objects. This is how you add pages to the Admin page menu, since admin pages are defined by the `admin_modules` model.

### MANAGING OBJECT MODEL TYPES

You can also use the API to perform CRUD operations on the model definitions with `GET`, `PATCH`, `POST`, and `DELETE` methods on the `/api/admin/models` endpoint. Just be mindful of the fact that you can mess up existing data objects if you're not careful:

* Changing a model's storage location won't automatically migrate existing objects.
* Adding/removing required fields might cause existing objects to be out of spec.
* Changing field types can cause existing objects to display weird field values.

Also the `/api/admin/models` endpoint only works at the whole-model level (for example, you can't make requests to `/api/admin/models/cool_model/field_name` or something).

Any models created using this API endpoint will behave as if they were defined by Core (you're actually editing `/utils/model_defs.json` with this endpoint).

### ACCESSING MODELS

#### From PHP

Use the `ObjectModel` class to interact with the Object Model system.

```php
// if no arguments are supplied, the object will return a list of all objects (with their schemas) in the "all_models" attribute.
$my_model = new ObjectModel($model = null);
var_dump($my_model->all_models); // returns all models that exist. if $model isn't defined, this is all you can do.
$all_items = $my_model->get_items(); // get all items of this kind
$one_item = $my_model->get_items($id); // get a specific item of this kind by its ID.
$add_item = $my_model->add_item([/*definition*/]); // create a new item
$del_item = $my_model->del_item($id); // delete the specified item
$update_item = $my_model->update_item($id, [/*data*/]); // update the specified item with the data supplied
```

#### From JavaScript

The `ObjectModel` class also exists in the JavaScript provided by the platform. Of course, due to the asynchronous nature of querying the server for data, it works a little differently.

```javascript
let my_model = new ObjectModel(model = true)
	.then(function(obj) {
        console.log(obj.all_models) // print all models
        console.log(obj.schema) // print the schema of the specified model
        var my_items = obj.get_items(item = null, get_opts = null)
            /*
            if item is null, you'll get all items. otherwise, you'll
            get the one you specify and its data.

            get_opts is an object with the following supported
            keywords.

                > only retrieve specified fields
                {fields: ['list', 'of', 'fields']}

                > get all fields
                {details: true}

                > get all items even if they are restricted by
                  group and your group is not the owner. requires
                  at least an admin-level group.
                {override_owner: true}
            */
        	.then(function(itemdata) {
                console.log(itemdata);
        });
        var add_item = obj.add_item(data)
        	.then(function(result) {
                // this creates a new item of the given type.
                console.log(result);
            });
        var del_item = obj.del_item(item)
        	.then(function(result) {
                // this deletes the named item
                console.log(result);
            });
        var patch_item = obj.update_item(item, data)
        	.then(function(result) {
                // this updates the named item
                console.log(result);
            });
    });
```



#### From a web client

Use the `{{url}}/models` endpoint to interact with the Object Model system with a RESTful interface. The following methods are supported:

* `OPTIONS` `/models/model_name` - get the schema (definition) of the named model.
* `GET` `/models` - list available models.
* `GET` `/models/model_name[?details=true]` list all the items of the given model type. The optional URL parameter `details` controls what you receive: if it is `true`, you will get all the actual data for all the items; if it's anything else or missing, you will get a simple list of item IDs.
* `GET` `/models/model_name/item_id` - get the definition of the named item.
* `POST` `/models/model_name` - create a new item by sending a JSON object with (at least) all required fields.
* `DELETE` `/models/model_name/item_id` - delete the specified item.
* `PATCH` `/models/model_name/item_id` - send a JSON object with changes to update the named item.
* `REORDER` `/models/model_name` - send a JSON list of item IDs to change the order in which items are displayed.