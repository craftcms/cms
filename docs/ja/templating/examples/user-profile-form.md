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

 <label for="bio">Bio</label>
 <textarea id="bio" name="fields[bio]">{{ currentUser.bio }}</textarea>

 <input type="submit" value="Save Profile">
</form>
```

