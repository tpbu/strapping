<?php
/**
 * Initialization file for Bootstrapskin
 *
 * Bootstrapskin is a mod of Strapping which is built on top of a modified Vector theme from
 * MediaWiki and utilizes Bootstrap 3 for base layout, typography, and additional widgets.
 *
 * @file
 * @ingroup Skins
 * @author Garrett LeSage
 * @author Lee Miller
 */

if( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );
 
$wgExtensionCredits['skin'][] = array(
        'path' => __FILE__,
        'name' => 'Bootstrapskin 2',
        'url' => "www.mediawikibootstrap.co",
        'author' => 'Garrett LeSage and Lee Miller',
        'descriptionmsg' => 'strapping-desc',
		'version' => '1.0.3',
);

$wgValidSkinNames['strapping'] = 'Strapping';
$wgAutoloadClasses['SkinStrapping'] = dirname(__FILE__).'/Strapping.skin.php';
$wgExtensionMessagesFiles['SkinStrapping'] = dirname(__FILE__).'/Strapping.i18n.php';
 
$wgResourceModules['skins.strapping'] = array(
        'styles' => array(
                'strapping/bootstrap/css/bootstrap.min.css' => array( 'media' => 'screen' ),
                'strapping/screen.css' => array( 'media' => 'screen' ),
                'strapping/theme.css' => array( 'media' => 'screen' ),
                'strapping/bootstrap/css/lazyYT.css' => array( 'media' => 'screen' ),
                'https://use.fontawesome.com/releases/v5.0.4/css/all.css' => array( 'media' => 'screen' ),		
	),
	'scripts' => array(
		'strapping/bootstrap/js/bootstrap.js',
		'strapping/strapping.js',
        'strapping/bootstrap/js/misc.js',
        'strapping/bootstrap/js/jquery.gritter.min.js',
        'strapping/bootstrap/js/lazyYT.js',	
	),
        'remoteBasePath' => &$GLOBALS['wgStylePath'],
        'localBasePath' => &$GLOBALS['wgStyleDirectory'],
);

if (file_exists('strapping/fonts.css')) {
  $wgResourceModules['skins.strapping']['styles'][] = 'strapping/fonts.css';
}

# Default options to customize skin
$wgStrappingSkinLogoLocation = 'navbar';
$wgStrappingSkinLoginLocation = 'footer';
$wgStrappingSkinAnonNavbar = false;
$wgStrappingSkinUseStandardLayout = false;
$wgStrappingSkinDisplaySidebarNavigation = false;
# Show print/export in navbar by default
$wgStrappingSkinSidebarItemsInNavbar = array( 'coll-print_export' );
