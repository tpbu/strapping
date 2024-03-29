<?php

class WikiArticleFeeds {
	const VERSION = '0.74.0 20170203';

	function feedStart( $text, $params = array(), Parser $parser ) {
		$parser->addTrackingCategory( 'wikiarticlefeeds-tracking-category' );
		return '<!-- FEED_START -->';
	}

	function feedEnd( $text, $params = array() ) {
		return '<!-- FEED_END -->';
	}

	function burnFeed( $text, $params = array() ) {
		return ( $params['name'] ? '<!-- BURN_FEED ' . base64_encode( serialize( $params['name'] ) ) . ' -->' : '' );
	}

	function itemTagsTag( $text, $params = array() ) {
		return ( $text ? '<!-- ITEM_TAGS ' . base64_encode( serialize( $text ) ) . ' -->' : '' );
	}

	function itemTagsFunction( Parser $parser ) {
		$tags = func_get_args();
		array_shift( $tags );
		return ( !empty( $tags ) ? '<pre>@ITEMTAGS@' . base64_encode( serialize( implode( ',', $tags ) ) ) . '@ITEMTAGS@</pre>' : '' );
	}

	function itemTagsPlaceholderCorrections( Parser $parser, &$text ) {
		$text = preg_replace(
			'|<pre>@ITEMTAGS@([0-9a-zA-Z\\+\\/]+=*)@ITEMTAGS@</pre>|',
			'<!-- ITEM_TAGS $1 -->',
			$text
		);
		return true;
	}

	# Sets up the WikiArticleFeeds Parser hooks
	static function setup( Parser $parser ) {
		global $wgWikiArticleFeeds;

		$parser->setHook( 'startFeed', array( $wgWikiArticleFeeds, 'feedStart' ) );
		$parser->setHook( 'endFeed', array( $wgWikiArticleFeeds, 'feedEnd' ) );
		$parser->setHook( 'feedBurner', array( $wgWikiArticleFeeds, 'burnFeed' ) );
		$parser->setHook( 'itemTags', array( $wgWikiArticleFeeds, 'itemTagsTag' ) );
		$parser->setFunctionHook( 'itemtags', array( $wgWikiArticleFeeds, 'itemTagsFunction' ) );

		return true;
	}

	/**
	 * Adds the Wiki feed link headers.
	 *
	 * @param OutputPage $out Handle to an OutputPage object (presumably $wgOut).
	 * @param string $text Article/Output text.
	 */
	public static function addWikiFeedHeaders( $out, $text ) {
		# Short-circuit if this article contains no feeds
		if ( !preg_match( '/<!-- FEED_START -->/m', $text ) ) {
			return true;
		}

		$rssArr = array(
			'rel' => 'alternate',
			'type' => 'application/rss+xml',
			'title' => $out->getTitle()->getText() . ' - RSS 2.0',
		);
		$atomArr = array(
			'rel' => 'alternate',
			'type' => 'application/atom+xml',
			'title' => $out->getTitle()->getText() . ' - Atom 0.3',
		);

		# Test for feedBurner presence
		if ( preg_match( '/<!-- BURN_FEED ([0-9a-zA-Z\\+\\/]+=*) -->/m', $text, $matches ) ) {
			$name = @unserialize( @base64_decode( $matches[1] ) );
			if ( $name ) {
				$rssArr['href'] = 'http://feeds.feedburner.com/' . urlencode( $name ) . '?format=xml';
				$atomArr['href'] = 'http://feeds.feedburner.com/' . urlencode( $name ) . '?format=xml';
			}
		}

		# If it's not being fed by feedBurner, do it ourselves!
		if ( !array_key_exists( 'href', $rssArr ) || !$rssArr['href'] ) {
			global $wgServer, $wgScript;

			$baseUrl = $wgServer . $wgScript . '?title=' . $out->getTitle()->getDBkey() . '&action=feed&feed=';
			$rssArr['href'] = $baseUrl . 'rss';
			$atomArr['href'] = $baseUrl . 'atom';
		}

		$out->addLink( $rssArr );
		$out->addLink( $atomArr );

		global $wgWikiFeedPresent;
		$wgWikiFeedPresent = true;

		# True to indicate that other action handlers should continue to process this page
		return true;
	}

	/**
	 * Add additional attributes to links to User- or User_talk pages
	 * It does this for all links on all pages
	 * (even when we need this only for pages which generate a feed)
	 *
	 * Attributes are used later in self::generateWikiFeed() to determine signatures with timestamps
	 * for attributing author and timestamp values to the feed item from the signatures.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkEnd
	 */
	public static function addSignatureMarker( $skin, Title $target, array $options, $text, array &$attribs, $ret ) {
		if ( $target->getNamespace() == NS_USER ) {
			$attribs['data-userpage-link'] = 'true';
		} elseif ( $target->getNamespace() == NS_USER_TALK ) {
			$attribs['data-usertalkpage-link'] = 'true';
		}
		return true;
	}

	/**
	 * Adds the Wiki feed links to the bottom of the toolbox in MonoBook or like-minded skins.
	 *
	 * @param BaseTemplate $template Instance of MonoBookTemplate or other BaseTemplate
	 */
	public static function addToolboxLinks( $template ) {
		global $wgServer, $wgScript, $wgWikiFeedPresent;

		# Short-circuit if there are no Feeds present
		if ( !$wgWikiFeedPresent ) {
			return true;
		}

		$title = $template->getSkin()->getTitle();

		if ( $title->getNamespace() < NS_MAIN ) {
			return true;
		}

		$result = '<li id="feedlinks">';

		# Test for feedBurner presence
		$burned = false;
		ob_start();
		$template->html( 'bodytext' );
		$text = ob_get_contents();
		ob_end_clean();
		if ( preg_match( '/<!-- BURN_FEED ([0-9a-zA-Z\\+\\/]+=*) -->/m', $text, $matches ) ) {
			$feedBurnerName = @unserialize( @base64_decode( $matches[1] ) );
			if ( $feedBurnerName ) {
				$feeds = array(
					'rss' => 'RSS',
					'atom' => 'Atom'
				);
				foreach ( $feeds as $feed => $name ) {
					$result .=
					'<span id="feed-' . htmlspecialchars( $feed ) . '">' .
					'<a class="feedlink" href="http://feeds.feedburner.com/' . urlencode( $feedBurnerName ) . '?format=xml">' .
					htmlspecialchars( $name ) . '</a>&#160;</span>';
				}
				$burned = true;
			}
		}

		# Generate regular RSS and Atom feeds if not fed by feedBurner
		if ( !$burned ) {
			$dbKey = $title->getPrefixedDBkey();
			$baseUrl = $wgServer . $wgScript . '?title=' . $dbKey . '&action=feed&feed=';
			$feeds = array(
				'rss' => 'RSS',
				'atom' => 'Atom'
			);
			foreach ( $feeds as $feed => $name ) {
				$result .=
				'<span id="feed-' . htmlspecialchars( $feed ) . '">' .
				'<a class="feedlink" href="' . htmlspecialchars( $baseUrl . $feed ) . '">' .
				htmlspecialchars( $name ) . '</a>&#160;</span>';
			}
		}

		echo $result . '</li>';

		# True to indicate that other action handlers should continue to process this page
		return true;
	}

	/**
	 * Injects handling of the 'feed' action.
	 *
	 * @param string $action Handle to an action string (presumably same as global $action).
	 * @param Article|WikiPage $article Article to be converted to RSS or Atom feed
	 */
	public static function onUnknownAction( $action, $article ) {
		# If some other action is in the works, cut and run!
		if ( $action != 'feed' ) {
			return true;
		}

		global $wgRequest, $wgFeedClasses, $wgFeedCacheTimeout, $wgDBname,
			$messageMemc;

		# Get query parameters
		$feedFormat = $wgRequest->getVal( 'feed', 'atom' );
		$filterTags = $wgRequest->getVal( 'tags', null );

		# Process requested tags for use in keys
		if ( $filterTags ) {
			$filterTags = explode( ',', $filterTags );
			array_walk( $filterTags, 'trim' );
			sort( $filterTags );
		}

		if ( !isset( $wgFeedClasses[$feedFormat] ) ) {
			wfHttpError( 500, 'Internal Server Error', 'Unsupported feed type.' );
			return false;
		}

		# Setup cache-checking vars
		$title = $article->getTitle();
		$titleDBkey = $title->getPrefixedDBkey();
		$tags = ( is_array( $filterTags ) ? ':' . implode( ',', $filterTags ) : '' );
		$key = "{$wgDBname}:wikiarticlefeedsextension:{$titleDBkey}:{$feedFormat}{$tags}";
		$timekey = $key . ':timestamp';
		$cachedFeed = false;
		$feedLastmod = $messageMemc->get( $timekey );

		# Dermine last modification time for either the article itself or an included template
		$lastmod = $article->getTimestamp();
		$templates = $title->getTemplateLinksFrom();
		foreach ( $templates as $tTitle ) {
			$tArticle = new Article( $tTitle );
			$tmod = $tArticle->getTimestamp();
			$lastmod = ( $lastmod > $tmod ? $lastmod : $tmod );
		}

		# Check for availability of existing cached **
		if ( ( $wgFeedCacheTimeout > 0 ) && $feedLastmod ) {
			$feedLastmodTS = wfTimestamp( TS_UNIX, $feedLastmod );
			if (
				time() - $feedLastmodTS < $wgFeedCacheTimeout ||
				$feedLastmodTS > wfTimestamp( TS_UNIX, $lastmod )
			)
			{
				wfDebugLog( 'WikiArticleFeeds', "Loading feed from cache ($key; $feedLastmod; $lastmod)...\n" );
				$cachedFeed = $messageMemc->get( $key );
			} else {
				wfDebugLog( 'WikiArticleFeeds', "Cached feed timestamp check failed ($feedLastmod; $lastmod)\n" );
			}
		}

		# Display cachedFeed, or generate one from scratch
		global $wgWikiArticleFeedsSkipCache;
		if ( !$wgWikiArticleFeedsSkipCache && is_string( $cachedFeed ) ) {
			wfDebugLog( 'WikiArticleFeeds', 'Outputting cached feed' );
			$feed = new $wgFeedClasses[$feedFormat]( $title->getText(), '', $title->getFullURL() . ' - Feed' );
			ob_start();
			$feed->httpHeaders();
			echo $cachedFeed;
		} else {
			wfDebugLog( 'WikiArticleFeeds', 'Rendering new feed and caching it' );
			ob_start();
			WikiArticleFeeds::generateWikiFeed( $article, $feedFormat, $filterTags );
			$cachedFeed = ob_get_contents();
			ob_end_flush();

			$expire = 3600; # One hour
			$messageMemc->set( $key, $cachedFeed );
			$messageMemc->set( $timekey, wfTimestamp( TS_MW ), $expire );
		}

		# False to indicate that other action handlers should not process this page
		return false;
	}

	/**
	 * Purges all associated feeds when an Article is purged.
	 *
	 * @param Article $article The Article which is being purged.
	 * @return bool Always true to permit additional hook processing.
	 */
	public static function onArticlePurge( $article ) {
		global $messageMemc, $wgDBname;
		$titleDBKey = $article->mTitle->getPrefixedDBkey();
		$keyPrefix = "{$wgDBname}:wikiarticlefeedsextension:{$titleDBKey}";
		$messageMemc->delete( "{$keyPrefix}:atom:timestamp" );
		$messageMemc->delete( "{$keyPrefix}:atom" );
		$messageMemc->delete( "{$keyPrefix}:rss" );
		$messageMemc->delete( "{$keyPrefix}:rss:timestamp" );
		return true;
	}

	/**
	 * Converts an MediaWiki article into a feed, echoing generated content directly.
	 *
	 * @param Article $article Article to be converted to RSS or Atom feed.
	 * @param string $feedFormat A format type - must be either 'rss' or 'atom'
	 * @param array $filterTags Tags to use in filtering out items.
	 */
	public static function generateWikiFeed( $article, $feedFormat = 'atom', $filterTags = null ) {
		global $wgOut, $wgServer, $wgFeedClasses, $wgVersion;

		# Setup, handle redirects
		if ( $article->isRedirect() ) {
			$rtitle = $article->getContentObject()->getRedirectTarget();
			if ( $rtitle ) {
				$article = new Article( $rtitle );
			}
		}
		$title = $article->getTitle();
		$feedUrl = $title->getFullURL();

		# Parse page into feed items.
		$content = $wgOut->parse( ContentHandler::getContentText( $article->getPage()->getContent() ) .
			"\n__NOEDITSECTION__ __NOTOC__" );
		preg_match_all(
			'/<!--\\s*FEED_START\\s*-->(.*?)<!--\\s*FEED_END\\s*-->/s',
			$content,
			$matches
		);
		$feedContentSections = $matches[1];

		# Parse and process all feeds, collecting feed items
		$items = array();
		$feedDescription = '';
		foreach ( $feedContentSections as $feedKey => $feedContent ) {
			# Determine Feed item depth (what header level defines a feed)
			preg_match_all( '/<h(\\d)>/m', $feedContent, $matches );
			if ( !isset( $matches[1] ) ) {
				continue;
			}
			$lvl = $matches[1][0];
			foreach ( $matches[1] as $match ) {
				if ( $match < $lvl ) {
					$lvl = $match;
				}
			}

			$sectionRegExp = '#<h' . $lvl . '>\s*<span.+?id="(.*?)">\s*(.*?)\s*</span>\s*</h' . $lvl . '>#m';

			# Determine the item titles and default item links
			preg_match_all(
				$sectionRegExp,
				$feedContent,
				$matches
			);
			$itemLinks = $matches[1];
			$itemTitles = $matches[2];

			# Split content into segments
			$segments = preg_split( $sectionRegExp, $feedContent );
			$segDesc = trim( strip_tags( array_shift( $segments ) ) );
			if ( $segDesc ) {
				if ( !$feedDescription ) {
					$feedDescription = $segDesc;
				} else {
					$feedDescription =
						$article->getContext()->msg( 'wikiarticlefeeds_combined_description' )->text();
				}
			}

			# Loop over parsed segments and add all items to item array
			foreach ( $segments as $key => $seg ) {
				# Filter by tag (if any are present)
				$skip = false;
				$tags = null;
				if ( is_array( $filterTags ) && !empty( $filterTags ) ) {
					if ( preg_match_all( '/<!-- ITEM_TAGS ([0-9a-zA-Z\\+\\/]+=*) -->/m', $seg, $matches ) ) {
						$tags = array();
						foreach ( $matches[1] as $encodedString ) {
							$t = @unserialize( @base64_decode( $encodedString ) );
							if ( $t ) {
								$t = explode( ',', $t );
								array_walk( $t, 'trim' );
								sort( $t );
								$tags = array_merge( $tags, $t );
							}
						}
						$tags = array_unique( $tags );
						if ( !count( array_intersect( $tags, $filterTags ) ) ) {
							$skip = true;
						}
						$seg = preg_replace( '/<!-- ITEM_TAGS ([0-9a-zA-Z\\+\\/]+=*) -->/m', '', $seg );
					} else {
						$skip = true;
					}
				}
				if ( $skip ) {
					continue;
				}

				# Try hard to determine the item author and date
				# Look for a regular signatures of the layout
				# data-userpage-link [optional user_talk page link] date (with a delimiting timezone code in parentheses)

				$author = null;
				$date = null;

				$signatureRegExp = '#<a href=".+?User:.+?" title="User:.+?">(.*?)</a> (\d\d):(\d\d), (\d+) ([a-z]+) (\d{4}) (\([A-Z]+\))#im';

				$signatureRegExp1 = '#<.*data-userpage-link.*>(.*?)</a>.*<.*data-usertalkpage-link.*>.*</a>\) (.*\([A-Z]+\))#im';
				$signatureRegExp2 = '#<.*data-userpage-link.*>(.*?)</a> (.*\([A-Z]+\))#im';
				$signatureRegExp3 = '#<.*data-usertalkpage-link.*>(.*?)</a> (.*\([A-Z]+\))#im';

				$isAttributable = ( preg_match( $signatureRegExp1, $seg, $matches )
					|| preg_match( $signatureRegExp2, $seg, $matches )
					|| preg_match( $signatureRegExp3, $seg, $matches ) );

				if ( $isAttributable ) {
					list( $author, $timestring ) = array_slice( $matches, 1 );

					$tempTimezone = date_default_timezone_get();
					date_default_timezone_set( 'UTC' );
					$date = date( 'YmdHis', strtotime( $timestring ) );
					date_default_timezone_set( $tempTimezone );
				}

				# Set default 'article section' feed-link
				$url = $feedUrl . '#' . $itemLinks[$key];

				# Look for an alternative to the default link (unless default 'section linking' has been forced)
				global $wgForceArticleFeedSectionLinks;
				if ( !$wgForceArticleFeedSectionLinks ) {
					$strippedSeg = preg_replace( $signatureRegExp, '', $seg );
					preg_match(
						'#<a [^>]*href=([\'"])(.*?)\\1[^>]*>(.*?)</a>#m',
						$strippedSeg,
						$matches
					);
					if ( isset( $matches[2] ) ) {
						$url = $matches[2];
						if ( preg_match( '#^/#', $url ) ) {
							$url = $wgServer . $url;
						}
					}
				}

				# Create 'absolutified' segment - where all URLs are fully qualified
				$seg = preg_replace( '/ (href|src)=([\'"])\\//', ' $1=$2' . $wgServer . '/', $seg );

				# Create item and push onto item list
				$items[$date][] = new FeedItem( strip_tags( $itemTitles[$key] ), $seg, $url, $date, $author );
			}
		}

		# Programmatically determine the feed title and ID.
		$feedTitle = $title->getPrefixedText() . ' - Feed';
		$feedId = $title->getFullURL();

		# Create feed
		$feed = new $wgFeedClasses[$feedFormat]( $feedTitle, $feedDescription, $feedId );

		# Push feed header
		$tempWgVersion = $wgVersion;
		$wgVersion .= ' via WikiArticleFeeds ' . WikiArticleFeeds::VERSION;
		$feed->outHeader();
		$wgVersion = $tempWgVersion;

		# Sort all items by date and push onto feed
		krsort( $items );
		foreach ( $items as $itemGroup ) {
			foreach ( $itemGroup as $item ) {
				$feed->outItem( $item );
			}
		}

		# Feed footer
		$feed->outFooter();
	}

} /* class WikiArticleFeeds */
