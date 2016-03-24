Installation
============

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ php composer.phar require parkmanager/app-sectioning-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new ParkManager\Bundle\AppSectioning\ParkManagerAppSectioningBundle(),
            // ...
        ];

        // ...
    }

    // ...
}
```

You are now ready to use the Park-Manager AppSectioning configurator bundle.

Continue to [Configuration](configuration.md) to learn more about usage and implementation.
