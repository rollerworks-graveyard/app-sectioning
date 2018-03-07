Routing configuration
=====================

If you already configured the [security firewalls](firewall.md) it's now time 
to configure the routing.

**Note:** A section needs to be at the root of importing (eg. config/routes.yaml).

## Route loader

Instead of importing your routes directly, you import them trough the 
`app_section` route loader.

The syntax for a resource is as follow: `section-name#actual-resource`, 
or `section-name:type#actual-resource` if you need to import with a specific
resource type.

```yaml
# config/routes.yml

_app_frontend:
    resource: 'frontend:yml#@AppCoreBundle/Resources/config/routing/frontend.yml'
    type: app_section

_app_backend:
    resource: 'backend#@AppCoreBundle/Resources/config/routing/backend.yml'
    type: app_section
```
