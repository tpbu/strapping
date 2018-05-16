<?php

function efAddSkinStyles(OutputPage &$out, Skin &$skin) {
    if(!$skin->getUser()->isLoggedIn()) {
        if ($skin->getSkinName() == 'strapping') {
            $out->addInlineStyle('#logout { display:none; }');
        }
    } else {
        if ($skin->getSkinName() == 'strapping') {
            $out->addInlineStyle('#login, li#p-personaltools.dropdown { display:none; }');
        }
    }

    return true;
}
$wgHooks['BeforePageDisplay'][] = 'efAddSkinStyles';

	$wgExtensionCredits['Extension'][] = array(
	'path' => __FILE__,
	'name' => 'StrappingExtension',
	'license-name' => 'MIT',
	'author' => array( 'Lee Miller' ),
	'url' => '//www.MediawikiBootstrap.co',
	'descriptionmsg' => 'Hides and displays items dependent on login state',
	'version' => '1.0.6 21:02 20th January 2018',

);