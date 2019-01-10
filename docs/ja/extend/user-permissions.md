# ユーザー権限

モジュールとプラグインは、[EVENT_REGISTER_PERMISSIONS](api:craft\services\UserPermissions::EVENT_REGISTER_PERMISSIONS) イベントを使用して新しいユーザー権限をシステムに登録できます。

```php
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use yii\base\Event;

public function init()
{
    parent::init();

    Event::on(
        UserPermissions::class,
        UserPermissions::EVENT_REGISTER_PERMISSIONS,
        function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Permission Group Name'] = [
                'permissionName' => [
                    'label' => 'Permission Label',
                ],
            ];
        }
    );
}
```

権限は `nested` キーをパーミッションの配列に追加することで、ネストされた権限を持つこともできます。

```php
'permissionName' => [
    'label' => 'Permission Label',
    'nested' => [
        'nestedPermissionName' => [
            'label' => 'Nested Permission Label',
        ],
    ],
];
```

## 権限の要求

コントローラーは、[requirePermission()](api:craft\web\Controller::requirePermission()) を呼び出すことで、ログインしているユーザー権限を持っていることを要求できます。

```php
public function actionStayUpLate()
{
    // Require the `stayUpLate` permission
    $this->requirePermission('stayUpLate');
}
```

ユーザーがその権限を持たない場合、403 エラーが返されます。

テンプレートでは、[requirePermission](../dev/tags/requirepermission.md) タグでユーザー権限を持っていることを保証することもできます。

```twig
{% requirePermission 'stayUpLate' %}
```

## 権限の確認

<api:craft\web\User::checkPermission()> を呼び出すことで、ログインしているユーザーが権限を持っているかを確認できます。

```php
// See if they have the `stayUpLate` permission
if (Craft::$app->user->checkPermission('stayUpLate')) {
    // ...
}
```

<api:craft\elements\User::can()> を呼び出すことで、指定されたユーザーが権限を持っているかを確認することもできます。

```php
/** @var \craft\elements\User $user */
if ($user->can('stayUpLate')) {
    // ...
}
```

