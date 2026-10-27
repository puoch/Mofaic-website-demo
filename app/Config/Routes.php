<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Only locales listed in App::$supportedLocales are routed; others get a 404.
$routes->useSupportedLocalesOnly(true);

$routes->get('/', static fn () => redirect()->to(site_url(config('App')->defaultLocale)));

$routes->get('health', 'Health::index');

// Staff sign-in. Public registration and magic-link sign-in are switched off:
// staff accounts are created by a Super Administrator.
service('auth')->routes($routes, ['except' => ['register', 'magic-link']]);

// Administration area. Every route needs a signed-in staff account (session
// filter in Config\Filters) and the admin.access permission.
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'permission:admin.access'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Dashboard::index');
    $routes->get('audit', 'Audit::index', ['filter' => 'permission:audit.view']);
    $routes->get('publishing', 'Publishing::index', ['filter' => 'permission:content.view']);

    // Content editing. Each action checks ContentPolicy for the record.
    $routes->group('content', ['filter' => 'permission:content.view'], static function (RouteCollection $routes): void {
        $routes->post('missions/(:num)/coverage', 'Content::addCoverage/$1');
        $routes->post('missions/(:num)/coverage/(:num)/remove', 'Content::removeCoverage/$1/$2');
        $routes->get('(:segment)', 'Content::index/$1');
        $routes->get('(:segment)/new', 'Content::new/$1');
        $routes->post('(:segment)', 'Content::create/$1');
        $routes->get('(:segment)/(:num)', 'Content::edit/$1/$2');
        $routes->post('(:segment)/(:num)', 'Content::update/$1/$2');
        $routes->post('(:segment)/(:num)/publish', 'Content::publish/$1/$2');
        $routes->post('(:segment)/(:num)/unpublish', 'Content::unpublish/$1/$2');
        $routes->post('(:segment)/(:num)/delete', 'Content::delete/$1/$2');
        $routes->get('(:segment)/(:num)/revisions', 'Content::revisions/$1/$2');
        $routes->post('(:segment)/(:num)/revisions/(:num)/restore', 'Content::restore/$1/$2/$3');
        $routes->post('(:segment)/(:num)/media', 'Content::addMedia/$1/$2');
        $routes->post('(:segment)/(:num)/media/(:num)/remove', 'Content::removeMedia/$1/$2/$3');
    });
});

// Public website routes come last: the {locale} placeholder would otherwise
// capture paths such as /login and /admin.
// Public website, prefixed with the language code, for example /en/missions.
$routes->group('{locale}', ['namespace' => 'App\Controllers\Site'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Home::index');

    // MISSIONS (confirmed hierarchy, brief section 2)
    $routes->get('missions', 'Missions::index');
    $routes->get('missions/finder', 'Missions::finder');
    $routes->get('missions/countries', 'Missions::countries');
    $routes->get('missions/countries/(:segment)', 'Missions::country/$1');
    $routes->get('missions/profile/(:segment)', 'Missions::profile/$1');
    $routes->get('missions/(abroad|in-south-sudan)', 'Missions::group/$1');
    $routes->get('missions/(abroad|in-south-sudan)/(:segment)', 'Missions::category/$1/$2');

    // ABOUT MFA&IC, ABOUT SOUTH SUDAN, SERVICES
    foreach (['about-mfaic', 'about-south-sudan', 'services'] as $section) {
        $routes->get($section, "Pages::section/{$section}");
        $routes->get($section . '/(:any)', "Pages::show/{$section}/$1");
    }

    // NEWS / PRESS
    $routes->get('news-press', 'News::index');
    $routes->get('news-press/gallery', 'News::gallery');
    $routes->get('news-press/gallery/(:segment)', 'News::album/$1');
    $routes->get('news-press/social-media', 'News::social');
    $routes->get('news-press/tags/(:segment)', 'News::tag/$1');
    $routes->get('news-press/(news|statements|mission-activities)', 'News::list/$1');
    $routes->get('news-press/(news|statements|mission-activities)/(:segment)', 'News::show/$1/$2');

    // ARCHIVES (navigation placement undecided; see Config\Navigation)
    $routes->get('archives', 'Archives::index');
    $routes->get('archives/(:segment)', 'Archives::show/$1');

    // SSD DIASPORA and CONTACTS are built at Gate 4.
    $routes->get('diaspora', 'Placeholder::section/diaspora');
    $routes->get('contacts', 'Contacts::index');
});
