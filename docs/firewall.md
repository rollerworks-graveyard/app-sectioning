Security firewall configuration
===============================

Once your sections are registered and configured, you can use the parameters 
for various parts of your application. Including the security firewalls.

Say you have two sections with the same service-prefix `app.section`,
and want to configure the firewalls:

```yaml
# config/packages/security.yaml
security:

    # ...

    firewalls:
        frontend:
            pattern: '%app.section.frontend.path%'
            host: '%app.section.frontend.path%'

            # Or if you have no special matcher requirements
            # request_matcher: app.section.frontend.request_matcher
            
            # remember_me:
            #     secret:               '%kernel.secret%'
            #     token_provider:       ~
            #     catch_exceptions:     true
            #     name:                 FRONTEND_REMEMBERME
            #     lifetime:             604800 # one week
            #     path:                 '%app.section.frontend.prefix%'
            #     domain:               '%app.section.frontend.domain%'
            #     secure:               '%app.section.frontend.is_secure%'
            #     httponly:             true
            #     always_remember_me:   false

        backend:
            request_matcher: app.section.backend.request_matcher

    access_control:
        # Notice. Path always ends with a /
        
        # Backend
        -
            path: '%app.section.backend.path%login$', 
            host: '%app.section.backend.host_pattern%'
            requires_channel: '%app.section.backend.channel%'
            
            role: IS_AUTHENTICATED_ANONYMOUSLY
        
        -
            path: '%app.section.backend.path%'
            host: '%app.section.backend.host_pattern%'
            requires_channel: '%app.section.backend.channel%'
            
            role: ROLE_ADMIN

        # Frontend
        -
            path: '%app.section.frontend.path%'
            host: '%app.section.frontend.host_pattern%'
            requires_channel: '%app.section.frontend.channel%'
            
            role: ROLE_USER

```

That's it, the firewall will now use the sections configuration.
And don't worry about the correct order, each path is constructed
to never match for other sections within the same host group!
