<?php
if ( !defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Gallery',
	'author' => array(
		'[https://mediawikibootstrap.org/ Lee Miller]',
	),
	'descriptionmsg' => 'gallery-desc',
	'url' => 'https://mediawikibootstrap.org/',
);

$wgExtensionMessagesFiles['Gallery'] = __DIR__ . '/Gallery.i18n.php';

$wgResourceModules['ext.gallery'] = array(
	'scripts' => array('darkbox.min.js',),
 
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Gallery',
	
);

$wgHooks['BeforePageDisplay'][] = 'addGallery';
 
function addGallery( OutputPage &$out, Skin &$skin ) {
   $out->addModules( 'ext.gallery' );
 
   return true;
}