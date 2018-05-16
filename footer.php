</div>
<div id="footer">
    <div class="row">
	<p class="text-center" style="color:#236b9b;">&copy; 2014 - 2018 <a href="https://mediawikibootstrap.org/">Lee Miller</a></p>
    </div>
	</div>
<footer class="footer-bottom">
  <div class="row">
  <div class="col-md-12">
    <div class="col-md-3">
      <p class="footer-title"><i class="fas fa-book"></i> Latest Docs</p>
        <ul class="menu vertical footer-links">
		    <?php
    	$rss = new DOMDocument();
    	$rss->load('http://mediawikibootstrap.org/index.php?title=Category_Documents&action=feed&feed=rss');
    	$feed = array();
    	foreach ($rss->getElementsByTagName('item') as $node) {
    		$item = array ( 
    			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
    			'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
    			);
    		array_push($feed, $item);
    	}
    	$limit = 5;
    	for($x=0;$x<$limit;$x++) {
    		$title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
    		$link = $feed[$x]['link'];
    		echo '<p><a href="'.$link.'" title="'.$title.'">'.$title.'</a><br />';
    	}
    ?>
      </ul> 
    </div>
    <div class="col-md-3">
      <p class="footer-title"><i class="fas fa-book"></i> Latest News</p>
	  <ul class="menu vertical footer-links">
		    <?php
    	$rss = new DOMDocument();
    	$rss->load('http://mediawikibootstrap.org/index.php?title=Latest_News&action=feed&feed=rss');
    	$feed = array();
    	foreach ($rss->getElementsByTagName('item') as $node) {
    		$item = array ( 
    			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
    			'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
    			);
    		array_push($feed, $item);
    	}
    	$limit = 5;
    	for($x=0;$x<$limit;$x++) {
    		$title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
    		$link = $feed[$x]['link'];
    		echo '<p><a href="'.$link.'" title="'.$title.'">'.$title.'</a><br />';
    	}
    ?>        
      </ul>
    </div>
    <div class="col-md-3">
      <p class="footer-title"><i class="fas fa-book"></i> Help Pages</p>
	  <ul class="menu vertical footer-links">
       <?php
    	$rss = new DOMDocument();
    	$rss->load('http://mediawikibootstrap.org/index.php?title=Help_Pages&action=feed&feed=rss');
    	$feed = array();
    	foreach ($rss->getElementsByTagName('item') as $node) {
    		$item = array ( 
    			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
    			'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
    			);
    		array_push($feed, $item);
    	}
    	$limit = 5;
    	for($x=0;$x<$limit;$x++) {
    		$title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
    		$link = $feed[$x]['link'];
    		echo '<p><a href="'.$link.'" title="'.$title.'">'.$title.'</a><br />';
    	}
    ?> 
      </ul>
    </div>
    <div class="col-md-3">
      <p class="footer-title"><i class="fas fa-book"></i> Stand Alone</p>
	  <ul class="menu vertical footer-links">
        <?php
    	$rss = new DOMDocument();
    	$rss->load('http://mediawikibootstrap.org/index.php?title=Stand_Alone&action=feed&feed=rss');
    	$feed = array();
    	foreach ($rss->getElementsByTagName('item') as $node) {
    		$item = array ( 
    			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
    			'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
    			);
    		array_push($feed, $item);
    	}
    	$limit = 3;
    	for($x=0;$x<$limit;$x++) {
    		$title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
    		$link = $feed[$x]['link'];
    		echo '<p><a href="'.$link.'" title="'.$title.'">'.$title.'</a><br />';
    	}
    ?> 
        </ul>	  
    </div>
</div>
</div>
</footer>