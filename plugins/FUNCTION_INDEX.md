# Core Functions

The core platform provides (probably too many) functions (both PHP and JavaScript) that you have access too in plugins.

[[_TOC_]]

## PHP Functions

`logData($data)` - dump something to the Audit Log.

`getDb($query, $auth)` - interact with a (MySQL/MariaDB) database. if `$auth` is defined, use that to connect. Otherwise use the database connection values defined in System Settings. `$auth` is an array in the order **[db host, db name, db username, db password]**.

`dbEsc($string, $auth)` - get a SQL-safe escaped string from `$string` using the system db connection settings or `$auth` as above.

`getDbArray($query, $auth)` - interact with a database and get an iterable array in return.

`logError($data, $title)` - log `$data` to the web server error log, with optional `$title`. For debugging purposes mostly.

`minimizeCSSsimple($css)` - minify the CSS string `$css`

`checkPasswordStrength($pass)` - returns `true` if `$pass` is strong enough, otherwise returns `false`.

`moveUserToGroup($user, $group)` - move user `$user` to group `$group`. Expects object IDs, not names.

`getOptionScript($option)` - returns JavaScript meant to be executed by clients when the system setting `$option` is changed from the plugins.

`getOptionChange($option, $value)` - compiles and returns all JavaScript from all plugins that want the client to do something when `$option` is changed to `$value`.

`revBool($bool, $str)` - return the opposite value for a given boolean value. If `$str` is `true`, expects `$bool` to be "true" or "false".

`validateCidr($cidr, $cidr_only)` - returns `true` if `$cidr` is a valid CIDR address. If `$cidr_only` is `true`, then `$cidr` *must* be a CIDR address that includes a subnet, otherwise any IP address is fine.

`validateField($test, $type)` - returns `true` if `$test` adheres to the rules for the data type `$type`. Plugins can add additional data type definitions with the `field_validators` parameter.

`buildUserGroupModel()` - returns the data model for Users and Groups, including extensions from plugins.

`parse_file($filename, $create = true)` - parse the specified file and return its contents as an array. Supports YAML and JSON files (and determins which format to use based on the file extension). Unless `$create` is set to `false`, the specified file will be created if it doesn't exist already.

`emit_file($filename, $data)` - exports the array `$data` to the file `$filename`. Will save as either JSON or YAML depending on what the given file extension is.

`clearUserLoginLock($user, $ipaddr)` - If a user is locked out, this clears the lock. If `ipaddr` is given, only clears locks that apply to the specified IP address.

`startSession()` - The same as `session_start()` but with some other little bits that the platform uses. Use this instead.

`getRankLevel($rank)` - Returns the numeric value for the given access level name.

`getUsersAndGroups($includePluginUsers)` - Returns all system users and groups as an array. if `$includePluginUsers` is `true`, includes users defined by plugins (though this will only count users who have already logged in at least once).

`getUserProfile($id)` - Returns profile data for the given user ID.

`getGroupProfile($id)` - Returns profile data for the given group ID.

`getGroupAccessLevel` - Returns the access level for the given group ID.

`getUserAccessLevel` - Returns the access level for the given user ID.

`accessMax($user, $standard)` - given the access control string `$standard`, return `true` if the given user ID is *at most* that level, `false` if the user's access level is *higher*.

`accessMatch($user, $standard)` - given the access control string `$standard`, return `true` if the given user ID is *at least* that level, `false` if the user's access level is *lower*.

`getAccessLevels()` - Return a list of all access levels, including any defined by plugins, and their numeric values.

`loadUserActions($user)` - Returns any pending user actions in the `user_actions.json` file (it's in the /utils folder), which caches actions to be be applied the next time a particular user interacts with the application. This is not actually used yet but is intended for things like password expiration, etc.

`saveUserActions($actions)` - Creates the `user_actions.json` file, containing the data `$actions`.

`logoutUser($user)` - Adds a logout command to the user actions file for the specified user.

`clearUserActions($user)` - Clears any pending actions for the given user.

`overlayUserSettings($context)` - Overlays user settings on top of system settings. This is how we establish which system settings apply to which user. Currently, user settings are only allowed to override theme options.

`buildSystemSettings($includeSchema)` - Return the complete array of currently active System Settings. If `includeSchema` is `true`, also includes the data model for system settings.

`buildPluginRegistry($includeDisabled)` - Returns an array containing all enabled plugins and the contents of their `plugin.json` files. If `$includeDisabled` is `true`, also includes disabled plugins.

`getPluginOpts($plugin, $value)` - Returns the contents of the given plugin's `plugin.json` file. If `$value` is given, returns only the value of that parameter.

`getModels()` - Returns the data model for all object models.

`getModelItem($model, $item, $override)` - Returns the specific instance `$item` of the model type `$model`. If `$override` is `true` AND the model type is configured to be limited by user group, includes the specified item even if it doesn't belong to the current user's group.

`getModelItems($model, $override)` - Returns all items of the model type `$model`. If the model is limited by group, only items that belong to the current user's group will be listed unless `$override` is set to `true`.

`deleteModelItem($model, $item)` - Delete the item `$item` of model type `$model`. Current user must be at least `admin:user` if the model is limited by group and the item does not belong to the user's group, otherwise access control is defined by the model's access settings.

`addModelItem($model, $item, $owner)` - Create the new item `$item` of type `$model`. If the model is limited by group, and the user is not at least `admin:user`, the item will belong to the user's group. If the user is at least `admin:user`, then the item will belong to the user's group unless group ID `$owner` is specified.

`patchModelItem($model, $item, $data)` - Update the specified item `$item` of type `$model` with the values `$data`. Will overwrite attributes specified by `$data` but will not change or remove attributes not supplied. If `$model` is group-limited, user must either belong to the owner's group, or be at least `admin:user`.

`buildComposerRequires()` - Rebuilds the Composer package database based on the current state of `/composer/composer.json`.

`insertHtmlHead()` -  Returns the completed template for the `<head>` tag.

`insertPageHeader()` - Returns the completed template HTML for the top of a standard app page.

`serveLocalFile($path, $attach = false)` - Deliver the specified file to the web client. If `$attach` is `true`, set the `Content-Disposition` header to `attachment`.

`downloadLocalFile($path)` - Download the specified file to the web client.

`real_scandir($dir)` - Same as the built-in `scandir()` except without the stupid dot directories.

`apiDie($message, $code)` - Exit a request immediately by returning the `$message`, using HTTP response `$code`. If `$message` is an array/object, it will be JSON-formatted.

`openTemplate()` and `closeTemplate()` - Begin and end a standard HTML page for returning to the web client.

## JavaScript Functions

### Available to everyone

`nl2br(str)` - just like PHP's built-in version.

`toggleAction(class, item)` - Opens/closes an `action_class` box whose class is `class` (include the preceding dot) and whose `item_id` is `item`.

### Available only to Admin-level users

okay.

## PHP Classes

and so on

## JavaScript Classes

### `FormInputList(options)`

Generate a form that matches the general design language of the core platform. `options` is a JSON object defining the parameters for the form and its behavior.

If `options` is an object containing the configuration items below, a new form will be generated. If it is a string, the existing FormInputList form whose `form_name` matches the given string will be returned. In either case, the form itself is returned as `objectName.form`.

#### CONFIGURATION PARAMETERS

#### `form_name` (required)

A name to use for the form. Arbitrary, but should be unique among all forms that might displayed on the page at a given time.

#### `buttons` (optional)

A list of buttons to add to the form in order to customize the actions performed when submitting the form. Each button is defined as an object consisting of a `label` key (the text shown on the button) and an `id`, which will be the button's ID. It's your responsibility to make something happend when that ID is clicked.

#### `ckeditor_opts` (optional)

A valid CKEditor configuration to be used for any `textarea` input types to be transformed into CKEditor instances. If this is omitted, a basic configuration with undeo/redo, font, style, and color options is used by default.

#### `fields` (required)

A list of input field items (the information to collect from the form). Each of these consists of these keywords:

* `type` - the type of data to be collected. one of `bool`, `multi`, `option`, `select`, `password`, `textarea`, or `str`.

* `name` - programmatic name for the field, unique among all fields for the form.

* `display` - arbitrary human-readable name for the field, used as the label.

* `placeholder` - if this is specified, this value will be used for `placeholder` text in input elements that support that attribute (the `display` parameter will still be used for elements that don't support `placeholder`).

* `ckedit` - (textarea type only) if the global setting `enable_ckeditor`, this input will be transformed into a CKEditor (v4) instance.

* `buttons` - Add buttons to the same line as the input field. These must be controlled by your own code. A button in this context is defined by 3 parameters: `label`, `class`, and `id`.

#### Object usage and configuration

For `multi`, `option`, and `select` fields, you must also specify the options for users to choose from. This can be done by supplying a key:value list as the `options` key, or by supplying an `option_src` key pointing to an API endpoint that returns a key:value list.

The `textarea` input type can be transformed by adding the `ckedit` keyword set to `true` and then executing the `enableCKEditors(objectName.ckeditors)` method on the class. That method takes an optional second parameter where you can pass a CKEditor (v4) configuration. Note that in order for any of this CKEditor stuff to work, the global system setting `enable_ckeditor` has to be enabled.

You can specify default values for these input fields with the `default` key.

#### Retrieving input data

To compile and return the data a user has entered into a FormInputList,
use the `getFormInputData()` method.

```javascript
let my_form = new FormInputList({
    form_name: 'my_great_form',
    buttons: [
        {
            label: 'Do the thing',
            id: 'myGreatFormSubmitBtn1'
        }
    ],
    fields: [
        {
            name: 'mf_field1',
            display: 'Field One',
            type: 'str',
            default: 'Super Value'
        },
        {
            name: 'mf_field2',
            display: 'Field Two',
            type: 'select',
            options: {
                'Option 1': 'option1',
                'Option 2': 'option2'
            }
        },
        {
            name: 'mf_field3',
            display: 'Field Three',
            type: 'textarea',
            ckedit: true
        },
        {
            name: 'mf_field4',
            display: 'Text input with button',
            type: 'str',
            buttons: [
                {
                    label: 'Button Name',
                    class: 'my_great_form_inputBtn',
                    id: 'my_great_form_field4btn1'
                }
            ]
        }
    ]
});

$(parent).append(my_form.form);

my_form.enableCKEditors(my_form.ckeditors, {cke: opts});

/* ... ... ... */

$(document).on('click', '#myGreatFormSubmitBtn1', function() {
    let my_form_data = new FormInputList('my_great_form').getFormInputData();
});
```

The `getFormInputData` method takes one optional boolean argument. If `true`, the function will return stringified JSON data instread of an object.

### `ObjectModelCreateForm(model)`

builds a form for creating a Model Object of type `model`.

### `ObjectModel`

See the main <a href="/README.md">README file</a>.

### `ModelEditForm(schema, item, context, callback)`

Creates an input form to edit or create a Model Item of the type whose schema you specify.

You can get the `schema` parameter with an `OPTIONS` request to the `/models/modelname` endpoint or (preferably) by initializing an `ObjectModel` class and accessing its `schema` property.

The `item` is the item ID of the specific item you wish to edit. If, instead, you want to create a new item, use the special ID keyword `__new__`.

`context` is the action you want to perform, either `editItem` or `createItem`.

`callback` is (shock) a callback function to perform when the form is submitted by the user.

### `PageInfoBox(title, text)`

Generate a simple text/notice box to add a notification to the page that can't be missed. Returned as `objectName.box`.