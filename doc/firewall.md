Security firewall configuration
===============================

Once your sections are registered and configured, you can use the parameters 
for various parts of your application. Including the security firewalls.

Say you have two sections with the same service-prefix `acme.section`,
and want to configure the firewalls:

```yaml
# config/packages/security.yml
security:

    # ...

    firewalls:
        frontend:
            pattern: '%acme.section.frontend.path%'
            host: '%acme.section.frontend.path%'

            # Or if you have no special matcher requirements
            # request_matcher: acme.section.frontend.request_matcher

        backend:
            request_matcher: acme.section.backend.request_matcher

```

That's it, the firewall will now use the sections configuration.
And don't worry about the correct order, each path is constructed
to never match for other sections!
