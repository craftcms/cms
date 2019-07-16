# ユーザープロフィールの編集フォーム

コントロールパネルへのアクセスを許可することなく、ユーザーが自身のプロフィールを編集できるようにするためのフロントエンドフォームを作成できます。これをするために、プロフィールフォームのためにコントロールパネルが使用するのと同じコントローラーにフォームを向かわせます。（フォームやコントローラーの詳細については、[フォームアクション](#form-action)に移動してください。）

可能な限りシンプルなプロフィールフォームとフル機能のプロフィールフォームの2つの例を紹介します。

## 簡易プロフィール

次のフィールドは、バリデーションを必要としません。

- ファーストネーム
- ラストネーム
- 写真

これがあなたにとって必要なものすべてであるならば、フォームはとてもシンプルになります。

```twig
{% requireLogin %}

<form id="profile-form" class="profile-form" method="post" accept-charset="UTF-8" enctype="multipart/form-data">
  <input type="hidden" name="action" value="users/save-user">

  {{ csrfInput() }}

  <input type="hidden" name="userId" value="{{ currentUser.id }}">

  <div>
    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="firstName" value="{{ currentUser.firstName }}">
  </div>

  <div>
    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="lastName" value="{{ currentUser.lastName }}">
  </div>

  <div>
    <label for="photo">Photo</label>

    {% if currentUser.photo %}
      <div>
        <img id="user-photo" src="{{ currentUser.photo.url() }}" alt="">
      </div>
    {% endif %}
  </div>

  <div>
    {# This file field takes precedence over the ”Delete photo” checkbox #}
    <label for="photo">Select photo</label>
    <input id="photo" type="file" name="photo" accept="image/png,image/jpeg">
  </div>

  <div>
    {# If a file has been selected, this has no effect #}
    <label for="deletePhoto">
      <input id="deletePhoto" type="checkbox" name="deletePhoto"> Delete photo
    </label>
  </div>

  <div>
    <input type="submit" value="Save Profile">
    <a href="{{ craft.request.url }}">Reset</a>
  </div>
</form>
```

[分析](#breaking-it-down)セクションでは、以下の詳細プロフィールサンプルに表示されているこれらのフィールドについて取り上げます。

## 詳細プロフィール

この例では、次のものをすべて含みます。

- ファーストネーム
- ラストネーム
- 写真
- ユーザー名
- メール
- パスワード
- カスタムフィールド
- バリデーション

詳細については、[分析](#breakdown)セクションを参照してください。このフォームのためのスタイル定義例は、[追加](#extras)セクションを参照してください。

この例には、カスタムの Bio フィールドが含まれていることを忘れないでください。そのため、Bio フィールドが不要な場合はテンプレート内にコピー＆ペーストした後でそのセクションを削除してください。

```twig
{% requireLogin %}

<form id="profile" method="post" accept-charset="UTF-8" enctype="multipart/form-data">

  {% set notice = craft.app.session.getFlash('notice') %}
  {% if notice %}
    <p>{{ notice }}</p>
  {% endif %}

  {% set formUser = user is defined ? user : currentUser  %}

  {% if formUser.hasErrors() %}
    <div class="error-list">
      <p>Unable to save user. Please check for errors.</p>

      <ul>
        {% for error in formUser.getFirstErrors() %}
          <li>{{ error }}</li>
        {% endfor %}
      </ul>
    </div>
  {% endif %}

  {{ csrfInput() }}

  {# {{ redirectInput('users/'~currentUser.username) }} #}

  <input type="hidden" name="action" value="users/save-user">

  <input type="hidden" name="userId" value="{{ formUser.id }}">

  <div class="group">
    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="firstName" value="{{ formUser.firstName }}">
  </div>

  <div class="group">
    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="lastName" value="{{ formUser.lastName }}">
  </div>

  {% if formUser.photo %}
  <div class="group">
    <label>Photo</label>
    <img id="user-photo" src="{{ formUser.photo.url({width: 150}) }}" alt="">
  </div>

  <div class="group">
    <label for="delete-photo">
      <input id="delete-photo" type="checkbox" name="deletePhoto">
      Delete photo
    </label>
    <p class="instruction">If a new photo is selected, this checkbox has no effect.</p>
  </div>
  {% endif %}

  <div class="group">
    <label for="photo">Select photo</label>
    <input id="photo" type="file" name="photo" accept="image/png,image/jpeg">
  </div>

  {% if not craft.app.config.general.useEmailAsUsername %}
    {% set error = formUser.getFirstError('username')  %}
    {% set class = error ? 'has-error' : '' %}
    <div class="group {{  class }}">
      <label for="username">Username <span class="error-symbol">&#9888;</span></label>
      <p class="instruction">If left blank, this will become the email address.</p>

      <p class="error-message">{{ error }}</p>
      <input type="text" id="username" name="username" value="{{ formUser.username }}">
    </div>
  {% endif %}

  {% set error = formUser.getFirstError('email')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{  class }}">
    <label for="email">Email <span class="error-symbol">&#9888;</span></label>

    {% if craft.app.projectConfig.get('users.requireEmailVerification') %}
      <p class="instruction">New email addresses need to be verified.</p>
    {% endif %}

    <p class="error-message">{{ error }}</p>
    <input type="text" id="email" name="email" value="{{ formUser.email }}">
  </div>

  {% set error = formUser.getFirstError('newPassword')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{ class }}">
    <label for="new-password">New Password  <span class="error-symbol">&#9888;</span></label>
    <p class="error-message">{{ error }}</p>
    <input type="password" id="new-password" name="newPassword" value="{{ formUser.newPassword }}">
  </div>

  {% set error = formUser.getFirstError('currentPassword')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{ class }}">
    <label for="current-password">Current Password <span class="error-symbol">&#9888;</span></label>
    <p class="instruction">Required to change Password and Email</p>
    <p class="error-message">{{ error }}</p>
    <input type="password" id="current-password" name="password" value="">
  </div>

  {# Custom field example. Delete if you don't have a `bio` field. #}
  {% set error = formUser.getFirstError('bio')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{ class }}">
    <label for="bio">Bio <span class="error-symbol">&#9888;</span></label>
    <p class="error-message">{{ error }}</p>
    <textarea id="bio" name="fields[bio]">{{ formUser.bio }}</textarea>
  </div>

  <div class="group">
    <input type="submit" value="Save Profile">
    <a href="{{ craft.request.url }}">Reset</a>
  </div>
</form>
```

### 分析

詳細フォームの例を順を追って見ていきましょう。

#### ログインの要求

```twig
{% requireLogin %}
```

ユーザーがログインしていることを確実にしてください。そうでなければ、テンプレートは `currentUser` を利用する処理でエラーを返します。予期しない `404 Not Found` エラーを回避するために、[{% requireLogin %} タグ](https://docs.craftcms.com/v3/dev/tags/requirelogin.html)のドキュメントを必ず読んでください。

#### フォームアクション

```twig
<form id="profile-form" class="profile-form" method="post" accept-charset="UTF-8">
      <input type="hidden" name="action" value="users/save-user">
```

`<form>` タグは、意図的に `action=""` パラメータを持ちません。不可視要素の `name="action"` 項目が、どのコントローラーやコントローラーメソッドを使用するか Craft に伝えます。

:::tip
コントロールパネルのプロフィールフォームは、Craft の [UserController::actionSaveUser()](api:craft\controllers\UsersController#method-actionsaveuser) コントローラーを使用しています。あなたのニーズに適している場合、フロントエンドでも自由に使うことができます。そうでなければ、独自のモジュールやプラグインで自身のコントローラーを実装するためのインスピレーションとして使用できます。
:::

#### 通知

```twig
{% set notice = craft.app.session.getFlash('notice') %}
{% if notice %}
  <p>{{ notice }}</p>
{% endif %}
```

送信が成功すると、この通知には “User saved.” のメッセージが表示されます。もちろん、リダイレクトの項目をセットしていない場合に限ります。（どのようにするかは、[オプションのリダイレクト](#optional-redirect)に移動してください。）

#### ユーザー変数

```twig
{% set formUser = user is defined ? user : currentUser  %}
```

フォームが初めてロードされるときは、変数 `currentUser` を使用します。バリデーションエラーがあった場合、過去に送信された値を持つ変数 `user` になります。

#### CSRF

```twig
{{ csrfInput() }}
```

<config:enableCsrfProtection> 設定で無効にしていない限り、Craft の[クロスサイトリクエストフォージェリ](https://en.wikipedia.org/wiki/Cross-site_request_forgery)プロテクションのため、`csrfInput()` ジェネレータファンクションはすべてのフォームで必須となります。

#### オプションのリダイレクト

```twig
{# {{ redirectInput('users/'~currentUser.username) }} #}
```

この行はコメントアウトされていますが、保存が成功したら別のページにリダイレクトできることを表しています。おそらく、ユーザー名に基づくユーザーのホームページです。

```twig
<input type="hidden" name="userId" value="{{ formUser.id }}">
```

適切なユーザーをアップデートするには、ユーザー ID が必要です。他のユーザーのプロフィールを編集することを許可しないようグループ権限がセットされているか、確認してください。

#### 名前フィールド

```twig
<div class="group">
  <label for="first-name">First Name</label>
  <input type="text" id="first-name" name="firstName" value="{{ formUser.firstName }}">
</div>

<div class="group">
  <label for="last-name">Last Name</label>
  <input type="text" id="last-name" name="lastName" value="{{ formUser.lastName }}">
</div>
```

これらのフィールドはバリエーションを必要としないため、かなり簡単です。

#### ユーザーフォト

```twig
{% if formUser.photo %}
  <div class="group">
    <label>Photo</label>
    <img id="user-photo" src="{{ formUser.photo.url({width: 150}) }}" alt="">
  </div>

  <div class="group">
    <label for="delete-photo">
      <input id="delete-photo" type="checkbox" name="deletePhoto">
      Delete photo
    </label>
    <p class="instruction">If a new photo is selected, this checkbox has no effect.</p>
  </div>
{% endif %}

<div class="group">
  <label for="photo">Select photo</label>
  <input id="photo" type="file" name="photo" accept="image/png,image/jpeg">
</div>
```

ユーザーフォトが存在する場合、それを削除するオプションのためのチェックボックスを含めて表示します。たとえ何があろうと、新しい写真を選ぶことができるようファイルフィールドを表示します。このセクションが洗練されていないと感じる場合、JavaScript による拡張が役立つかもしれません。それはあなた次第です。

#### ユーザー名

```twig
{% if not craft.app.config.general.useEmailAsUsername %}
  {% set error = formUser.getFirstError('username')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{  class }}">
    <label for="username">Username <span class="error-symbol">&#9888;</span></label>
    <p class="instruction">If left blank, this will become the email address.</p>

    <p class="error-message">{{ error }}</p>
    <input type="text" id="username" name="username" value="{{ formUser.username }}">
  </div>
{% endif %}
```

コンフィグ設定 <config:useEmailAsUsername> を `true` にセットした場合、ユーザー名フィールドは表示されません。

バリデーションが有効になるのは、ここからです。変数 `error` に `getFirstError('username')` をセットすると、このフィールドにエラーがあるかどうかを伝えてくれます。（そうでなければ `null` になります。）エラーがある場合、それを開示するための HTML 要素の適切な class 名をセットし、エラーメッセージを表示します。

class 名に基づく HTML 要素を表示・非表示にするためのスタイルを [追加](#extras) セクションで見るけることができるでしょう。もちろん、あなたがしたいように操作できます。

#### メール

```twig
{% set error = formUser.getFirstError('email')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{  class }}">
  <label for="email">Email <span class="error-symbol">&#9888;</span></label>

  {% if craft.app.projectConfig.get('users.requireEmailVerification') %}
    <p class="instruction">New email addresses need to be verified.</p>
  {% endif %}

  <p class="error-message">{{ error }}</p>
  <input type="text" id="email" name="email" value="{{ formUser.email }}">
</div>
```

コントロールパネルの「設定 > ユーザー > 設定」で「メールアドレスを確認しますか？」のチェックボックスを ON にしている場合、ユーザーが新しいメールアドレスを確認することを期待するメッセージを表示することを除けば、ユーザー名フィールドと同様です。[現在のパスワード](#current-password)フィールドは、メールアドレスを変更するために必須です。

#### パスワード

```twig
{% set error = formUser.getFirstError('newPassword')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{ class }}">
  <label for="new-password">New Password  <span class="error-symbol">&#9888;</span></label>
  <p class="error-message">{{ error }}</p>
  <input type="password" id="new-password" name="newPassword" value="{{ formUser.newPassword }}">
</div>
```

ユーザーはパスワードを変更できますが、[現在のパスワード](#current-password)も入力する必要があります。入力されたパスワードが短すぎる場合、エラーになります。

#### 現在のパスワード

```twig
{% set error = formUser.getFirstError('currentPassword')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{ class }}">
  <label for="current-password">Current Password <span class="error-symbol">&#9888;</span></label>
  <p class="instruction">Required to change Password and Email</p>
  <p class="error-message">{{ error }}</p>
  <input type="password" id="current-password" name="password" value="">
</div>
```

このフィールドはメールアドレス、または、パスワードが変更された場合、必須となります。それ以外の場合、ユーザーは空白のままにできます。他のフィールドの状態に基づいてこれを表示・非表示にするために、いくつかの素晴らしい JavaScript を使用できます。

#### カスタムフィールド：Bio

```twig
{% set error = formUser.getFirstError('bio')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{ class }}">
  <label for="bio">Bio <span class="error-symbol">&#9888;</span></label>
  <p class="error-message">{{ error }}</p>
  <textarea id="bio" name="fields[bio]">{{ formUser.bio }}</textarea>
</div>
```

「設定 > ユーザー > フィールド」にあるユーザープロフィールのフィールドレイアウトで `bio` というハンドルのカスタムフィールド名 “Bio” を追加したとします。それはまた、必須のフィールドだとします。ここでの違いは、カスタムフィールドが `field[<fieldname>]` のような名前の配列 `fields` に属していることです。

:::tip
行列やサードパーティプラグインのような複雑なカスタムフィールドの操作は、理解しにくいと思うでしょう。それらの種類のフィールドを処理する方法を知るために、コントロールパネルでユーザープロフィールフォームのソースコードを見てください。
:::

#### フォームの送信

```twig
<div class="group">
  <input type="submit" value="Save Profile">
  <a href="{{ craft.request.url }}">Reset</a>
</div>
```

現在のページをリロードするためのリンクは、変数 `currentUser` を使用し、さらにバリデーションエラーが消去されるため、フォームをリセットするのに良い方法です。

## 追加

これはブラウザでこのページのフォームをより読みやすくするためのスタイル定義です。

```html
<style>
  #profile {
    width: 30rem;
  }

  #profile .group + .group {
    margin-top: 2em;
  }

  #profile label {
    display: block;
    font-weight: bold;
  }

  #profile input[type="text"],
  #profile input[type="password"] {
    margin: .5em 0;
    padding: .5em;
    width: 100%;
    font-size: 1em;
  }

  #profile .instruction {
    font-size: .75em;
    margin: .25em;
  }

  #profile .group .error-message,
  #profile .group .error-symbol{
    display: none;
  }

  #profile .group.has-error label,
  #profile .group.has-error .error-message {
    display: block;
    color: darkred;
    font-size: .75em;
    margin: .25em;
  }

  #profile .error-list {
    color: darkred;
    padding: 0 1em;
    border: 1px solid darkred;
    margin-bottom: 2em;
  }

  #profile .group.has-error .error-symbol {
    display: inline;
    font-size: 1.25em;
  }

  #profile .group.has-error input {
    border: 1px solid darkred;
  }
</style>
```

