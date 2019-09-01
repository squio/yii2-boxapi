# Box API 2.0 for Yii 2

Interface with the Box.com API

### Features:
- connect through OAuth 2
- basic file management (list, upload, download, delete)

This module is roughly based on [maengkom/boxapi for Laravel](https://github.com/maengkom/boxapi)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist squio/yii2-boxapi "*"
```

or add

```
"squio/yii2-boxapi": "*"
```

to the require section of your `composer.json` file.


## Configuration

Run migrations to create the box_user table:

`php yii migrate --migrationPath=@vendor/squio/boxapi/src/migrations`

Add the configuration to `modules` in your `config.php`

```php
'modules' => [
    'boxapi' => [
        'class' => 'squio\boxapi\BoxApi',
        'config' => [
            'client_id'         => 'YOUR_BOX_CLIENT_ID',
            'client_secret'     => 'YOUR_BOX_CLIEN_SECRET',
            // optional encryption key, to encrypt user tokens in database
            'data_crypt_key'    => 'RANDOM_STRING',
        ]
    ],
],

```

## API List

Below are the API methods you can used. All methods are following Box documentation.


Object     | Method                               | Verb   | Official Manual
-------- | -------------------------------------- | ------ | ---------------
Folder   | getFolderInfo($id)                     | Get    | [Get Folder’s Info](https://box-content.readme.io/reference#folder-object)
Folder   | getFolderItems($id)                    | Get    | [Get Folder's Items](https://box-content.readme.io/reference#get-a-folders-items)
Folder   | createFolder($name, $parent_id)        | Post   | [Create Folder](https://box-content.readme.io/reference#create-a-new-folder)
Folder   | updateFolder($id, $name)               | Put    | [Update Folder](https://box-content.readme.io/reference#update-information-about-a-folder)
Folder   | deleteFolder($id)                      | Delete | [Delete Folder](https://box-content.readme.io/reference#delete-a-folder)
Folder   | copyFolder($id, $dest)                 | Post   | [Copy Folder](https://box-content.readme.io/reference#copy-a-folder)
Folder   | createSharedLink($id)                  | Put    | [Create Shared Link](https://box-content.readme.io/reference#create-a-shared-link-for-a-folder)
Folder   | folderCollaborations($id)              | Get    | [Folder Collaborations](https://box-content.readme.io/reference#view-a-folders-collaborations)
Folder   | getTrashedItems($limit, $offeset)      | Get    | [Get Trashed Items](https://box-content.readme.io/reference#get-the-items-in-the-trash)
Folder   | getTrashedFolder($id)                  | Get    | [Get Trashed Folder](https://box-content.readme.io/reference#get-a-trashed-folder)
Folder   | permanentDelete($id)                   | Delete | [Permanently Delete](https://box-content.readme.io/reference#permanently-delete-a-trashed-folder)
Folder   | restoreFolder($id, $newName)           | Get    | [Restore Folder](https://box-content.readme.io/reference#restore-a-trashed-folder)
File     | getFileInfo($id)                       | Get    | [Get File's Info](https://box-content.readme.io/reference#files)
File     | updateFileInfo($id, $name)             | Put    | [Update File's Info](https://box-content.readme.io/reference#update-a-files-information)
File     | toggleLock($id, $type, $expire, $down) | Put    | [Lock and Unlock](https://box-content.readme.io/reference#lock-and-unlock)
File     | downloadFile($id)                      | Get    | [Download File](https://box-content.readme.io/reference#download-a-file)
File     | uploadFile($file, $parent, $name)      | Post   | [Upload File](https://box-content.readme.io/reference#upload-a-file)
File     | deleteFile($id)                        | Delete | [Delete File](https://box-content.readme.io/reference#delete-a-file)
File     | updateFile($name, $id)                 | Post   | [Update File](https://box-content.readme.io/reference#upload-a-new-version-of-a-file)
File     | copyFile($id, $dest)                   | Post   | [Copy File](https://box-content.readme.io/reference#copy-a-file)
File     | getThumbnail($id)                      | Get    | [Get Thumbnail](https://box-content.readme.io/reference#get-a-thumbnail-for-a-file)
File     | getEmbedLink($id)                      | Get    | [Get Embed Link](https://box-content.readme.io/reference#get-embed-link)
File     | createSharedLink($id, $access)         | Put    | [Create Shared Link](https://box-content.readme.io/reference#create-a-shared-link-for-a-file)
File     | getTrashedFile($id)                    | Get    | [Get Trashed File](https://box-content.readme.io/reference#get-a-trashed-file)
File     | deleteFilePermanent($id)               | Delete | [Permanently Delete](https://box-content.readme.io/reference#permanently-delete-a-trashed-file)
File     | restoreItem($id, $newName)             | Post   | [Restore File Item](https://box-content.readme.io/reference#restore-a-trashed-item)
File     | viewComments($id)                      | Get    | [View Comments](https://box-content.readme.io/reference#view-the-comments-on-a-file)
File     | getFileTasks($id)                      | Get    | [Get File's Tasks](https://box-content.readme.io/reference#get-the-tasks-for-a-file)

## Usage


Once the module is installed, simply use it anywhere in your code:

```php
// Initialize module and authenticate current logged-in user with Box
// Throws InvalidCallException if no user is logged in
$boxapi = \Yii::$app->getModule('boxapi');

// Authenticate with Box; takes care of all of OAuth protocol and redirects
// user as needed to ask for permissions
$boxapi->authenticate();

// Get root folder listing as nested array structure
$boxapi->getFolderInfo('0')

// List a folder's index: open URL http://<app-name>/boxapi/folder?id=0

// Download file, a specific version can be specified for Enterprise accounts
$this->redirect($boxapi->downloadFile('12364521', $versionId = '675463234'))

```
