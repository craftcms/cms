# Volume Types

Plugins can provide custom asset volume types by creating a class that implements <api:craft\base\VolumeInterface> and <api:craft\base\VolumeTrait>. The class will serve both as a way to communicate various things about your volume type (with static methods), and as a model that volumes of its type will be instantiated with.

As a convenience, you can extend <api:craft\base\Volume>, which provides a base volume type implementation, optimized for [Flysystem](https://flysystem.thephpleague.com/) adapters.

You can refer to Craft’s own volume classes for examples. They are located in `vendor/craftcms/cms/src/volumes/`.

## Registering Custom Volume Types

Once you have created your volume class, you will need to register it with the Volumes service, so Craft will know about it when populating the list of available volume types:

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Volumes;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Volumes::class, Volumes::EVENT_REGISTER_VOLUME_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyVolume::class;
        });

        // ...
    }

    // ...
}
```
