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
placed directly within the Kernel before any extensions are initialized.

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
        $sections = $container->getParameter('app_sections');
        (new SectioningFactory($container, 'app.section'))
            // You can use choose to container parameters or load them from the Enviroment (requires manual cache:clear)
            // Only sections defined in $requiredSections are registered.
            ->set('frontend', $sections['frontend'])
            ->set('backend', $sections['backend'])
            
            // ->set('backend', $_ENV['APP_BACKEND_URL'] ?? '/admin') # Note: Requires a manual cache:clear
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
        frontend: 'example.com/'
        backend: 'https://example.com/admin'
```

Or loading them from a `.env` file:

```bash
# NOTE. Changing this value requires a manual cache:clear
APP_FRONTEND_URL="example.com/"
APP_BACKEND_URL="https://example.com/admin"
```

**Caution:** A prefix must always begin with a `/` otherwise `admin/` is seen
as host `admin` and prefix `/`.

### Dynamic host requirements

The host allows the usage of variables with accepted values separated by `|`; 
Eg. `com|net`. As you would in the Routing system (except that regexp is not 
supported).

An variable is defined as `{variable-name;default-value;accepted-values}`

```yaml
parameters:
    app_sections:
        frontend: 'example.{tld;com;com|net}/'
        backend: 'example.{tld;nl;nl|de}/'
```

Or loading them from a `.env` file:

```bash
# NOTE. Changing this value requires a manual cache:clear
APP_FRONTEND_URL="example.{tld;com;com|net}/"
APP_BACKEND_URL="example.{tld;nl;nl|de}/"
```

### Container parameters

The SectioningFactory registers a number of service-container parameters
for usage in your service definitions, [security config] and [routing]
schema.

Parameters are registered as follow:

```
'app.section.frontend.is_secure'    : false
'app.section.channel'               : null
'app.section.frontend.host'         : 'example.com'
'app.section.frontend.domain'       : 'example.com'
'app.section.frontend.host_pattern' : '^example\.com$'
'app.section.frontend.prefix'       : '/'
'app.section.frontend.path'         : '^/(?!(backend|api)/)'
```

**Note:**

* The `host_pattern` and `path` are regular expressions, `host_pattern`
  matches completely but `path` will only matches the beginning of a uri.

* The `host` may contain variables such as `example.{tld}` use `domain`
  which only contains a value _if_ the host doesn not contain any variables.

* The `channel` is only set to 'https' when `is_secure` is true. 
  _This prevents forcing an HTTP channel when HTTPS is used._

The `app.section.frontend.request_matcher` service provides a `RequestMatcher`
for a firewall and other services.

All parameters follow the same `{service-prefix}.{section-name}` pattern.

The Service-prefix is configured as the second parameter of the `SectioningFactory`
used when registering (`app.section` in the example above).

And `{section-name}` as the name of the section (like 'frontend').

[security firewall]: firewall.md
[routing]: routing.md

## Limitations

The main purpose of this library is separating the application's UI into
multiple sections, using the host and prefix; The schema is used to 
configure `is_secure`.

* The prefix does not allow the usage of variables (you can still use variables
  in routes themselves);
* The host variables requirements do not allow regexp;
* The host accepts an IP address but does not allow variables then.
