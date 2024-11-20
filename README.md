A simple middleware for Slim4 framework for using locale in uri. Usage is very simple, just add in your container settings this array:
```
'settings_global' => [
        'language' => 'it',
        'languages' => ['it', 'en'],
		    'locale' => true,
]
```
And enable this Middleware (in the Middleware part of your Slim4 Project) with:
```
<?php

use PerSeo\Middleware\Locale\Locale;

$app->add(Locale::class);
```
After this, all your Slim 4 routes works without the language prefix in routes (because this Middleware check the language before routes are called). To retrive what language the project is using, just call:
```
$request->getAttribute('locale');
```
Simple, isn't it?
