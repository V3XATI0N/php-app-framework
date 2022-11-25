# REST API

The REST API is an extensible system for interacting programmatically with the application and plugins. All endpoints are in the `/api` path.

## Core API Endpoints

### **`/account`** - User Account Settings

Manage your own user account attributes.

#### **Account Info**
`GET /api/account` - Retrieve all the information for your user account.

#### **Theme Settings**
`GET /api/account/theme` - Return theme settings
`PATCH /api/account/theme` `{JSON data}` - send new theme settings

#### **Account Settings**
`GET /api/account/settings` - Return account settings
`PATCH /api/account/settings` `{JSON data}` - update settings

### **`/admin`** - Admin Options

By default, you must have at least a `moderator:user` access level to access anything here, and `admin:user` to make most changes.

#### `/admin/audit_log` - **Audit Log**

`GET /admin/audit_log` - Return the entire Audit Log.

#### `/admin/users` - **User Accounts**

`GET /admin/users` - List user accounts and their attributes
`IMPORT /admin/users` `{JSON data}` - Import user data from ancient versions of the application, never used in real life.
`OPTIONS /admin/users` - Return the complete user account model specifying the information needed to create or modify user accounts.
`PATCH /admin/users/<id>` `{JSON data}` - Submit updates to one or more attributes for a user account.
`POST /admin/users` `{JSON data}` - Submit a new user account for creation.
`DELETE /admin/users/<id>` - Delete the specified user account.

#### `/admin/groups` - **User Groups**

`GET /admin/groups` - List group accounts and their attributes
`IMPORT /admin/groups` `{JSON data}` - Import group data from ancient versions of the application, never used in real life.
`OPTIONS /admin/groups` - Return the complete group account model specifying the information needed to create or modify group accounts.
`PATCH /admin/groups/<id>` `{JSON data}` - Submit updates to one or more attributes for a group account.
`POST /admin/groups` `{JSON data}` - Submit a new group account for creation.
`DELETE /admin/groups/<id>` - Delete the specified group account.

#### `/admin/models` - **Object Models**

#### `/admin/plugins` - **Plugin System**

#### `/admin/settings` - **System Settings**

#### `/admin/update` - **System Updates**

### **`/tools`** - Utilities & Resources

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
