UPGRADE
=======

## Upgrade FROM 0.5 to 0.6

* The `SectioningFactory::fromJson()` and `SectioningFactory::fromArray()`
  methods were removed.
  
* The second argument of the `SectioningFactory::set()` method now expects 
  an URI pattern instead of an array. 
  
  See [documentation](docs/index.md) for supported formats.
  
* Non-host defaults and requirements for imported routes now need to be
  declared in the imported routes instead. _The string URI format only
  allow accepts variables for the host._

## Upgrade FROM 0.4 to 0.5

* The namespace has changed from `Rollerworks\Bundle\AppSectioningBundle`
  to `Rollerworks\Component\AppSectioning` as this configuration helper
  no longer requires a bundle-type integration.

* Host requirements no longer allow regexp, only `|` for multiple accepted values.
  
* The Validator was combined with the Configurator. The Validator class
  has been removed.

* Prefix now explicitly disallows attributes (this wasn't supported but 
  now it’s forbidden).
  
* Section can now only be registered at a single point.
  Late resolving had to many issues and has been removed.
  
* The `SectioningFactory` now requires `register()` is called after all
  sections are set.
  
  ```php
  (new SectioningFactory($container, 'acme.sections'))
      ->set('section-name', ['configuration'])
      ->set('section-name2', ['configuration'])
      ->register();
  ```
  
  **Tip**: The SectioningFactory now allows to register from an array 
  or JSON string (eg. Environment value).

## Upgrade FROM 0.3 to 0.4

* The namespace has changed from `Rollerworks\Bundle\AppSectioning`
  to `Rollerworks\Bundle\AppSectioningBundle` to make Symfony Flex work.
  
* Support for Symfony 2.8 was dropped, you now need at least Symfony 3.2

## Upgrade FROM 0.2 to 0.3

The vendor-namespace changed to `Rollerworks`.

You need to change `ParkManager\Bundle\AppSectioning`
to `Rollerworks\Bundle\AppSectioning` in your Bundle extension
classes.

All other options, and classes have remain almost unchanged.
