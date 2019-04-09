# ユーザープロフィールの編集フォーム

ユーザー自身のプロフィールを編集できるフォームを作成するには、次のコードで実現できます。

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="users/save-user">
    {{ redirectInput('users/'~currentUser.username) }}
    <input type="hidden" name="userId" value="{{ currentUser.id }}">

    <label for="location">Location</label>
    <input type="text" id="location" name="fields[location]" value="{{ currentUser.location }}">

    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="firstName" value="{{ currentUser.firstName }}">

    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="lastName" value="{{ currentUser.lastName }}">

    <label for="bio">Bio</label>
    <textarea id="bio" name="fields[bio]">{{ currentUser.bio }}</textarea>

    <input type="submit" value="Save Profile">
</form>
```

::: tip
ネイティブのユーザーフィールドではなく、カスタムフィールドの入力欄だけが名前フォーマット `fields[<FieldHandle>]` となります。
:::

