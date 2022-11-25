# Object Model System

## Defining Models
Object Models can be defined and/or modified by plugins in the `/models.json` (or `/models.yaml`) file. This file contains 2 sections:

### - `append` : Adding objects to an existing Model set
The `append` section of a `models.json` file consists of a list of objects each specifying a model set and the path to another file containing items to add to that set. This is how a plugin can add pages to the Settings screen, for example:

```json
"append": [
    {
        "model": "admin_modules",
        "store": "/models/admin_modules.json"
    }
]
```

### - `models` : Defining new Models
The `models` section of a `models.json` file contains definitions
for new Object Models. This is a list of JSON objects which contain the complete definition for a Model by specifying these parameters:

- `name` : (required) an arbitrary but unique name for the model
- `admin_manage` : (optional, default `true`) set to `false` to remove the usual Add/Remove/Edit interface for this object on the Model Objects settings page.
- `hide_on_admin_page` : (optional, default `true`) set to `false` to remove references to this Model from the Model Objects settings page altogether.
- `humanname` : (required) a user-friendly name for the Model used in lists and tools.
- `store` : (required) the path to the storage mechanism for the objects of this type. If it is a filename, it should be a JSON or YAML file and you should provide a path relative to the root of the plugin folder. You can also use a database table string here to specify that model itmes should be stored in a database. This must be formatted in the `__XXXdb__://<table>` format where `__XXXdb__` refers to a database connection defined in the plugin's configuration file.
- `access` : control access rules for the items of this kind by giving an Access String for each relevant HTTP method.
- `fields` : (required) the set of fields or attributes that comprise an object's definition.
- `assign_by_group` : set to `true` to restrict visibility and admin rights to these objects to users belonging to the group that owns the items.

#### `fields` : Defining the Model Attributes
Model fields are attributes that describe an individual item belonging to the model type you're defining. These are defined in the `fields` parameter of the model definition and consist of key:value pairs describing the attribute. The following fields values are supported:

- `name` : (required) the programmatic name of the field. Arbitrary, but cannot be `id` or `owner_group`. You don't have to have a field with the name `name`, but if you don't have one, your items may not appear as expected in tools and lists.
- `display` : (required) a user-friendly display name for the field used when prompting for user input or displaying items in a list.
- `type` : (required) the type of information represented by a field. Must be one of:
    - `str` : simple alphanumeric text string
    - `bool` : boolean (true/false) value
    - `password` : a string that will be obscured on the frontend and one-way hashed on the backend
    - `file` : a file input to support file uploads
    - `select` : a drop-down list to choose from. Options are stored in the field's `options` attribute.
- `options` : (required for `select` fields) a set of options to choose from to populate the field, consists of key-value pairs in the form `Friendly Name`:`backend_value`
- `textarea` : if this is a `str` field, you can set `textarea` to `true` to display a textarea field instead of an input field for user input.
- `ckedit` : if this is a `str`/`textarea` field, set `ckedit` to `true` to display a CKEditor (v4) editor for user input (requires the `ckedit_enabled` system setting to be enabled).
- `required` : set this to `true` if this field must have a value in order for an item to be valid.
- `inline_store` : if this is a `file` field and the Model is being stored in a database, set this to `true` to store uploaded files in the filesystem rather than in the database. For large datasets, this improves database performance.
- `do_not_hash` : if the Model is stored in a database, setting this to `true` means the value for this field will be stored as plaintext in the database rather than encoded with Base64, which is the default behavior. `password` type values will still be hashed with one-way encryption.
- `disposition` : (optional) for `file` fields, this controls the `Content-Disposition` header when serving the file to clients (in most cases). Set it to any valid value for that header.
- `unique` : set to `true` to enforce uniqueness among objects of this type. If `assign_by_owner` is enabled, uniqueness is only enforced within the scope of group ownership.
- `unique_to_models`: list the names of other models whose items must not have a matching field name with a matching value.
- `unique_to_models_error_text` : specify an error message to display to users if they attempt to create or save an item that breaks the `unique_to_models` rule.
- `validate` : give a data type that the value for this field must validate as (for example, `CIDR` validation will fail if the user puts in an email address).

## Managing Models
Assuming your user accoung has permission to perform an action, you can manage models and objects in the following ways:

### Admin Settings
By default, all model types are visible on the Admin Settings page under **Model Objects**. This is a simple tool to manage individual model items belonging of any type.

### PHP and JavaScript functions
Because many use cases call for more flexibility or specificity than the Model Objects tool provides, these functions are available to construct Model manipulation tools within a plugin.

### `/models` API
The `/models` API is a separate set of REST endpoints for interacting with the Object Model system.

#### Retrieving data
`GET /models` - list the available Model types
`GET /models/my_model` - list all `my_model` items by ID
`GET /models/my_model?details=true` - list all `my_model` items, including their data.
`GET /models/my_model?fields=name,style,etc` - list all `my_model` items along with their `name`, `style`, and `etc` values. Will also include the `id` field.
`GET /models/my_model/a1b2c3d4` - retrieve all data for the `my_model` item with ID `a1b2c3d4`.
`GET /models/my_model/a1b2c3d4/icon` - retrieve the `icon` file data for this item, including the appropriate `Content-Type` MIME data.

#### Creating items
`POST /models/my_model` - submit a JSON object containing all the necessary information to create a new `my_model` item.

#### Updating items
`PATCH /models/my_model/a1b2c3d4` - send JSON data to update this particular item.

#### Deleting items
`DELETE /models/my_model/a1b2c3d4` - delete this particular `my_model` item.