<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="en">
	<head>
		<meta content="<?php echo $isbn; ?>" name="dtb:uid"/>
		<meta content="2" name="dtb:depth"/>
		<meta content="Spine (0.1)" name="dtb:generator"/>
		<meta content="0" name="dtb:totalPageCount"/>
		<meta content="0" name="dtb:maxPageNumber"/>
	</head>
	<docTitle>
		<text><?php echo $title; ?></text>
	</docTitle>
	<docAuthor>
		<text><?php echo $sort_author; ?></text>
	</docAuthor>

	<navMap>

		<?php
			$order = 0;
			foreach ($contents as $content) {
				$order++;
		?>
			<navPoint class="chapter" id="<?php echo $content->getId(); ?>" playOrder="<?php echo $order; ?>">
				<navLabel>
					<text><?php echo $content->getName(); ?></text>
				</navLabel>
				<content src="Text/<?php echo $content->getFilename(); ?>"/>
			</navPoint>
		<?php } ?>
	</navMap>
</ncx>