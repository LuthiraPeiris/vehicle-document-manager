<?php

/*
|--------------------------------------------------------------------------
| Determine Current Page
|--------------------------------------------------------------------------
*/

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$requestPath = trim($requestPath, '/');

/*
|--------------------------------------------------------------------------
| Remove project folder from URL
|--------------------------------------------------------------------------
*/

$projectFolder = 'vehicle-document-manager';

if (str_starts_with($requestPath, $projectFolder)) {
    $requestPath = substr(
        $requestPath,
        strlen($projectFolder)
    );
}

$requestPath = trim($requestPath, '/');


/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$currentSection = 'dashboard';
$pageTitle = 'Dashboard';


/*
|--------------------------------------------------------------------------
| Page Mapping
|--------------------------------------------------------------------------
*/

switch ($requestPath) {

    /* Dashboard */
    case '':
    case 'dashboard.php':

        $currentSection = 'dashboard';
        $pageTitle = 'Dashboard';

        break;


    /* Vehicles */
    case 'vehicles':
    case 'vehicles/':
    case 'vehicles/index.php':

        $currentSection = 'vehicles';
        $pageTitle = 'My Vehicles';

        break;


    case 'vehicles/add.php':

        $currentSection = 'vehicles';
        $pageTitle = 'Add Vehicle';

        break;


    case 'vehicles/view.php':

        $currentSection = 'vehicles';
        $pageTitle = 'Vehicle Details';

        break;


    /* Documents */
    case 'documents':
    case 'documents/':
    case 'documents/index.php':

        $currentSection = 'documents';
        $pageTitle = 'Documents';

        break;


    case 'documents/add.php':

        $currentSection = 'documents';
        $pageTitle = 'Add Document';

        break;


    /* Renewals */
    case 'documents/renewals.php':

        $currentSection = 'renewals';
        $pageTitle = 'Renewals';

        break;

    /* Renewal History */
    case 'documents/renewal_history.php':

        $currentSection = 'renewal_history';
        $pageTitle = 'Renewal History';

        break;


    /* Profile */
    case 'profile':
    case 'profile/':
    case 'profile/index.php':

        $currentSection = 'profile';
        $pageTitle = 'Profile';

        break;
}