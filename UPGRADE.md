UPGRADE
=======

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
