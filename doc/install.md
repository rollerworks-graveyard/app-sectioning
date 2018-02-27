Installation
============

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this library:

```bash
$ php composer.phar require rollerworks/app-sectioning
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

You are now ready to use the `SectioningFactory` for registering your application
sections.

Registering Sections
--------------------

**Note:** The SectioningFactory needs to register the app-sections parameters
before any Extension can use them. Because of this, the SectioningFactory is
placed directly within the Kernel bore any extensions initialized.

The AppSectioning configurator helps with separating your Symfony application
into multiple sections (eg. frontend and backend). Each with there own
configurable URI pattern.

The examples in this document expect you _can_ update the Applications's 
Kernel by either keeping everything in a single application or using a custom
Symfony Flex recipe that overwrites the default Kernel class.

```php
// src/Kernel.php

use Rollerworks\Component\AppSectioning\SectioningFactory;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // ...

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        (new SectioningFactory($container, 'acme.section'))
            // You can use choose to container parameters or load them from the Enviroment (requires manual cache:clear)
            // Only sections defined in $requiredSections are registered.
            ->fromArray(/* $requiredSections /*['frontend', 'backend'], $container->getParameter('app_sections'))
            // fromJson(['frontend', 'backend'], $_ENV['app_sections'] ?? '') # Note: Requires a manual cache:clear
            ->register();
            
        // ...
    }
}
```


That's it! You can now configure the sections with a custom host and prefix 
using the following configuration:

```yaml
parameters:
    app_sections:
        frontend:
            prefix: /
            host: example.com
        backend:
            prefix: /admin
            host: example.com
            
            # Same as any routing configuration
            # For host attributes a few limitations apply (see below)
            requirements: { } # Cannot be null; defaults to { }
            defaults: { }     # Cannot be null; defaults to { }
```

Or loading them from a `.env` file (using `fromJson`):

```bash
# NOTE. Changing this value requires a manual cache:clear
APP_SECTIONS='{"frontend":{"prefix":"\/","host":"example.com"},"backend":{"prefix":"\/admin","host":"example.com"}}'
```

**Tip:** http://www.unserialize.me/ allows to convert from YAML to JSON.

### Dynamic host requirements

The host configuration allows the usage attributes using non-regexp accepted
values separated by `|`; Eg. `com|net`. As you would in the Routing system.

With a minor exception, you must define the requirements and defaults for all host attributes;
And the host requirements only allow usage of a `|` but no any other regexp.

```yaml
parameters:
    app_sections:
        frontend:
            prefix: /
            host: example.{tld}
            defaults: 
                tld: com
            requirements: 
                tld: 'com|net'
        backend:
            prefix: /
            host: example.{tld}
            defaults: 
                tld: nl
            requirements: 
                tld: 'nl|de'
                section: { title: backend } # Only attributes used by the host are validated, all others are passed to the Route definition as-is
```

Or loading them from a `.env` file (using `fromJson`):

```bash
# NOTE. Changing this value requires a manual cache:clear
APP_SECTIONS='{"frontend":{"prefix":"\/","host":"example.{tld}","defaults":{"tld":"com"},"requirements":{"tld":"com|net"}},"backend":{"prefix":"\/","host":"example.{tld}","defaults":{"tld":"nl"},"requirements":{"tld":"nl|de"}}}'
```

### Container parameters

In your bundle services or application [security firewall], [routing] etc.
you use the service-container parameters as follow:

```
'acme.section.frontend.host'         : 'example.com'
'acme.section.frontend.host_pattern' : '^example\.com$'
'acme.section.frontend.prefix'       : '/'
'acme.section.frontend.path'         : '^/(?!(backend|api)/)'
```

And in addition you have the `acme.section.frontend.host_requirements` and
`acme.section.frontend.host_default` which are primarily used for routing.

**Note:**

> `host_pattern` and `path` are regular expressions, `host_pattern` will 
> match completely but `path` will only check the beginning of the uri.
>
> The `host` may contain attributes such as `example.{tld}`

The `acme.section.frontend.request_matcher` service provides a `RequestMatcher`
for the firewall and other services.

All parameters follow the same `{service-prefix}.{section-name}` pattern.

The Service-prefix is configured as the second parameter of `SectioningFactory`
used when registering (`acme.section` in the example above).

And `{section-name}` the name of the section (like 'frontend').

[security firewall]: firewall.md
[routing]: routing.md

## Limitations

The main purpose of this library is separating the application's UI into
multiple sections, using the host and prefix.

* Attributes in the prefix are not supported;
* The routing doesn't allow setting conditions or schema's;
* Host requirements only allow the usage of `|` but no regexp;
* An IP address as host requirement cannot have attributes.
