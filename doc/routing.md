Routing configuration
=====================

If you already configured the [security firewalls](firewall.md)
it's now time to configure the routing.

Note that a section needs to be at the root of importing,
you cannot import a section as part of another import with prefix and/or host.

## Route loader

Instead of importing your routes directly, you import them trough the app_section route loader.

The syntax for a resource is as follow: `section-name#actual-resource`, or `section-name:type#actual-resource`
if you need to import a specific resource type.

```yaml
# app/config/routing.yml

acme_frontend:
    resource: 'frontend:yml#@AcmeFrontendBundle/Resources/config/routing.yml'
    type: app_section

acme_backend:
    resource: 'backend#@AcmeBackendBundle/Resources/config/routing.yml'
    type: app_section
```
