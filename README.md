Rollerworks AppSectioning configurator
======================================

The AppSectioning configurator helps with separating your Symfony application
into multiple sections (eg. frontend and backend). Each with there own
configurable URI pattern.

But this library does more!

Say there are two sections:

* Frontend - `example.com/`
* Backend  - `example.com/backend/`

Unless the 'backend' section is tried earlier the 'frontend' will always match!
To prevent this, each path (regexp) is constructed to never match for other
sections within the same host group!.

You then use these generated parameters for routing and the security firewalls.

Requirements
------------

You need at least PHP 7.1, the Symfony DependencyInjection, Routing and
HttpFoundation Components. The FrameworkBundle and SecureBundle are optional.

Documentation
-------------

 * [Installation](docs/install.md)
 * [Security firewall](docs/firewall.md)
 * [Routing](docs/routing.md)

Versioning
----------

For transparency and insight into the release cycle, and for striving
to maintain backward compatibility, this package is maintained under
the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit <http://semver.org/>.

License
-------

The package is provided under the [MIT license](LICENSE).
