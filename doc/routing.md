Routing configuration
=====================

If you already configured the [security firewalls](firewall.md)
it's now time to configure the routing.

Note that a section needs to be at the root of importing,
you cannot import a section as part of another import with prefix and/or host.

**Caution:** Make sure to use prefix (not path)!

```yaml
# app/config/routing.yml

acme_frontend:
    resource: '@AcmeFrontendBundle/Resources/config/routing.yml'
    prefix:   "%acme.section.frontend.prefix%"
    host:     "%acme.section.frontend.host%"
    requirements:
        host: "%acme.section.frontend.host_pattern%"
    defaults:
        host: "acme.section.frontend.host%"

acme_backend:
    resource: '@AcmeBackendBundle/Resources/config/routing.yml'
    prefix:   "%acme.section.backend.prefix%"
    host:     "%acme.section.backend.host%"
    requirements:
        host: "%acme.section.backend.host_pattern%"
    defaults:
        host: "acme.section.backend.host%"
```
