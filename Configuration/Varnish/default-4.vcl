vcl 4.0;

import std;
import directors;


backend default {
    .host = "web";
    .port = "80";
}

sub vcl_init {
    new cluster1 = directors.round_robin();
    cluster1.add_backend(default);
}


# Enable flushing access only to internals
acl purge {
    "web";
}

sub vcl_recv {
    set req.backend_hint = cluster1.backend();

    # Catch PURGE Command
    if (req.method == "PURGE") {

        if (!client.ip ~ purge) {
            return (synth(405, "Not allowed"));
        }

        if(req.http.X-Varnish-Purge-All == "1" && req.http.X-TYPO3-Sitename) {
            ban("req.url ~ /" + " && obj.http.X-TYPO3-Sitename == " + req.http.X-TYPO3-Sitename);
            return (synth(200, "Banned all on site "+ req.http.X-TYPO3-Sitename)) ;
        } else if(req.http.X-Varnish-Purge-All == "1") {
            ban("req.url ~ /");
            return (synth(200, "Banned all"));
        }

        if(req.http.X-Cache-Tags && req.http.X-TYPO3-Sitename) {
            ban("obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags + " && obj.http.X-TYPO3-Sitename == " + req.http.X-TYPO3-Sitename);
            return (synth(200, "Banned cache tags " + req.http.X-Cache-Tags + " on site " + req.http.X-TYPO3-Sitename));
        } else if(req.http.X-Cache-Tags) {
            ban("obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags);
            return (synth(200, "Banned cache tags " + req.http.X-Cache-Tags)) ;
        }

        return (synth(200, "Banned"));
    }

    # Set a unique cache header with client ip
    if (req.restarts == 0) {
        if (req.http.x-forwarded-for) {
            set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
        }
    }

    # block w00tw00tt and other attacks
    # block backup and private files too
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
        return(synth(403,  "Notpermitted"));
    }

    # Always allow post request to be sent to the backend but not cached
    if (req.method == "POST") {
        #ban("req.url == " + req.url);
        #set req.http.X-Test = req.url;
        return (pass);
    }

    if (req.method != "GET" &&
        req.method != "HEAD" &&
        req.method != "PUT" &&
        req.method != "POST" &&
        req.method != "TRACE" &&
        req.method != "OPTIONS" &&
        req.method != "DELETE") {
          /* Non-RFC2616 or CONNECT which is weird. */
          return (pipe);
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

    # collect all cookies
    std.collect(req.http.Cookie);

    # static files are always cacheable. remove SSL flag and cookie
    if (req.url ~ "\.(ico|css|css\.gzip|js|js\.gzip|jpg|jpeg|png|gif|tiff|bmp|mp3|ogg|svg|swf|woff|woff2|eot|ttf|otf)$") {
        unset req.http.Https;
        unset req.http.Cookie;
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
        # Delete cookie
        unset req.http.Cookie;
    }

    # Handle compression correctly. Different browsers send different
    # "Accept-Encoding" headers, even though they mostly all support the same
    # compression mechanisms.
    if (req.http.Accept-Encoding) {
        if (req.http.Accept-Encoding ~ "gzip") {
            # If the browser supports gzip, that is what we use
            set req.http.Accept-Encoding = "gzip";
        } else if (req.http.Accept-Encoding ~ "deflate") {
            # Next try deflate encoding
            set req.http.Accept-Encoding = "deflate";
        } else {
            # Unknown algorithm. Remove it and send unencoded.
            unset req.http.Accept-Encoding;
        }
    }

    # Lookup in cache
    return (hash);
}

sub vcl_hash {
    hash_data(req.url);

    if (req.http.Host) {
        hash_data(req.http.Host);
    } else {
        hash_data(server.ip);
    }

    return (lookup);
}

sub vcl_backend_response {

    # Set default cache to 24 hours
    set beresp.ttl = 24h;

    # Deliver old content up to 1 day
    set beresp.grace = 24h;

    # Set cache for 3 days
    if (bereq.url ~ "\.(png|gif|jpeg|jpg|ico|swf|css|css\.gzip|js|js\.gzip|pdf|txt)(\?|$)") {
        set beresp.ttl = 72h;
    }

    # Delete cookies if not required (no session data or fe_typo_user)
    if (bereq.method == "POST" || bereq.url ~ "^/typo3" || bereq.url ~ "(\?|&)eID=") {
    } else {
        unset beresp.http.Set-Cookie;
    }

    if (beresp.ttl <= 0s || beresp.http.Set-Cookie || beresp.http.Vary == "*") {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s;
        return (deliver);
    }

    if (beresp.http.content-type ~ "text") {
        set beresp.do_esi = true;
    }

    if (bereq.url ~ "\.(js|js\.gzip)$" || beresp.http.content-type ~ "text") {
        set beresp.do_gzip = true;
    }

    # cache only successfully responses and 404s
    if (beresp.status != 200 && beresp.status != 404) {
        set beresp.ttl = 0s;
        set beresp.uncacheable = true;
        return (deliver);
    } elsif (beresp.http.Cache-Control ~ "private") {
        set beresp.uncacheable = true;
        set beresp.ttl = 86400s;
        return (deliver);
    }

    # validate if we need to cache it and prevent from setting cookie
    # images, css and js are cacheable by default so we have to remove cookie also
    if (beresp.ttl > 0s && (bereq.method == "GET" || bereq.method == "HEAD")) {
        unset beresp.http.set-cookie;
        if (bereq.url !~ "\.(ico|css|css\.gzip|js|css\.gzip|jpg|jpeg|png|gif|tiff|bmp|gz|tgz|bz2|tbz|mp3|ogg|svg|swf|woff|woff2|eot|ttf|otf)(\?|$)") {
            set beresp.http.Pragma = "no-cache";
            set beresp.http.Expires = "-1";
            set beresp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
            set beresp.grace = 1m;
        }
    }

    return (deliver);
}

sub vcl_deliver {

    if ((
        req.http.X-Real-IP ~ "^192\.168\." ||
        client.ip ~ purge
    )) {
        ### we are in the dev ip mask or from purge backend
        # Add additional information for backend server

        set resp.http.X-Varnish-Enabled = "TRUE";
        set resp.http.X-Varnish-Client-IP = client.ip;
        set resp.http.X-Varnish-Server-IP = server.ip;
        set resp.http.X-Varnish-Local-IP = local.ip;
        set resp.http.X-Varnish-Remote-IP = remote.ip;

        if (obj.hits > 0) {
            set resp.http.X-Cache = "Hit (" + obj.hits + ")";
        } else {
            set resp.http.X-Cache = "Miss";
        }
    } else {
        # Remove some significant headers
        unset resp.http.X-Varnish;
        unset resp.http.Via;
        unset resp.http.Age;
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