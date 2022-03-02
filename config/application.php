<?php
/*
 * Application default config values
 * This file should not need to be edited
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
$config = \Bs\Config::getInstance();
include_once(__DIR__ . '/session.php');

/**************************************
 * Default app config values
 **************************************/

$config['site.title'] = 'Base Template';
$config['site.email'] = 'user@example.com';

/*
 * Setup what paths to check when migrating SQL
 */
$config['sql.migrate.list'] = array(
    'Lib Sql' => $config->getVendorPath() . '/uom/tk-base',
    'Plugin Sql' => $config->getPluginPath(),
    'App Sql' => $config->getSrcPath() . '/config'
);


/*
 * The user types available to the system
 */
$config['user.type.list'] = array(
    'Administrator' => 'admin',
    'Member' => 'member'
);

/*
 * Template folders for pages
 */
$config['system.template.path'] = '/html';

$config['system.theme.admin']   = $config['system.template.path'] . '/admin';
$config['system.theme.public']  = $config['system.template.path'] . '/public';

$config['template.admin']       = $config['system.theme.admin'] . '/admin.html';
$config['template.member']      = $config['system.theme.admin'] . '/admin.html';
$config['template.public']      = $config['system.theme.public'] . '/public.html';

/*
 * Set the error page template
 */
$config['template.error']       = dirname($config['template.admin']) . '/error.html';

/*
 * Set the maintenance page template
 */
$config['template.maintenance']       = dirname($config['template.admin']) . '/maintenance.html';

/*
 * This path is where designers can place templates that override the system default templates for Dom\Renderer objects.
 */
$config['template.xtpl.path']   = $config['system.template.path'] . '/app/xtpl';
$config['template.xtpl.ext']    = '.xtpl';

/*
 * Does this html template use bootstrap4 markup
 * Default: 'bs4'
 */
$config['css.framework']         = 'bs4';


/**
 * Set the system timezone
 */
$config['date.timezone'] = 'Australia/Victoria';

/*
 * Enable logging of triggered events
 * Default: false
 */
$config['event.dispatcher.log'] = false;

/*
 * Max size for profile images
 * Default; 1028*1028*2 (2M)
 */
$config['upload.course.imagesize'] = 1028*1028*2;

/*
 * The session log allows us to add the log to exception emails
 * See the log init script in the Bootstrap object
 */
$config['log.session'] = $config->getTempPath().'/session.log';

/*
 * if set to true then all required form fields will render the required="required" attribute
 * currently disabled by default as the errors do not play well with tabs, wizards and fields that are hidden
 * it causes the error popup to float to the top of the screen.
 */
//$config['system.form.required.attr.enabled'] = false;

/*
 * Enable exception emails
 */
//$config['system.email.exception'] = array('user@example.com');

/*
 * Send copies of all system emails to these recipients (not error emails)
 */
//$config['mail.bcc'] = array('user1@example.edu.au');

/**
 * If this is set to true then emails are sent to the logged in users email address
 */
$config['system.debug.email.authUser'] = false;


/*
 * This make the Form renderer add the required attribute to required
 * fields. This can be disabled by using the novalidate attribute on the form
 */
$config['system.form.required.attr.enabled'] = true;

/*  
 * ---- AUTH CONFIG ----
 */

/*
 * Can users create an account on this site
 */
$config['site.client.registration'] = false;

/*
 * Are user created account automatically activated?
 */
$config['site.client.activation'] = true;

/*
 * The hash function to use for passwords and general hashing
 * Warning if you change this after user account creation
 * users will have to reset/recover their passwords
 */
$config['hash.function'] = 'md5';

/*
 * Should the system use a salted password?
 */
$config['system.auth.salted'] = false;

/*
 * Config for the \Tk\Auth\Adapter\DbTable
 */
$config['system.auth.dbtable.tableName'] = 'user';
$config['system.auth.dbtable.usernameColumn'] = 'username';
$config['system.auth.dbtable.passwordColumn'] = 'password';

/*
 * Config for the \Tk\Auth\Adapter\DbTable
 */
$config['system.auth.adapters'] = array(
    'DbTable' => '\Tk\Auth\Adapter\DbTable',
    //'Config' => '\Tk\Auth\Adapter\Config',
    'Trap' => '\Tk\Auth\Adapter\Trapdoor'
    //'LDAP' => '\Tk\Auth\Adapter\Ldap'
);

/*
 * \Tk\Auth\Adapter\Config
 * Note: Do not use this methid in client production sites
 */
//$config['system.auth.username'] = 'admin';
//$config['system.auth.password'] = 'password';


/* **********************************************
 *  Common Dom\Template var name for UI elements
 * **********************************************/

/*
 * where in teh page template to place the controller result string/template
 */
$config['template.var.page.site-title'] = 'site-title';
$config['template.var.page.site-short-title'] = 'site-short-title';
$config['template.var.page.page-header'] = 'page-header';
$config['template.var.page.breadcrumbs'] = 'breadcrumb';
$config['template.var.page.alerts'] = 'alerts';
$config['template.var.page.content'] = 'content';
$config['template.var.page.login'] = 'login';
$config['template.var.page.logout'] = 'logout';

$config['template.var.page.user-name'] = 'user-name';
$config['template.var.page.username'] = 'username';
$config['template.var.page.user-url'] = 'user-url';

// side-nav
$config['template.var.page.side-nav'] = 'side-nav';


/* **********************************************
 *  Common URL for the base controllers
 * **********************************************/

$config['url.auth.home'] = '/index.html';
$config['url.auth.login'] = '/login.html';
$config['url.auth.logout'] = '/logout.html';
$config['url.auth.register'] = '/register.html';
$config['url.auth.recover'] = '/recover.html';
