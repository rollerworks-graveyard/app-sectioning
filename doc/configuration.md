Configuration
=============

The AppSectioning configurator helps with separating your Symfony application
into multiple sections (eg. frontend and backend). Each with there own
configurable URI pattern.

The examples in this document assume you have one or more bundles for keeping
your configuration. Don't worry if sections are configured per bundle, the system
will handle this nicely.

## Register a section's configuration tree

First update your bundle's `Configuration` class to add the section configurator:

```php
// src/Acme/FrontendBundle/DependencyInjection/Configuration.php
namespace Acme\FrontendBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Rollerworks\Bundle\AppSectioning\DependencyInjection\SectioningConfigurator;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('acme_frontend');

        $rootNode
            ->children()
                // 'section' is config name as used in the tree.
                // in the end the section is registered in your bundle extension config
                // as `acme_frontend.section`
                ->append(SectioningConfigurator::createSection('section'))

                // Optionally add an extra section
                //->append(SectioningConfigurator::createSection('second_section'))
            ->end()
        ;

        return $treeBuilder;
    }
}
```

The `SectioningConfigurator::createSection()` method adds the required
configuration parts to the Configuration tree (prefix and host).

Next update your bundle's Extension class to get the section(s) registered
the service container:

```php
// src/Acme/FrontendBundle/DependencyInjection/AcmeFrontendExtension.php
namespace Acme\FrontendBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Rollerworks\Bundle\AppSectioning\DependencyInjection\SectioningFactory;

class AcmeFrontendExtension extends ConfigurableExtension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $factory = new SectioningFactory($this->container, 'acme.section');
        $factory->set('frontend', $config['section']));
        //$factory->set('backend', $config['second_section']));

        // ...
    }

    // ...
}
```

That's it! Users of your bundle can now configure the `frontend` section
with a custom host and prefix using the following configuration:

```yaml
acme_frontend:
    section:
        prefix: /
        host: example.com
```

### Dynamic host requirements

Your host can also use regex requirements as you would in the Routing
system. With exception, you must define the requirements and defaults
for all attributes.

```yaml
acme_frontend:
    section:
        prefix: /
        host: example.{tld}
        defaults: 
            tld: com
        requrements: 
            tld: 'com|net'
```

### Container parameters

In your bundle services or application [security firewall], [routing] etc.
you use the service-container parameters like:

```
'acme.section.frontend.host'         : 'example.com'
'acme.section.frontend.host_pattern' : '^example\.com$'
'acme.section.frontend.prefix'       : '/'
'acme.section.frontend.path'         : '^/(?!(backend|api)/)'
```

And in addition you have the `acme.section.frontend.host_requirements` and
`acme.section.frontend.host_default` which are primarily used for routing.

**Note:** 

> `host_pattern` and `path` are regular expressions, host will match
> completely but path will only check the beginning of the uri.
>
> The `host` may contain attributes such as `example.{tld}`

The `acme.section.frontend.request_matcher` service provides a
configured `RequestMatcher` for the firewall and other services.

All parameters follow the same `{service-prefix}.{section-name}` pattern.
Service-prefix is value of the second parameter of `SectioningFactory`
used when registering (`acme.section` in the example above).

And `{section-name}` the name of the section (like 'frontend').

[security firewall]: firewall.md
[routing]: routing.md

## Limitations

* Placeholders/attributes in the prefix are not yet supported.
  See also [Add placeholder support for prefix and host in the issue tracker](https://github.com/rollerworks/app-sectioning-bundle/issues/1)

* Unicode support is not fully supported yet.

* The routing doesn't allow setting conditions or schema's.
