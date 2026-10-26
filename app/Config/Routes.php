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
});

// Public website routes come last: the {locale} placeholder would otherwise
// capture paths such as /login and /admin.
// Public website, prefixed with the language code, for example /en/missions.
$routes->group('{locale}', static function (RouteCollection $routes): void {
    $routes->get('/', 'Pages::home');

    $sections = [
        'missions'                                       => 'missions',
        'missions/abroad'                                => 'missionsAbroad',
        'missions/abroad/embassies'                      => 'abroadEmbassies',
        'missions/abroad/consulates'                     => 'abroadConsulates',
        'missions/abroad/permanent-missions'             => 'abroadPermanentMissions',
        'missions/abroad/non-resident-missions'          => 'abroadNonResidentMissions',
        'missions/in-south-sudan'                        => 'missionsInSouthSudan',
        'missions/in-south-sudan/embassies'              => 'foreignEmbassies',
        'missions/in-south-sudan/international-agencies' => 'foreignInternationalAgencies',
        'about-mfaic'                                    => 'aboutMfaic',
        'about-south-sudan'                              => 'aboutSouthSudan',
        'services'                                       => 'services',
        'news-press'                                     => 'newsPress',
        'diaspora'                                       => 'diaspora',
        'contacts'                                       => 'contacts',
        'archives'                                       => 'archives',
    ];

    foreach ($sections as $path => $navKey) {
        $routes->get($path, "Pages::section/{$navKey}");
    }
});
