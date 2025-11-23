<?php

/**
 * The configuration of SimpleSAMLphp
 */

$httpUtils = new \SimpleSAML\Utils\HTTP();

$config = [

    'baseurlpath' => 'https://CONFIGURE-ME.sabanciuniv.edu/', // CONFIGURE-ME: Full path of the simplesamlphp, 
    'enable.saml20-sp' => true,

    'cachedir' => '.', // CONFIGURE-ME
    //'cachedir' => '/var/cache/simplesamlphp',
    //'loggingdir' => '/var/log/',
    //'datadir' => '/var/data/',
    //'tempdir' => '/tmp/simplesamlphp',
    // 'certdir' => 'cert2/',

    'timezone' => null,



    /**********************************
     | SECURITY CONFIGURATION OPTIONS |
     **********************************/

    /*
     * This is a secret salt used by SimpleSAMLphp when it needs to generate a secure hash
     * of a value. It must be changed from its default value to a secret value. The value of
     * 'secretsalt' can be any valid string of any length.
     *
     * A possible way to generate a random salt is by running the following command from a unix shell:
     * LC_ALL=C tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
     */
    'secretsalt' => 'defaultsecretsalt', // CONFIGURE-ME in config-local.php file


    /*
     * Set this option to true if you want to require administrator password to access the metadata.
     */
    'admin.protectmetadata' => true,
    'admin.checkforupdates' => false,

    'trusted.url.domains' => [],
    'trusted.url.regex' => false,
    'enable.http_post' => false,

    /*
     * Set the allowed clock skew between encrypting/decrypting assertions
     *
     * If you have a server that is constantly out of sync, this option
     * allows you to adjust the allowed clock-skew.
     *
     * Allowed range: 180 - 300
     * Defaults to 180.
     */
    'assertion.allowed_clock_skew' => 180,

    /*
     * Set custom security headers. The defaults can be found in \SimpleSAML\Configuration::DEFAULT_SECURITY_HEADERS
     *
     * NOTE: When a header is already set on the response we will NOT overrule it and leave it untouched.
     *
     * Whenever you change any of these headers, make sure to validate your config by running your
     * hostname through a security-test like https://en.internet.nl
    'headers.security' => [
        'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'; object-src 'none'; script-src 'self'; style-src 'self'; font-src 'self'; connect-src 'self'; img-src 'self' data:; base-uri 'none'",
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'origin-when-cross-origin',
    ],
     */


    /************************
     | ERRORS AND DEBUGGING |
     ************************/

    /*
     * The 'debug' option allows you to control how SimpleSAMLphp behaves in certain
     * situations where further action may be taken
     *
     * It can be left unset, in which case, debugging is switched off for all actions.
     * If set, it MUST be an array containing the actions that you want to enable, or
     * alternatively a hashed array where the keys are the actions and their
     * corresponding values are booleans enabling or disabling each particular action.
     *
     * SimpleSAMLphp provides some pre-defined actions, though modules could add new
     * actions here. Refer to the documentation of every module to learn if they
     * allow you to set any more debugging actions.
     *
     * The pre-defined actions are:
     *
     * - 'saml': this action controls the logging of SAML messages exchanged with other
     * entities. When enabled ('saml' is present in this option, or set to true), all
     * SAML messages will be logged, including plaintext versions of encrypted
     * messages.
     *
     * - 'backtraces': this action controls the logging of error backtraces so you
     * can debug any possible errors happening in SimpleSAMLphp.
     *
     * - 'validatexml': this action allows you to validate SAML documents against all
     * the relevant XML schemas. SAML 1.1 messages or SAML metadata parsed with
     * the XML to SimpleSAMLphp metadata converter or the metaedit module will
     * validate the SAML documents if this option is enabled.
     *
     * If you want to disable debugging completely, unset this option or set it to an
     * empty array.
     */
    'debug' => [
        'saml' => true,
        'backtraces' => true,
        'validatexml' => false,
    ],

    /*
     * When 'showerrors' is enabled, all error messages and stack traces will be output
     * to the browser.
     *
     * When 'errorreporting' is enabled, a form will be presented for the user to report
     * the error to 'technicalcontact_email'.
     */
    'showerrors' => false,
    'errorreporting' => false,

    /*
     * Custom error show function called from SimpleSAML\Error\Error::show.
     * See docs/simplesamlphp-errorhandling.md for function code example.
     *
     * Example:
     *   'errors.show_function' => ['SimpleSAML\Module\example\Error', 'show'],
     */


    /**************************
     | LOGGING AND STATISTICS |
     **************************/

    /*
     * Define the minimum log level to log. Available levels:
     * - SimpleSAML\Logger::ERR     No statistics, only errors
     * - SimpleSAML\Logger::WARNING No statistics, only warnings/errors
     * - SimpleSAML\Logger::NOTICE  Statistics and errors
     * - SimpleSAML\Logger::INFO    Verbose logs
     * - SimpleSAML\Logger::DEBUG   Full debug logs - not recommended for production
     *
     * Choose logging handler.
     *
     * Options: [syslog,file,errorlog,stderr]
     *
     * If you set the handler to 'file', the directory specified in loggingdir above
     * must exist and be writable for SimpleSAMLphp. If set to something else, set
     * loggingdir above to 'null'.
     */
    'logging.level' => SimpleSAML\Logger::NOTICE,
    'logging.handler' => 'syslog',

    /*
     * Specify the format of the logs. Its use varies depending on the log handler used (for instance, you cannot
     * control here how dates are displayed when using the syslog or errorlog handlers), but in general the options
     * are:
     *
     * - %date{<format>}: the date and time, with its format specified inside the brackets. See the PHP documentation
     *   of the date() function for more information on the format. If the brackets are omitted, the standard
     *   format is applied. This can be useful if you just want to control the placement of the date, but don't care
     *   about the format.
     *
     * - %process: the name of the SimpleSAMLphp process. Remember you can configure this in the 'logging.processname'
     *   option below.
     *
     * - %level: the log level (name or number depending on the handler used).
     *
     * - %stat: if the log entry is intended for statistical purposes, it will print the string 'STAT ' (bear in mind
     *   the trailing space).
     *
     * - %trackid: the track ID, an identifier that allows you to track a single session.
     *
     * - %srcip: the IP address of the client. If you are behind a proxy, make sure to modify the
     *   $_SERVER['REMOTE_ADDR'] variable on your code accordingly to the X-Forwarded-For header.
     *
     * - %msg: the message to be logged.
     *
     */
    //'logging.format' => '%date{M j H:i:s} %process %level %stat[%trackid] %msg',

    /*
     * Choose which facility should be used when logging with syslog.
     *
     * These can be used for filtering the syslog output from SimpleSAMLphp into its
     * own file by configuring the syslog daemon.
     *
     * See the documentation for openlog (http://php.net/manual/en/function.openlog.php) for available
     * facilities. Note that only LOG_USER is valid on windows.
     *
     * The default is to use LOG_LOCAL5 if available, and fall back to LOG_USER if not.
     */
    'logging.facility' => defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER,

    /*
     * The process name that should be used when logging to syslog.
     * The value is also written out by the other logging handlers.
     */
    'logging.processname' => 'simplesamlphp',

    /*
     * Logging: file - Logfilename in the loggingdir from above.
     */
    'logging.logfile' => 'simplesamlphp.log',

    /*
     * This is an array of outputs. Each output has at least a 'class' option, which
     * selects the output.
     */
    'statistics.out' => [
        // Log statistics to the normal log.
        /*
        [
            'class' => 'core:Log',
            'level' => 'notice',
        ],
        */
        // Log statistics to files in a directory. One file per day.
        /*
        [
            'class' => 'core:File',
            'directory' => '/var/log/stats',
        ],
        */
    ],



    /***********************
     | PROXY CONFIGURATION |
     ***********************/

    /*
     * Proxy to use for retrieving URLs.
     *
     * Example:
     *   'proxy' => 'http://proxy.example.com:5100'
     */
    'proxy' => null,

    /*
     * Username/password authentication to proxy (Proxy-Authorization: Basic)
     * Example:
     *   'proxy.auth' = 'myuser:password'
     */
    //'proxy.auth' => 'myuser:password',



    /**************************
     | DATABASE CONFIGURATION |
     **************************/

    /*
     * This database configuration is optional. If you are not using
     * core functionality or modules that require a database, you can
     * skip this configuration.
     */

    /*
     * Database connection string.
     * Ensure that you have the required PDO database driver installed
     * for your connection string.
     */
    'database.dsn' => 'mysql:host=localhost;dbname=saml',

    /*
     * SQL database credentials
     */
    'database.username' => 'simplesamlphp',
    'database.password' => 'secret',
    'database.options' => [],

    /*
     * (Optional) Table prefix
     */
    'database.prefix' => '',

    /*
     * (Optional) Driver options
     */
    'database.driver_options' => [],

    /*
     * True or false if you would like a persistent database connection
     */
    'database.persistent' => false,

    /*
     * Database secondary configuration is optional as well. If you are only
     * running a single database server, leave this blank. If you have
     * a primary/secondary configuration, you can define as many secondary servers
     * as you want here. Secondaries will be picked at random to be queried from.
     *
     * Configuration options in the secondary array are exactly the same as the
     * options for the primary (shown above) with the exception of the table
     * prefix and driver options.
     */
    'database.secondaries' => [
        /*
        [
            'dsn' => 'mysql:host=mysecondary;dbname=saml',
            'username' => 'simplesamlphp',
            'password' => 'secret',
            'persistent' => false,
        ],
        */
    ],



    /*************
     | PROTOCOLS |
     *************/

    /*
     * Which functionality in SimpleSAMLphp do you want to enable. Normally you would enable only
     * one of the functionalities below, but in some cases you could run multiple functionalities.
     * In example when you are setting up a federation bridge.
     */
    'enable.saml20-idp' => false,
    'enable.adfs-idp' => false,



    /***********
     | MODULES |
     ***********/

    /*
     * Configuration for enabling/disabling modules. By default the 'core', 'admin' and 'saml' modules are enabled.
     *
     * Example:
     *
     * 'module.enable' => [
     *     'exampleauth' => true, // Setting to TRUE enables.
     *     'consent' => false, // Setting to FALSE disables.
     *     'core' => null, // Unset or NULL uses default.
     * ],
     */

    'module.enable' => [
        'core' => true,
        'saml' => true,
    ],


    /*************************
     | SESSION CONFIGURATION |
     *************************/

    /*
     * This value is the duration of the session in seconds. Make sure that the time duration of
     * cookies both at the SP and the IdP exceeds this duration.
     */
    'session.duration' => 8 * (60 * 60), // 8 hours.

    /*
     * Sets the duration, in seconds, data should be stored in the datastore. As the data store is used for
     * login and logout requests, this option will control the maximum time these operations can take.
     * The default is 4 hours (4*60*60) seconds, which should be more than enough for these operations.
     */
    'session.datastore.timeout' => (4 * 60 * 60), // 4 hours

    /*
     * Sets the duration, in seconds, auth state should be stored.
     */
    'session.state.timeout' => (60 * 60), // 1 hour

    /*
     * Option to override the default settings for the session cookie name
     */
    'session.cookie.name' => 'SimpleSAMLSessionID',

    /*
     * Expiration time for the session cookie, in seconds.
     *
     * Defaults to 0, which means that the cookie expires when the browser is closed.
     *
     * Example:
     *  'session.cookie.lifetime' => 30*60,
     */
    'session.cookie.lifetime' => 0,

    /*
     * Limit the path of the cookies.
     *
     * Can be used to limit the path of the cookies to a specific subdirectory.
     *
     * Example:
     *  'session.cookie.path' => '/simplesaml/',
     */
    'session.cookie.path' => '/',

    /*
     * Cookie domain.
     *
     * Can be used to make the session cookie available to several domains.
     *
     * Example:
     *  'session.cookie.domain' => '.example.org',
     */
    //'session.cookie.domain' => '',

    /*
     * Set the secure flag in the cookie.
     *
     * Set this to TRUE if the user only accesses your service
     * through https. If the user can access the service through
     * both http and https, this must be set to FALSE.
     *
     * If unset, SimpleSAMLphp will try to automatically determine the right value
     */
    //'session.cookie.secure' => true,

    /*
     * Set the SameSite attribute in the cookie.
     *
     * You can set this to the strings 'None', 'Lax', or 'Strict' to support
     * the RFC6265bis SameSite cookie attribute. If set to null, no SameSite
     * attribute will be sent.
     *
     * A value of "None" is required to properly support cross-domain POST
     * requests which are used by different SAML bindings. Because some older
     * browsers do not support this value, the canSetSameSiteNone function
     * can be called to only set it for compatible browsers.
     *
     * You must also set the 'session.cookie.secure' value above to true.
     *
     * Example:
     *  'session.cookie.samesite' => 'None',
     */
    //'session.cookie.samesite' => $httpUtils->canSetSameSiteNone() ? 'None' : null,

    /*
     * Options to override the default settings for php sessions.
     */
    'session.phpsession.cookiename' => 'PRFssoSAML',
    'session.phpsession.savepath' => null,
    'session.phpsession.httponly' => true,

    /*
     * Option to override the default settings for the auth token cookie
     */
    'session.authtoken.cookiename' => 'PRFssoSAMLAuthToken',

    /*
     * Options for remember me feature for IdP sessions. Remember me feature
     * has to be also implemented in authentication source used.
     *
     * Option 'session.cookie.lifetime' should be set to zero (0), i.e. cookie
     * expires on browser session if remember me is not checked.
     *
     * Session duration ('session.duration' option) should be set according to
     * 'session.rememberme.lifetime' option.
     *
     * It's advised to use remember me feature with session checking function
     * defined with 'session.check_function' option.
     */
    'session.rememberme.enable' => false,
    'session.rememberme.checked' => false,
    'session.rememberme.lifetime' => (14 * 86400),

    /*
     * Custom function for session checking called on session init and loading.
     * See docs/simplesamlphp-advancedfeatures.md for function code example.
     *
     * Example:
     *   'session.check_function' => ['\SimpleSAML\Module\example\Util', 'checkSession'],
     */



    /*************************************
     | LANGUAGE AND INTERNATIONALIZATION |
     *************************************/

    /*
     * Languages available, RTL languages, and what language is the default.
     */
    'language.available' => [
        'en'
    ],
    'language.rtl' => ['ar', 'dv', 'fa', 'ur', 'he'],
    'language.default' => 'en',

    /*
     * Options to override the default settings for the language parameter
     */
    'language.parameter.name' => 'language',
    'language.parameter.setcookie' => false,

    /**************
     | APPEARANCE |
     **************/

    /*
     * Which theme directory should be used?
     */
    'theme.use' => 'default',

    /*
     * Set this option to the text you would like to appear at the header of each page. Set to false if you don't want
     * any text to appear in the header.
     */
    'theme.header' => 'SSO Client',

    /**
     * A template controller, if any.
     *
     * Used to intercept certain parts of the template handling, while keeping away unwanted/unexpected hooks. Set
     * the 'theme.controller' configuration option to a class that implements the
     * \SimpleSAML\XHTML\TemplateControllerInterface interface to use it.
     */
    //'theme.controller' => '',

    /*
     * Templating options
     *
     * By default, twig templates are not cached. To turn on template caching:
     * Set 'template.cache' to an absolute path pointing to a directory that
     * SimpleSAMLphp has read and write permissions to.
     */
    //'template.cache' => '',

    /*
     * Set the 'template.auto_reload' to true if you would like SimpleSAMLphp to
     * recompile the templates (when using the template cache) if the templates
     * change. If you don't want to check the source templates for every request,
     * set it to false.
     */
    'template.auto_reload' => false,


    /*
     * Set the 'template.debug' to true to enable debugging for Twig templates.
     * This is useful during development as it provides better error messages.
     * Defaults to false.
     */
    //'template.debug' => false,

    /*
     * Set this option to true to indicate that your installation of SimpleSAMLphp
     * is running in a production environment. This will affect the way resources
     * are used, offering an optimized version when running in production, and an
     * easy-to-debug one when not. Set it to false when you are testing or
     * developing the software, in which case a banner will be displayed to remind
     * users that they're dealing with a non-production instance.
     *
     * Defaults to true.
     */
    'production' => true,

    /*
     * SimpleSAMLphp modules can host static resources which are served through PHP.
     * The serving of the resources can be configured through these settings.
     */
    'assets' => [
        /*
         * These settings adjust the caching headers that are sent
         * when serving static resources.
         */
        'caching' => [
            /*
             * Amount of seconds before the resource should be fetched again
             */
            'max_age' => 86400,
            /*
             * Calculate a checksum of every file and send it to the browser
             * This allows the browser to avoid downloading assets again in situations
             * where the Last-Modified header cannot be trusted,
             * for example in cluster setups
             *
             * Defaults false
             */
            'etag' => false,
        ],
    ],

    /**
     * Set to a full URL if you want to redirect users that land on SimpleSAMLphp's
     * front page to somewhere more useful. If left unset, a basic welcome message
     * is shown.
     */
    //'frontpage.redirect' => 'https://example.com/',


    /*************************************
     | AUTHENTICATION PROCESSING FILTERS |
     *************************************/

    /*
     * Authentication processing filters that will be executed for all IdPs
     */
    'authproc.idp' => [
        /* Enable the authproc filter below to add URN prefixes to all attributes
        10 => [
            'class' => 'core:AttributeMap', 'addurnprefix'
        ],
        */
        /* Enable the authproc filter below to automatically generated eduPersonTargetedID.
        20 => 'core:TargetedID',
        */

        // Adopts language from attribute to use in UI
        30 => 'core:LanguageAdaptor',

        /* When called without parameters, it will fallback to filter attributes 'the old way'
         * by checking the 'attributes' parameter in metadata on IdP hosted and SP remote.
         */
        50 => 'core:AttributeLimit',

        /*
         * Search attribute "distinguishedName" for pattern and replaces if found
         */
        /*
        60 => [
            'class' => 'core:AttributeAlter',
            'pattern' => '/OU=studerende/',
            'replacement' => 'Student',
            'subject' => 'distinguishedName',
            '%replace',
        ],
        */

        /*
         * Consent module is enabled (with no permanent storage, using cookies).
         */
        /*
        90 => [
            'class' => 'consent:Consent',
            'store' => 'consent:Cookie',
            'focus' => 'yes',
            'checked' => true
        ],
        */
        // If language is set in Consent module it will be added as an attribute.
        99 => 'core:LanguageAdaptor',
    ],

    /*
     * Authentication processing filters that will be executed for all SPs
     */
    'authproc.sp' => [
        /*
        10 => [
            'class' => 'core:AttributeMap', 'removeurnprefix'
        ],
        */

        50 => [
            'class' => 'core:AttributeMap',
            'entra2name',
            'user.prefFirstName'=>'prefFirstName',
            'user.prefLastName'=>'prefLastName',
        ],


        /*
         * Generate the 'group' attribute populated from other variables, including eduPersonAffiliation.
        60 => [
            'class' => 'core:GenerateGroups', 'eduPersonAffiliation'
        ],
        */
        /*
         * All users will be members of 'users' and 'members'
         */
        /*
        61 => [
            'class' => 'core:AttributeAdd', 'groups' => ['users', 'members']
        ],
        */

        // Adopts language from attribute to use in UI
        90 => 'core:LanguageAdaptor',
    ],



    /**************************
     | METADATA CONFIGURATION |
     **************************/

    /*
     * This option allows you to specify a directory for your metadata outside of the standard metadata directory
     * included in the standard distribution of the software.
     */
    'metadatadir' => 'metadata',

    /*
     * This option configures the metadata sources. The metadata sources is given as an array with
     * different metadata sources. When searching for metadata, SimpleSAMLphp will search through
     * the array from start to end.
     *
     * Each element in the array is an associative array which configures the metadata source.
     * The type of the metadata source is given by the 'type' element. For each type we have
     * different configuration options.
     *
     * Flat file metadata handler:
     * - 'type': This is always 'flatfile'.
     * - 'directory': The directory we will load the metadata files from. The default value for
     *                this option is the value of the 'metadatadir' configuration option, or
     *                'metadata/' if that option is unset.
     *
     * XML metadata handler:
     * This metadata handler parses an XML file with either an EntityDescriptor element or an
     * EntitiesDescriptor element. The XML file may be stored locally, or (for debugging) on a remote
     * web server.
     * The XML metadata handler defines the following options:
     * - 'type': This is always 'xml'.
     * - 'file': Path to the XML file with the metadata.
     * - 'url': The URL to fetch metadata from. THIS IS ONLY FOR DEBUGGING - THERE IS NO CACHING OF THE RESPONSE.
     *
     * MDQ metadata handler:
     * This metadata handler looks up for the metadata of an entity at the given MDQ server.
     * The MDQ metadata handler defines the following options:
     * - 'type': This is always 'mdq'.
     * - 'server': Base URL of the MDQ server. Mandatory.
     * - 'validateCertificate': The certificates file that may be used to sign the metadata. You don't need this
     *                          option if you don't want to validate the signature on the metadata. Optional.
     * - 'cachedir': Directory where metadata can be cached. Optional.
     * - 'cachelength': Maximum time metadata can be cached, in seconds. Defaults to 24
     *                  hours (86400 seconds). Optional.
     *
     * PDO metadata handler:
     * This metadata handler looks up metadata of an entity stored in a database.
     *
     * Note: If you are using the PDO metadata handler, you must configure the database
     * options in this configuration file.
     *
     * The PDO metadata handler defines the following options:
     * - 'type': This is always 'pdo'.
     *
     * Examples:
     *
     * This example defines two flatfile sources. One is the default metadata directory, the other
     * is a metadata directory with auto-generated metadata files.
     *
     * 'metadata.sources' => [
     *     ['type' => 'flatfile'],
     *     ['type' => 'flatfile', 'directory' => 'metadata-generated'],
     * ],
     *
     * This example defines a flatfile source and an XML source.
     * 'metadata.sources' => [
     *     ['type' => 'flatfile'],
     *     ['type' => 'xml', 'file' => 'idp.example.org-idpMeta.xml'],
     * ],
     *
     * This example defines a remote xml-file with optional connection context.
     * See PHP documentation for possible context options: https://www.php.net/manual/en/context.php
     *
     * 'metadata.sources' => [
     *     [
     *         'type' => 'xml',
     *         'url' => 'https://example.org/idp.example.org-idpMeta.xml',
     *         'context' => [
     *             'ssl' => [
     *                 'verify_peer' => true,
     *             ],
     *         ],
     *     ],
     * ],
     *
     * This example defines an mdq source.
     * 'metadata.sources' => [
     *      [
     *          'type' => 'mdq',
     *          'server' => 'http://mdq.server.com:8080',
     *          'validateCertificate' => [
     *              '/var/simplesamlphp/cert/metadata-key.new.crt',
     *              '/var/simplesamlphp/cert/metadata-key.old.crt'
     *          ],
     *          'cachedir' => '/var/simplesamlphp/mdq-cache',
     *          'cachelength' => 86400
     *      ]
     * ],
     *
     * This example defines an pdo source.
     * 'metadata.sources' => [
     *     ['type' => 'pdo']
     * ],
     *
     * Default:
     * 'metadata.sources' => [
     *     ['type' => 'flatfile']
     * ],
     */
    'metadata.sources' => [
        ['type' => 'flatfile'],
    ],

    /*
     * Should signing of generated metadata be enabled by default.
     *
     * Metadata signing can also be enabled for a individual SP or IdP by setting the
     * same option in the metadata for the SP or IdP.
     */
    'metadata.sign.enable' => false,

    /*
     * The default key & certificate which should be used to sign generated metadata. These
     * are files stored in the cert dir.
     * These values can be overridden by the options with the same names in the SP or
     * IdP metadata.
     *
     * If these aren't specified here or in the metadata for the SP or IdP, then
     * the 'certificate' and 'privatekey' option in the metadata will be used.
     * if those aren't set, signing of metadata will fail.
     */
    'metadata.sign.privatekey' => null,
    'metadata.sign.privatekey_pass' => null,
    'metadata.sign.certificate' => null,
    'metadata.sign.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',


    /****************************
     | DATA STORE CONFIGURATION |
     ****************************/

    /*
     * Configure the data store for SimpleSAMLphp.
     *
     * - 'phpsession': Limited datastore, which uses the PHP session.
     * - 'memcache': Key-value datastore, based on memcache.
     * - 'sql': SQL datastore, using PDO.
     * - 'redis': Key-value datastore, based on redis.
     *
     * The default datastore is 'phpsession'.
     */
    'store.type'                    => 'phpsession',

    /*
     * The DSN the sql datastore should connect to.
     *
     * See http://www.php.net/manual/en/pdo.drivers.php for the various
     * syntaxes.
     */
    'store.sql.dsn'                 => 'sqlite:/path/to/sqlitedatabase.sq3',

    /*
     * The username and password to use when connecting to the database.
     */
    'store.sql.username' => null,
    'store.sql.password' => null,

    /*
     * The prefix we should use on our tables.
     */
    'store.sql.prefix' => 'SimpleSAMLphp',

    /*
     * The driver-options we should pass to the PDO-constructor.
     */
    'store.sql.options' => [],

    /*
     * The hostname and port of the Redis datastore instance.
     */
    'store.redis.host' => 'localhost',
    'store.redis.port' => 6379,

    /*
     * The credentials to use when connecting to Redis.
     *
     * If your Redis server is using the legacy password protection (config
     * directive "requirepass" in redis.conf) then you should only provide
     * a password.
     *
     * If your Redis server is using ACL's (which are recommended as of
     * Redis 6+) then you should provide both a username and a password.
     * See https://redis.io/docs/manual/security/acl/
     */
    'store.redis.username' => '',
    'store.redis.password' => '',

    /*
     * Communicate with Redis over a secure connection instead of plain TCP.
     *
     * This setting affects both single host connections as
     * well as Sentinel mode.
     */
    'store.redis.tls' => false,

    /*
     * Verify the Redis server certificate.
     */
    'store.redis.insecure' => false,

    /*
     * Files related to secure communication with Redis.
     *
     * Files are searched in the 'certdir' when using relative paths.
     */
    'store.redis.ca_certificate' => null,
    'store.redis.certificate' => null,
    'store.redis.privatekey' => null,

    /*
     * The prefix we should use on our Redis datastore.
     */
    'store.redis.prefix' => 'SimpleSAMLphp',

    /*
     * The master group to use for Redis Sentinel.
     */
    'store.redis.mastergroup' => 'mymaster',

    /*
     * The Redis Sentinel hosts.
     * Example:
     * 'store.redis.sentinels' => [
     *     'tcp://[yoursentinel1]:[port]',
     *     'tcp://[yoursentinel2]:[port]',
     *     'tcp://[yoursentinel3]:[port]
     * ],
     *
     * Use 'tls' instead of 'tcp' in order to make use of the additional
     * TLS settings.
     */
    'store.redis.sentinels' => [],

    /*********************
     | IdP/SP PROXY MODE |
     *********************/

    /*
     * If the IdP in front of SimpleSAMLphp in IdP/SP proxy mode sends
     * AuthnContextClassRef, decide whether the AuthnContextClassRef will be
     * processed by the IdP/SP proxy or if it will be passed to the SP behind
     * the IdP/SP proxy.
     */
    'proxymode.passAuthnContextClassRef' => false,
];

if(file_exists(dirname(__FILE__).'/config-local.php')){
	include(dirname(__FILE__).'/config-local.php');
}