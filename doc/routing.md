Routing configuration
=====================

If you already configured the [security firewalls](firewall.md) it's now time 
to configure the routing.

**Note:** A section needs to be at the root of importing, you cannot import 
a section as part of another import with a prefix and/or host.

## Route loader

Instead of importing your routes directly, you import them trough the 
`app_section` route loader.

The syntax for a resource is as follow: `section-name#actual-resource`, 
or `section-name:type#actual-resource` if you need to import with a specific
resource type.

```yaml
# config/routes.yml

_acme_frontend:
    resource: 'frontend:yml#@AcmeCoreBundle/Resources/config/routing/frontend.yml'
    type: app_section

_acme_backend:
    resource: 'backend#@AcmeCoreBundle/Resources/config/routing/backend.yml'
    type: app_section
```
