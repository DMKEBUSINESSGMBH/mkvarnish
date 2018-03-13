vcl 4.0;

import std;
import directors;

# Backend definition
backend be_localhost {
    # host may also be a docker service name
    .host = "127.0.0.1";
    .port = "8080";
    .connect_timeout = 5s;
    .first_byte_timeout = 120s;
    .between_bytes_timeout = 120s;
}

acl purge {
    "127.0.0.1";
    "192.168.255.0"/24;
}

sub vcl_fini {
    return (ok);
}

#
## Called after a request is received from the browser,
## but before it is processed.
#
sub vcl_recv {

    # debug id for customer
    set req.http.X-Debug-ID = "12473148735471684";

    # X-Forwarded-For Behandlung
    if (req.restarts == 0) {
      if (req.http.x-forwarded-for) {
        if (std.port(server.ip) == 443) {
          # SSL Proxy IP entfernen (127.0.0.1)
          set req.http.X-Forwarded-For = regsub(req.http.X-Forwarded-For, ", 127.0.0.1$", "");
          std.log("RealIP:" + regsub(req.http.X-Forwarded-For, "^(?:.*[, ]+|)(.+)$", "\1"));
        } else {
          std.log("RealIP:" + client.ip);
        }
      } else {
        set req.http.X-Forwarded-For = client.ip;
        std.log("RealIP:" + client.ip);
      }
    }

    # purging is only allowed from localhost and local net
    if (req.method == "PURGE") {
        if (client.ip !~ purge ||
            std.ip(regsub(req.http.x-forwarded-for, "^(?:.*[, ]+|)(.+)$", "\1"), "0.0.0.0") !~ purge) {
            return (synth (405, "Method Not Allowed."));
        }

        ### check purge all command
        if(req.http.X-Varnish-Purge-All == "1" && req.http.X-TYPO3-Sitename) {
            ban("req.url ~ /" + " && obj.http.X-TYPO3-Sitename == " + req.http.X-TYPO3-Sitename);
            return (synth(200, "Banned all on site "+ req.http.X-TYPO3-Sitename)) ;
        } else if(req.http.X-Varnish-Purge-All == "1") {
            ban("req.url ~ /");
            return (synth(200, "Banned all"));
        }

        ### check purge by tags command
        if(req.http.X-Cache-Tags && req.http.X-TYPO3-Sitename) {
            ban("obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags + " && obj.http.X-TYPO3-Sitename == " + req.http.X-TYPO3-Sitename);
            return (synth(200, "Banned cache tags " + req.http.X-Cache-Tags + " on site " + req.http.X-TYPO3-Sitename));
        } else if(req.http.X-Cache-Tags) {
            ban("obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags);
            return (synth(200, "Banned cache tags " + req.http.X-Cache-Tags)) ;
        }

        return (purge);
    }

    // block w00tw00tt and other attacks
    // block backup and private files too
    if (
        req.url ~ "^/w00tw00t" ||
        req.url ~ "^/phppath/" ||
        req.url ~ "^/pma/" ||
        req.url ~ "^/phpMyAdmin" ||
        req.url ~ "^/phpmyadmin" ||
        req.url ~ "wp-(admin|login|content)" ||
        req.url ~ "\.inc\.php" ||
        req.url ~ "^/%70%68%70%70%61%74%68/" ||
        req.url ~ "\.(bak|backup|conf|log|properties|sql|tar)$" ||
        req.url ~ "/Private/" ||
        req.url ~ "typo3conf/ext/(.*)/Configuration/(TypoScript|FlexForms|TCA|ExtensionBuilder)/"
    ) {
        return(synth(403,  "Not permitted"));
    }

    # Non-RFC2616 or CONNECT which is weird.
    if (req.method != "GET" &&
        req.method != "HEAD" &&
        req.method != "PUT" &&
        req.method != "POST" &&
        req.method != "TRACE" &&
        req.method != "OPTIONS" &&
        req.method != "DELETE") {
        return (pipe);
    }

    if (req.url ~ "(\?|&)(utm_source|utm_medium|utm_campaign|utm_content|gclid|cx|ie|cof|siteurl)=") {
        set req.url = regsuball(req.url, "&(utm_source|utm_medium|utm_campaign|utm_content|gclid|cx|ie|cof|siteurl)=([A-z0-9_\-\.%25]+)", "");
        set req.url = regsuball(req.url, "\?(utm_source|utm_medium|utm_campaign|utm_content|gclid|cx|ie|cof|siteurl)=([A-z0-9_\-\.%25]+)", "?");
        set req.url = regsub(req.url, "\?&", "?");
        set req.url = regsub(req.url, "\?$", "");
    }

    # Remove any Google Analytics based cookies
    set req.http.Cookie = regsuball(req.http.Cookie, "__utm.=[^;]+(; )?", "");
    set req.http.Cookie = regsuball(req.http.Cookie, "_ga=[^;]+(; )?", "");
    set req.http.Cookie = regsuball(req.http.Cookie, "_gat=[^;]+(; )?", "");
    set req.http.Cookie = regsuball(req.http.Cookie, "utmctr=[^;]+(; )?", "");
    set req.http.Cookie = regsuball(req.http.Cookie, "utmcmd.=[^;]+(; )?", "");
    set req.http.Cookie = regsuball(req.http.Cookie, "utmccn.=[^;]+(; )?", "");

    # remove piwik cookies
    set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(_pk_(ses|id)[\.a-z0-9]*)=[^;]*", "");

    # Discard any X-Forwarded-Protocol-Header for security reasons
    if (req.http.X-Forwarded-Protocol) {
         unset req.http.X-Forwarded-Protocol;
    }

    # If the incoming connections port is 443, then probably stunnel with SSL will be used
    # Set a new X-Forwarded-Protocol-Header with value "https"
    if (std.port(server.ip) == 443) {
         set req.http.X-Forwarded-Protocol = "https";
    }

    #Even though there are few possible values for Accept-Encoding, Varnish treats
    #them literally rather than semantically, so even a small difference which makes
    #no difference to the backend can reduce cache efficiency by making Varnish cache
    #too many different versions of an object.
    #http://varnish.projects.linpro.no/wiki/FAQ/Compression
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(?:jpg|png|gif|gz|tgz|bz2|tbz|mp3|ogg)$") {
            # No point in compressing these
            unset req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            # unkown algorithm
            unset req.http.Accept-Encoding;
        }
    }

    # We only deal with GET and HEAD by default
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    # Strip hash as the server doesn't need it
    if (req.url ~ "\#") {
        set req.url = regsub(req.url, "\#.*$", "");
    }

    # normalize url in case of leading HTTP scheme and domain
    set req.url = regsub(req.url, "^http[s]?://", "");

    # Remove a ";" prefix in the cookie if present
    set req.http.Cookie = regsuball(req.http.Cookie, "^;\s*", "");

    # Are there cookies left with only spaces or that are empty?
    if (req.http.cookie ~ "^\s*$") {
        unset req.http.cookie;
    }

    # Large static files are delivered directly to the end-user without
    # waiting for Varnish to fully read the file first.
    # Varnish 4 fully supports Streaming, so set do_stream in vcl_backend_response()
    if (req.url ~ "^[^?]*\.(7z|avi|bz2|flac|flv|gz|gzip|mka|mkv|mov|mp3|mp4|mpeg|mpg|ogg|ogm|opus|rar|tar|tgz|tbz|txz|wav|webm|xz|zip)(\?.*)?$") {
        unset req.http.Cookie;
        return (pass);
    }

    # Remove all cookies for static files
    # A valid discussion could be held on this line: do you really need to cache static files that don't cause load? Only if you have memory left.
    # Sure, there's disk I/O, but chances are your OS will already have these files in their buffers (thus memory).
    # Before you blindly enable this, have a read here: https://ma.ttias.be/stop-caching-static-files/
    if (req.url ~ "^[^?]*\.(7z|avi|bmp|bz2|css|csv|doc|docx|eot|flac|flv|gif|gz|ico|jpeg|jpg|js|less|mka|mkv|mov|mp3|mp4|mpeg|mpg|odt|otf|ogg|ogm|opus|pdf|png|ppt|pptx|rar|rtf|svg|svgz|swf|tar|tbz|tgz|tiff|ttf|txt|txz|wav|webm|webp|woff|woff2|xls|xlsx|xml|xz|zip)(\?.*)?$") {
          unset req.http.Cookie;
          return (pass);
    }

    # If any autorisation was set do not cache
    if (req.http.Authorization || req.http.Cookie ~ "fe_typo_user") {
        return (pass);
    }

    # If we work in backend
    if (req.http.Cookie ~ "be_typo_user") {
        # Delete cache depending on TYPO3 cache control
        if (req.http.Cache-Control ~ "no-cache") {
            set req.ttl = 0s;
            ban("req.url == " + req.url);
        }
        return (pass);
    } else {
        # Pass all no_cache=1 sites and eID scripts
        if (req.url ~ "(\?|&)no_cache=1" || req.url ~ "(\?|&)eID=") {
            return (pass);
        }
    }

    ### if varnish is only in front of cacheable domains
    ### only the content of the if branch is needed
    if (req.http.host == "example.com") {
        return (hash);
    } else {
        return (pass);
    }
}

#
## Called when a request must be forwarded directly to the backend
## with minimal handling by Varnish (think HTTP CONNECT)
#
sub vcl_pipe {
    # Note that only the first request to the backend will have
    # X-Forwarded-For set.  If you use X-Forwarded-For and want to
    # have it set for all requests, make sure to have:
    # set bereq.http.connection = "close";
    # here.  It is not set by default as it might break some broken web
    # applications, like IIS with NTLM authentication.
    return (pipe);
}

#
## Called when the request is to be passed to the backend
## without looking it up in the cache.
#
sub vcl_pass {
    return (fetch);
}

#
## Called on purge of object
#
sub vcl_purge {
    return (synth(200, "Object purged"));
}

#
## Called to determine the hash key used to look up
## a request in the cache.
#
sub vcl_hash {
    # Calculate hash based on the requested URL
    hash_data(req.url);

    # deliver different objects for http/https per default
    hash_data(std.port(server.ip));

    # If the Host-header is set, append it to the hash
    if (req.http.host) {
        hash_data(req.http.host);
    # if not, append the local server-ip
    } else {
        hash_data(server.ip);
    }
    return (lookup);
}

#
## Called after a cache (hash) when the object requested
## has been found in the cache.
#
sub vcl_hit {
    # save some information for later use
    set req.http.X-Lifetime = obj.ttl;

    return (deliver);
}

#
## Called after a cache (hash) when the object requested
## was NOT found in the cache.
#
sub vcl_miss {
    # if it's a PURGE requests, we've nothing found in cache
    if (req.method == "PURGE") {
        return (synth (404, "Object not found"));
    }

    return (fetch);
}

sub vcl_backend_fetch {
    return (fetch);
}

#
## Called when the request has been sent to the backend
## and a response has been received from the backend.
#
sub vcl_backend_response {

    set beresp.grace = 1m;

    # save some information for later use
    set beresp.http.X-BE-Name = beresp.backend.name;
    set beresp.http.X-Purge-URL = bereq.url;
    set beresp.http.X-Purge-Host = bereq.http.host;

    # Enabling cache for static files
    if (bereq.url ~ "^[^?]*\.(7z|avi|bmp|bz2|css|csv|doc|docx|eot|flac|flv|gif|gz|ico|jpeg|jpg|js|less|mka|mkv|mov|mp3|mp4|mpeg|mpg|odt|otf|ogg|ogm|opus|pdf|png|ppt|pptx|rar|rtf|svg|svgz|swf|tar|tbz|tgz|tiff|ttf|txt|txz|wav|webm|webp|woff|woff2|xls|xlsx|xml|xz|zip)(\?.*)?$") {
        unset beresp.http.set-cookie;
    }

    # Varnish 4 fully supports Streaming, so use streaming here to avoid locking.
    if (bereq.url ~ "^[^?]*\.(7z|avi|bz2|flac|flv|gz|gzip|mka|mkv|mov|mp3|mp4|mpeg|mpg|ogg|ogm|opus|rar|tar|tgz|tbz|txz|wav|webm|xz|zip)(\?.*)?$") {
      unset beresp.http.set-cookie;
      set beresp.do_stream = true;  # Check memory usage it'll grow in fetch_chunksize blocks (128k by default) if the backend doesn't send a Content-Length header, so only enable it for big objects
    }

    # A response is considered cacheable if it is valid (see above), the
    # HTTP status code is 200, 203, 300, 301, 302, 404 or 410 and it has a
    # non-zero time-to-live when Expires and Cache-Control headers are taken
    # into account.
    # If there is an Set-Cookie-header sent from the backend, do not cache
    # If Cache-Control or Pragma-Headers diswallow caching, do not cache
    if (beresp.ttl <= 0s || beresp.http.Set-Cookie || beresp.http.Vary == "*" ||
        beresp.http.Cache-Control ~ "(?:private|no-store|no-cache)" ||
        beresp.http.Pragma == "no-cache") {
        set beresp.uncacheable = true;
        return (deliver);
    }

    # The following vcl code will make Varnish serve expired objects.
    # All object will be kept up to n minutes past their expiration time or a fresh object is generated.
    # from http://www.varnish-cache.org/trac/wiki/VCLExampleGrace
    set beresp.grace = 30m;

    return (deliver);
}

#
## Called before a response object (from the cache
## or the web server) is sent to the requesting client.
#
sub vcl_deliver {
    # if we receive a debug header, print some more debugging http-headers.
    # default debug header should be set to the lb system order
    # number - usually the first order number in an cluster
    if (req.http.X-Debug == req.http.X-Debug-ID) {
        # Append new header "X-Cache", if we deliver cached content
        if (obj.hits > 0) {
            set resp.http.X-Cache = "HIT (" + obj.hits + ")";
        } else {
            set resp.http.X-Cache = "MISS";
        }

        # Append the used backend - is a director is used, only the directors name will be appended
        set resp.http.X-BE = req.backend_hint;

        # Append the objects lifetime, if fetched from cache
        set resp.http.X-Lifetime = req.http.X-Lifetime;

        # If a restart has occured, append the number of restarts
        if (req.restarts > 0) {
            set resp.http.X-Restarts = req.restarts;
        }
    } else {
        # Or delete the following headers by default
        unset resp.http.X-Varnish;
        unset resp.http.Via;
        unset resp.http.X-Purge-URL;
        unset resp.http.X-Purge-Host;
        unset resp.http.X-BE-Name;
        unset resp.http.Link;
        unset resp.http.X-Powered-By;
        unset resp.http.Server;

        ### mkvarnish headers
        unset resp.http.X-TYPO3-cHash;
        unset resp.http.X-TYPO3-INTincScripts;
        unset resp.http.X-TYPO3-Sitename;
        unset resp.http.X-Cache-Tags;
    }

    return (deliver);
}

#
## Error handling
sub vcl_backend_error {
    # generate an error-page
    set beresp.http.Content-Type = "text/html; charset=utf-8";
    set beresp.http.Retry-After = "5";
    synthetic ({"
        <?xml version="1.0" encoding="utf-8"?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html>
            <head>
                <title>Error</title>
            </head>
            <body>
                <p>Error "} + beresp.status  + " " + beresp.reason + {"</p>
            </body>
        </html>
    "});
    return (deliver);
}

# /*
#  * We can come here "invisibly" with the following errors: 413, 417 & 503
#  */
sub vcl_synth {
    if (resp.status == 750) {
      set resp.http.location = resp.reason;
      set resp.status = 301;
    } else {
      # generate an error-page
      set resp.http.Content-Type = "text/html; charset=utf-8";
      set resp.http.Retry-After = "5";
      synthetic ({"
         <?xml version="1.0" encoding="utf-8"?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html>
            <head>
                <title>Error</title>
            </head>
            <body>
                <p>Error "} + resp.status  + " " + resp.reason + {"</p>
            </body>
        </html>
      "});
    }
    return (deliver);
}
